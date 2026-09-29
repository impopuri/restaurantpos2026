<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ReceiptSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = session('cart', []);
        $products = MenuItem::with('recipes.inventoryItem')->get()->keyBy('item_key');
        $items = collect($cart)
            ->map(function (int $quantity, string $cartKey) use ($products) {
                [$key, $choice] = array_pad(explode('|', $cartKey, 2), 2, null);
                if (! isset($products[$key])) {
                    return null;
                }

                return [
                    'key' => $cartKey,
                    'item_key' => $key,
                    'name' => $products[$key]->name.($choice ? " - {$choice}" : ''),
                    'price' => $products[$key]->price,
                    'quantity' => $quantity,
                ];
            })
            ->filter()
            ->values();

        return view('cart', [
            'items' => $items,
            'cartCount' => array_sum($cart),
            'total' => $items->sum(fn (array $item) => (float) $item['price'] * $item['quantity']),
            'receiptSettings' => ReceiptSetting::firstOrCreate(['id' => 1]),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_key' => ['required', 'string'],
            'choice' => ['nullable', 'string', 'max:80'],
        ]);
        $product = MenuItem::where('item_key', $validated['product_key'])->firstOrFail();
        $choice = trim($validated['choice'] ?? '');
        abort_if($choice !== '' && ! in_array($choice, $product->options ?? [], true), 422);
        $cartKey = $product->item_key.($choice !== '' ? '|'.$choice : '');

        $cart = $request->session()->get('cart', []);
        $cart[$cartKey] = min(($cart[$cartKey] ?? 0) + 1, 99);
        $request->session()->put('cart', $cart);

        return redirect()->route('pos')->with('status', 'Item added to the cart.');
    }

    public function update(Request $request, string $productKey): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $cart = $request->session()->get('cart', []);
        [$itemKey] = explode('|', $productKey, 2);
        abort_unless(isset($cart[$productKey]) && MenuItem::where('item_key', $itemKey)->exists(), 404);

        $cart[$productKey] = $validated['quantity'];
        $request->session()->put('cart', $cart);

        if ($request->expectsJson()) {
            $products = MenuItem::all()->keyBy('item_key');
            $total = 0;
            foreach ($cart as $cartKey => $quantity) {
                [$key] = explode('|', $cartKey, 2);
                if (isset($products[$key])) {
                    $total += (float) $products[$key]->price * $quantity;
                }
            }
            [$itemKey] = explode('|', $productKey, 2);

            return response()->json([
                'quantity' => $validated['quantity'],
                'line_total' => number_format((float) $products[$itemKey]->price * $validated['quantity'], 2),
                'total' => number_format($total, 2),
                'cart_count' => array_sum($cart),
            ]);
        }

        return redirect()->route('cart')->with('status', 'Quantity updated.');
    }

    public function remove(Request $request, string $productKey): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$productKey]);
        $request->session()->put('cart', $cart);

        return redirect()->route('cart');
    }

    public function checkout(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'kitchen_note' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', 'in:none,senior,pwd,other'],
            'discount_label' => ['nullable', 'required_if:discount_type,other', 'string', 'max:80'],
            'discount_rate' => ['nullable', 'required_if:discount_type,other', 'numeric', 'min:0', 'max:100'],
            'payment_method' => ['required', 'in:cash,gcash,maya,maribank,others'],
            'payment_other' => ['nullable', 'required_if:payment_method,others', 'string', 'max:80'],
        ]);
        $cart = $request->session()->get('cart', []);
        $products = MenuItem::with('recipes.inventoryItem')->get()->keyBy('item_key');
        $items = collect($cart)->filter(function ($quantity, $cartKey) use ($products) {
            [$itemKey] = explode('|', $cartKey, 2);
            return isset($products[$itemKey]) && $quantity > 0;
        });

        if ($items->isEmpty()) {
            return redirect()->route('pos')->withErrors(['cart' => 'Add at least one item before checkout.']);
        }

        $subtotal = 0;
        foreach ($items as $cartKey => $quantity) {
            [$itemKey] = explode('|', $cartKey, 2);
            $subtotal += (float) $products[$itemKey]->price * $quantity;
        }
        $subtotal = round($subtotal, 2);
        $discountRate = match ($validated['discount_type']) {
            'senior', 'pwd' => 20.0,
            'other' => (float) $validated['discount_rate'],
            default => 0.0,
        };
        $discountLabel = match ($validated['discount_type']) {
            'senior' => 'Senior Citizen',
            'pwd' => 'PWD',
            'other' => $validated['discount_label'],
            default => null,
        };
        $discountAmount = round($subtotal * $discountRate / 100, 2);
        $total = round($subtotal - $discountAmount, 2);
        $paymentMethod = $validated['payment_method'];
        $paymentLabel = $paymentMethod === 'others'
            ? $validated['payment_other']
            : match ($paymentMethod) {
                'gcash' => 'GCash',
                'maya' => 'Maya',
                'maribank' => 'MariBank',
                default => 'Cash',
            };

        $order = DB::transaction(function () use ($request, $validated, $items, $products, $subtotal, $discountRate, $discountLabel, $discountAmount, $total, $paymentMethod): Order {
            $requiredStock = [];
            foreach ($items as $cartKey => $quantity) {
                [$itemKey] = explode('|', $cartKey, 2);
                foreach ($products[$itemKey]->recipes as $recipe) {
                    $ingredientId = $recipe->inventory_item_id;
                    $requiredStock[$ingredientId] = ($requiredStock[$ingredientId] ?? 0)
                        + (float) $recipe->quantity_per_item * $quantity;
                }
            }

            $lockedStock = InventoryItem::whereIn('id', array_keys($requiredStock))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($requiredStock as $ingredientId => $requiredQuantity) {
                $inventoryItem = $lockedStock->get($ingredientId);
                if (! $inventoryItem || (float) $inventoryItem->quantity < $requiredQuantity) {
                    $ingredientName = $inventoryItem?->name ?? 'Required ingredient';
                    throw ValidationException::withMessages([
                        'inventory' => "Not enough {$ingredientName} in inventory to complete this order.",
                    ]);
                }
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'kitchen_note' => $validated['kitchen_note'] ?? null,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount_type' => $validated['discount_type'],
                'discount_label' => $discountLabel,
                'discount_rate' => $discountRate,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'payment_other' => $paymentMethod === 'others' ? $validated['payment_other'] : null,
                'paid_at' => now(),
            ]);

            foreach ($items as $cartKey => $quantity) {
                [$itemKey, $choice] = array_pad(explode('|', $cartKey, 2), 2, null);
                $product = $products[$itemKey];
                $order->items()->create([
                    'product_key' => $itemKey,
                    'category' => $product->category,
                    'name' => $product->name.($choice ? " - {$choice}" : ''),
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                ]);
            }

            foreach ($requiredStock as $ingredientId => $requiredQuantity) {
                $lockedStock[$ingredientId]->decrement('quantity', $requiredQuantity);
                InventoryMovement::create([
                    'inventory_item_id' => $ingredientId,
                    'user_id' => $request->user()->id,
                    'order_id' => $order->id,
                    'movement_type' => 'sale',
                    'quantity_change' => -$requiredQuantity,
                    'note' => "Used for order #{$order->id}",
                ]);
            }

            return $order;
        });

        $request->session()->forget('cart');

        if ($request->expectsJson()) {
            return response()->json([
                'order_id' => $order->id,
                'date' => $order->created_at->format('M j, Y g:i A'),
                'cashier' => $request->user()->username,
                'items' => $items->map(function ($quantity, $cartKey) use ($products) {
                    [$itemKey, $choice] = array_pad(explode('|', $cartKey, 2), 2, null);
                    $product = $products[$itemKey];
                    return [
                    'name' => $product->name.($choice ? " - {$choice}" : ''),
                    'quantity' => $quantity,
                    'unit_price' => number_format((float) $product->price, 2),
                    'line_total' => number_format((float) $product->price * $quantity, 2),
                    ];
                })->values(),
                'subtotal' => number_format($subtotal, 2),
                'discount_label' => $discountLabel,
                'discount_rate' => number_format($discountRate, 2),
                'discount_amount' => number_format($discountAmount, 2),
                'total' => number_format($total, 2),
                'payment_method' => $paymentLabel,
                'kitchen_note' => $order->kitchen_note,
            ]);
        }

        return redirect()->route('kitchen')->with('status', "Order #{$order->id} sent to the kitchen.");
    }

}