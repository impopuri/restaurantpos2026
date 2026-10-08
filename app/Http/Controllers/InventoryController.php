<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryRecipe;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        return view('superadmin.inventory', [
            'inventoryItems' => InventoryItem::with('recipes.menuItem')->orderBy('name')->get(),
            'menuItems' => MenuItem::orderBy('category')->orderBy('name')->get(),
            'trackableMenuItems' => MenuItem::whereIn('category', ['MEALS', 'SNACKS', 'DRINKS'])
                ->orWhere(fn ($query) => $query->where('category', 'EXTRAS')->where('item_key', 'egg'))
                ->orderBy('category')->orderBy('name')->get(),
            'recipes' => InventoryRecipe::with(['menuItem', 'inventoryItem'])->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:inventory_items,name'],
            'unit' => ['required', 'string', 'max:30'],
            'low_stock_threshold' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ]);
        $data['item_key'] = \Illuminate\Support\Str::slug($data['name']).'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(5));
        $data['quantity'] = 0;
        $item = InventoryItem::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Inventory item added.',
                'item' => $item->only(['id', 'name', 'unit', 'quantity', 'low_stock_threshold']),
            ], 201);
        }

        return redirect()->route('superadmin.inventory')->with('status', 'Inventory item added. Enter opening stock below.');
    }

    public function adjust(Request $request, InventoryItem $inventoryItem): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'adjustment' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'direction' => ['required', 'in:add,remove'],
            'note' => ['nullable', 'string', 'max:120'],
            'low_stock_threshold' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ]);
        $change = (float) $data['adjustment'] * ($data['direction'] === 'add' ? 1 : -1);

        DB::transaction(function () use ($request, $inventoryItem, $change, $data): void {
            $lockedItem = InventoryItem::whereKey($inventoryItem->id)->lockForUpdate()->firstOrFail();
            abort_if((float) $lockedItem->quantity + $change < 0, 422, 'Stock cannot be reduced below zero.');
            $lockedItem->low_stock_threshold = $data['low_stock_threshold'];
            $lockedItem->save();
            $lockedItem->increment('quantity', $change);
            InventoryMovement::create([
                'inventory_item_id' => $lockedItem->id,
                'user_id' => $request->user()->id,
                'movement_type' => $change > 0 ? 'stock_in' : 'adjustment',
                'quantity_change' => $change,
                'note' => $data['note'] ?? null,
            ]);
        });

        if ($request->expectsJson()) {
            $inventoryItem->refresh();

            return response()->json([
                'message' => 'Stock quantity updated.',
                'quantity' => number_format((float) $inventoryItem->quantity, 3, '.', ''),
                'low_stock_threshold' => number_format((float) $inventoryItem->low_stock_threshold, 3, '.', ''),
            ]);
        }

        return redirect()->route('superadmin.inventory')->with('status', 'Stock quantity updated.');
    }

    public function storeRecipe(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'menu_item_id' => ['required', 'integer', function (string $attribute, mixed $value, \Closure $fail): void {
                $menuItem = MenuItem::find($value);
                if (! $menuItem || (! in_array($menuItem->category, ['MEALS', 'SNACKS', 'DRINKS'], true) && ! ($menuItem->category === 'EXTRAS' && $menuItem->item_key === 'egg'))) {
                    $fail('Inventory recipes are available for meals, snacks, drinks, and the Egg extra only.');
                }
            }],
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'quantity_per_item' => ['required', 'numeric', 'gt:0', 'max:999999'],
        ]);

        $recipe = InventoryRecipe::updateOrCreate(
            ['menu_item_id' => $data['menu_item_id'], 'inventory_item_id' => $data['inventory_item_id']],
            ['quantity_per_item' => $data['quantity_per_item']],
        );

        if ($request->expectsJson()) {
            $recipe->load(['inventoryItem', 'menuItem']);

            return response()->json([
                'message' => 'Recipe usage saved.',
                'recipe' => [
                    'id' => $recipe->id,
                    'menu_item_id' => $recipe->menu_item_id,
                    'inventory_item_id' => $recipe->inventory_item_id,
                    'inventory_item_name' => $recipe->inventoryItem->name,
                    'unit' => $recipe->inventoryItem->unit,
                    'quantity_per_item' => number_format((float) $recipe->quantity_per_item, 3, '.', ''),
                ],
            ]);
        }

        return redirect()->route('superadmin.inventory')->with('status', 'Recipe usage saved.');
    }

    public function destroyRecipe(Request $request, InventoryRecipe $recipe): RedirectResponse|JsonResponse
    {
        $recipeId = $recipe->id;
        $recipe->delete();

        if ($request->expectsJson()) {
            return response()->json(['recipe_id' => $recipeId, 'message' => 'Recipe mapping removed.']);
        }

        return redirect()->route('superadmin.inventory')->with('status', 'Recipe mapping removed.');
    }
}