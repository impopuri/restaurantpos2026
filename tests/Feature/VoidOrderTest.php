<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoidOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_void_excludes_sales_restores_stock_and_is_audited(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $admin = User::factory()->create(['role' => 'superadmin']);
        $hotdog = InventoryItem::where('item_key', 'hotdog')->firstOrFail();
        $hotdog->update(['quantity' => 3]);
        $order = Order::create([
            'user_id' => $cashier->id,
            'status' => 'cooking',
            'subtotal' => 138,
            'discount_amount' => 0,
            'total' => 138,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'product_key' => 'tohsilog',
            'category' => 'MEALS',
            'name' => 'Tohsilog',
            'unit_price' => 69,
            'quantity' => 2,
        ]);
        InventoryMovement::create([
            'inventory_item_id' => $hotdog->id,
            'user_id' => $cashier->id,
            'order_id' => $order->id,
            'movement_type' => 'sale',
            'quantity_change' => -2,
            'note' => 'Used for order',
        ]);

        $this->actingAs($cashier)->post(route('orders.void', $order), [
            'void_reason' => 'Customer cancelled before preparation.',
        ])->assertRedirect(route('kitchen'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'voided',
            'voided_by' => $cashier->id,
            'void_reason' => 'Customer cancelled before preparation.',
        ]);
        $this->assertEquals(5, $hotdog->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'order_id' => $order->id,
            'inventory_item_id' => $hotdog->id,
            'movement_type' => 'void_reversal',
            'quantity_change' => '2.000',
        ]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'name' => 'Tohsilog']);

        $this->actingAs($admin)->get(route('superadmin.cash-flow', ['period' => 'today']))
            ->assertOk()
            ->assertSee('₱0.00');
        $this->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('TODAY')
            ->assertSee('₱0.00');
        $this->get(route('dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('₱0.00');
        $this->get(route('superadmin.voided-orders'))
            ->assertOk()
            ->assertSee('Customer cancelled before preparation.')
            ->assertSee('Hotdog +2 pcs');
    }

    public function test_void_requires_reason_and_cannot_be_applied_twice(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $order = Order::create([
            'user_id' => $cashier->id,
            'status' => 'pending',
            'subtotal' => 20,
            'discount_amount' => 0,
            'total' => 20,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->from(route('kitchen'))
            ->post(route('orders.void', $order), ['void_reason' => ''])
            ->assertSessionHasErrors('void_reason');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);

        $this->post(route('orders.void', $order), ['void_reason' => 'Duplicate order entered.'])
            ->assertRedirect(route('kitchen'));
        $this->postJson(route('orders.void', $order), ['void_reason' => 'Second attempt.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');
        $this->assertSame(1, Order::where('status', 'voided')->count());
    }
}