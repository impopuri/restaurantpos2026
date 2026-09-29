<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryRecipe;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\ReceiptSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_clears_transactions_and_stock_but_preserves_configuration(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $menuItem = MenuItem::where('item_key', 'tohsilog')->firstOrFail();
        $egg = InventoryItem::where('item_key', 'egg')->firstOrFail();
        $egg->update(['quantity' => 23, 'low_stock_threshold' => 4]);
        $recipeCount = InventoryRecipe::count();
        ReceiptSetting::updateOrCreate(['id' => 1], [
            'business_name' => 'Saved Business Name',
            'paper_width' => '58',
            'survey_url' => 'https://example.com/survey',
        ]);
        $order = Order::create([
            'user_id' => $cashier->id,
            'status' => 'served',
            'subtotal' => 69,
            'discount_amount' => 0,
            'total' => 69,
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'product_key' => $menuItem->item_key,
            'category' => 'MEALS',
            'name' => $menuItem->name,
            'unit_price' => $menuItem->price,
            'quantity' => 1,
        ]);
        Expense::create([
            'user_id' => $admin->id,
            'description' => 'Opening test expense',
            'category' => 'Supplies',
            'amount' => 12.50,
            'payment_method' => 'cash',
            'expense_date' => today(),
        ]);
        InventoryMovement::create([
            'inventory_item_id' => $egg->id,
            'user_id' => $admin->id,
            'order_id' => $order->id,
            'movement_type' => 'stock_in',
            'quantity_change' => 23,
            'note' => 'Opening stock',
        ]);

        $this->actingAs($admin)
            ->from(route('superadmin.reset.index'))
            ->post(route('superadmin.reset.run'), ['confirmation' => 'RESET BUSINESS DATA'])
            ->assertRedirect(route('superadmin.dashboard'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertEquals(0, $egg->fresh()->quantity);
        $this->assertEquals(4, $egg->fresh()->low_stock_threshold);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'superadmin']);
        $this->assertDatabaseHas('users', ['id' => $cashier->id, 'role' => 'cashier']);
        $this->assertDatabaseHas('menu_items', ['id' => $menuItem->id, 'item_key' => 'tohsilog']);
        $this->assertSame($recipeCount, InventoryRecipe::count());
        $this->assertDatabaseHas('receipt_settings', [
            'business_name' => 'Saved Business Name',
            'paper_width' => '58',
        ]);
    }

    public function test_reset_requires_exact_confirmation_and_superadmin_access(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $cashier = User::factory()->create(['role' => 'cashier']);
        Order::create(['user_id' => $cashier->id, 'status' => 'pending']);

        $this->actingAs($cashier)->get(route('superadmin.reset.index'))->assertForbidden();

        $this->actingAs($admin)
            ->post(route('superadmin.reset.run'), ['confirmation' => 'reset'])
            ->assertSessionHasErrors('confirmation');

        $this->assertDatabaseCount('orders', 1);
    }
}