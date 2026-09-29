<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderVoidController extends Controller
{
    public function index(): View
    {
        return view('superadmin.voided-orders', [
            'orders' => Order::where('status', 'voided')
                ->with(['items', 'user', 'voidedBy', 'inventoryMovements.inventoryItem'])
                ->latest('voided_at')
                ->paginate(30),
        ]);
    }

    public function __invoke(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $order, $validated): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($lockedOrder->status === 'voided' || $lockedOrder->voided_at) {
                throw ValidationException::withMessages(['order' => 'This order has already been voided.']);
            }

            $saleMovements = InventoryMovement::where('order_id', $lockedOrder->id)
                ->where('movement_type', 'sale')
                ->get()
                ->groupBy('inventory_item_id')
                ->map(fn ($movements) => abs($movements->sum(fn (InventoryMovement $movement) => (float) $movement->quantity_change)));

            $stock = InventoryItem::whereIn('id', $saleMovements->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($saleMovements as $inventoryItemId => $quantity) {
                if ($quantity <= 0 || ! $stock->has($inventoryItemId)) {
                    continue;
                }

                $stock[$inventoryItemId]->increment('quantity', $quantity);
                InventoryMovement::create([
                    'inventory_item_id' => $inventoryItemId,
                    'user_id' => $request->user()->id,
                    'order_id' => $lockedOrder->id,
                    'movement_type' => 'void_reversal',
                    'quantity_change' => $quantity,
                    'note' => "Stock restored for voided order #{$lockedOrder->id}",
                ]);
            }

            $lockedOrder->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $request->user()->id,
                'void_reason' => $validated['void_reason'],
            ]);
        });

        return redirect()->route('kitchen')->with('status', "Order #{$order->id} was voided and logged.");
    }
}