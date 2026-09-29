<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SuperAdminInventoryArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_superadmin_tools_and_admin_can_manage_accounts(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier)
            ->get(route('superadmin.dashboard'))
            ->assertForbidden();
        $this->get(route('superadmin.menu.index'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($admin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Administration');
        $this->get(route('superadmin.inventory'))->assertOk()->assertSee('Eggs');
        $this->get(route('superadmin.menu.index'))->assertOk()->assertSee('Manage menu');
        $this->get(route('superadmin.accounts'))->assertOk()->assertSee('Create account');
        $this->get(route('superadmin.accounts'))->assertDontSee('Email');

        $this->post(route('superadmin.accounts.store'), [
            'name' => 'New Cashier',
            'username' => 'new-cashier',
            'role' => 'cashier',
            'password' => 'cashier-password',
            'password_confirmation' => 'cashier-password',
        ])->assertRedirect(route('superadmin.accounts'));

        $this->assertDatabaseHas('users', ['username' => 'new-cashier', 'role' => 'cashier']);
        $this->assertNotEmpty(\App\Models\User::where('username', 'new-cashier')->value('email'));
    }

    public function test_meal_and_extra_checkout_deducts_recipe_stock_and_records_movements(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier);
        $egg = InventoryItem::where('item_key', 'egg')->firstOrFail();
        $hotdog = InventoryItem::where('item_key', 'hotdog')->firstOrFail();
        $egg->update(['quantity' => 10]);
        $hotdog->update(['quantity' => 5]);

        $this->post(route('cart.add'), ['product_key' => 'tohsilog']);
        $this->post(route('cart.add'), ['product_key' => 'tohsilog']);
        $this->post(route('cart.add'), ['product_key' => 'egg']);

        $this->postJson(route('checkout'), [
            'discount_type' => 'none',
            'payment_method' => 'cash',
        ])->assertOk();

        $this->assertEquals(7, $egg->fresh()->quantity);
        $this->assertEquals(3, $hotdog->fresh()->quantity);
        $this->assertSame(1, InventoryMovement::where('inventory_item_id', $egg->id)->where('movement_type', 'sale')->count());
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $egg->id,
            'quantity_change' => '-3.000',
            'movement_type' => 'sale',
        ]);
    }

    public function test_checkout_is_rejected_without_enough_recipe_stock(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cashier']));
        InventoryItem::where('item_key', 'egg')->update(['quantity' => 0]);
        InventoryItem::where('item_key', 'hotdog')->update(['quantity' => 0]);
        $this->post(route('cart.add'), ['product_key' => 'tohsilog']);

        $this->postJson(route('checkout'), [
            'discount_type' => 'none',
            'payment_method' => 'cash',
        ])->assertUnprocessable()->assertJsonValidationErrors('inventory');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, array_sum(session('cart')));
    }

    public function test_cashier_pos_warns_for_configured_low_stock_only(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $lowStock = InventoryItem::where('item_key', 'hotdog')->firstOrFail();
        $lowStock->update(['quantity' => 2, 'low_stock_threshold' => 3]);

        $this->actingAs($cashier)->get(route('pos'))
            ->assertOk()
            ->assertSee('Low stock warning')
            ->assertSee('Hotdog')
            ->assertSee('2.000 pcs left');

        $lowStock->update(['quantity' => 0]);
        $this->get(route('pos'))->assertSee('Out of stock');

        InventoryItem::where('item_key', 'ham')->update(['quantity' => 0, 'low_stock_threshold' => 0]);
        $this->get(route('pos'))->assertDontSee('Ham');
    }

    public function test_snacks_and_drinks_deduct_matching_stock_but_non_egg_extras_are_untracked(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $siomaiStock = InventoryItem::where('item_key', 'siomai')->firstOrFail();
        $siomaiStock->update(['quantity' => 20]);
        $cocaColaStock = InventoryItem::where('item_key', 'menu-coca-cola-290ml')->firstOrFail();
        $cocaColaStock->update(['quantity' => 12]);

        $this->actingAs($cashier);
        $this->post(route('cart.add'), ['product_key' => 'siomai', 'choice' => 'Fried']);
        $this->post(route('cart.add'), ['product_key' => 'coca-cola-290ml']);
        $this->postJson(route('checkout'), [
            'discount_type' => 'none',
            'payment_method' => 'cash',
        ])->assertOk();

        $this->assertEquals(19, $siomaiStock->fresh()->quantity);
        $this->assertEquals(11, $cocaColaStock->fresh()->quantity);
        $this->assertSame(1, InventoryMovement::where('inventory_item_id', $siomaiStock->id)->where('movement_type', 'sale')->count());
        $this->assertSame(1, InventoryMovement::where('inventory_item_id', $cocaColaStock->id)->where('movement_type', 'sale')->count());

        $admin = User::factory()->create(['role' => 'superadmin']);
        $gravyMenu = \App\Models\MenuItem::where('item_key', 'gravy')->firstOrFail();
        $this->actingAs($admin)
            ->postJson(route('superadmin.inventory.recipes.store'), [
                'menu_item_id' => $gravyMenu->id,
                'inventory_item_id' => $siomaiStock->id,
                'quantity_per_item' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('menu_item_id');
    }

    public function test_only_yesterday_served_orders_are_archived(): void
    {
        Carbon::setTestNow('2026-09-09 00:05:00');
        $cashier = User::factory()->create();
        $served = Order::create([
            'user_id' => $cashier->id,
            'status' => 'served',
            'served_at' => Carbon::parse('2026-09-08 23:40:00'),
        ]);
        $cooking = Order::create([
            'user_id' => $cashier->id,
            'status' => 'cooking',
            'served_at' => null,
        ]);
        $pending = Order::create([
            'user_id' => $cashier->id,
            'status' => 'pending',
            'served_at' => null,
        ]);

        $this->artisan('orders:archive-served')->assertSuccessful();

        $this->assertDatabaseHas('orders', ['id' => $served->id, 'status' => 'archived']);
        $this->assertDatabaseHas('orders', ['id' => $cooking->id, 'status' => 'cooking', 'archived_at' => null]);
        $this->assertDatabaseHas('orders', ['id' => $pending->id, 'status' => 'pending', 'archived_at' => null]);
        $this->actingAs($cashier)->get(route('kitchen'))->assertOk()->assertDontSee('ORDER #'.$served->id);
        $this->get(route('kitchen.archive'))->assertOk()->assertSee('Order #'.$served->id);

        Carbon::setTestNow();
    }
}