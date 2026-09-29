<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MenuController extends Controller
{
    private const CATEGORIES = ['MEALS', 'SNACKS', 'DRINKS', 'EXTRAS'];

    public function index(): View
    {
        return view('menu.index', [
            'items' => MenuItem::orderBy('name')->get()->sortBy(
                fn (MenuItem $item) => array_search($item->category, self::CATEGORIES, true)
            )->values(),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateItem($request);
        $validated['item_key'] = Str::slug($validated['name']).'-'.Str::lower(Str::random(6));
        $validated['options'] = $this->parseOptions($validated['options'] ?? null);
        MenuItem::create($validated);

        return redirect()->route('superadmin.menu.index')->with('status', 'Menu item added.');
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $validated = $this->validateItem($request);
        $validated['options'] = $this->parseOptions($validated['options'] ?? null);
        $menuItem->update($validated);

        return redirect()->route('superadmin.menu.index')->with('status', 'Menu item updated.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->delete();

        return redirect()->route('superadmin.menu.index')->with('status', 'Menu item removed.');
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'in:'.implode(',', self::CATEGORIES)],
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'options' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function parseOptions(?string $options): ?array
    {
        $values = collect(preg_split('/[,\r\n]+/', $options ?? ''))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $values === [] ? null : $values;
    }
}