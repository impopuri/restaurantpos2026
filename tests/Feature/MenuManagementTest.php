<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        InventoryItem::query()->update(['quantity' => 1000]);
    }

    public function test_product_choices_are_separate_cart_and_kitchen_lines(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('cart.add'), ['product_key' => 'fries', 'choice' => 'Plain']);
        $this->post(route('cart.add'), ['product_key' => 'fries', 'choice' => 'Cheese']);

        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('Fries - Plain')
            ->assertSee('Fries - Cheese');

        $this->patchJson(route('cart.update', 'fries|Plain'), ['quantity' => 2])
            ->assertOk()
            ->assertJsonPath('line_total', '100.00');

        $this->postJson(route('checkout'), [
            'discount_type' => 'none',
            'payment_method' => 'cash',
        ])->assertOk()->assertJsonPath('items.0.name', 'Fries - Plain')
            ->assertJsonPath('items.1.name', 'Fries - Cheese');

        $this->assertSame(2, Order::withCount('items')->firstOrFail()->items_count);
    }

    public function test_menu_items_can_be_created_updated_and_removed(): void
    {
        $this->actingAs(User::factory()->create());

        $this->actingAs(User::factory()->create(['role' => 'superadmin']));

        $this->post(route('superadmin.menu.store'), [
            'category' => 'SNACKS',
            'name' => 'Test Snack',
            'price' => '12.50',
            'options' => "Small, Large\nExtra large",
        ])->assertRedirect(route('superadmin.menu.index'));

        $item = MenuItem::where('name', 'Test Snack')->firstOrFail();
        $this->assertSame(['Small', 'Large', 'Extra large'], $item->options);

        $this->put(route('superadmin.menu.update', $item), [
            'category' => 'EXTRAS',
            'name' => 'Updated Snack',
            'price' => '15.00',
            'options' => '',
        ])->assertRedirect(route('superadmin.menu.index'));

        $this->assertDatabaseHas('menu_items', [
            'id' => $item->id,
            'category' => 'EXTRAS',
            'name' => 'Updated Snack',
            'price' => '15.00',
        ]);

        $this->post(route('cart.add'), ['product_key' => $item->item_key]);
        $this->postJson(route('checkout'), [
            'discount_type' => 'none',
            'payment_method' => 'maya',
        ])->assertOk()->assertJsonPath('total', '15.00');
        $this->assertDatabaseHas('order_items', ['name' => 'Updated Snack', 'unit_price' => '15.00']);

        $this->delete(route('superadmin.menu.destroy', $item))->assertRedirect(route('superadmin.menu.index'));
        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
    }

    public function test_choice_must_belong_to_the_selected_menu_item(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('cart.add'), [
            'product_key' => 'fries',
            'choice' => 'Steamed',
        ])->assertUnprocessable();
    }
}