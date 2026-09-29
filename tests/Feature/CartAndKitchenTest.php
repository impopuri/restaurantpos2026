<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAndKitchenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        InventoryItem::query()->update(['quantity' => 1000]);
    }

    public function test_cashier_can_add_edit_and_send_order_to_kitchen(): void
    {
        $cashier = User::factory()->create(['username' => 'cashier']);
        $this->actingAs($cashier);

        $this->post(route('cart.add'), ['product_key' => 'tohsilog'])
            ->assertRedirect(route('pos'));
        $this->post(route('cart.add'), ['product_key' => 'tohsilog'])
            ->assertRedirect(route('pos'));

        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('Tohsilog')
            ->assertSee('value="2"', false);

        $this->patch(route('cart.update', 'tohsilog'), ['quantity' => 3])
            ->assertRedirect(route('cart'));

        $this->post(route('checkout'), [
            'kitchen_note' => 'No onions, please.',
            'discount_type' => 'none',
            'payment_method' => 'cash',
        ])->assertRedirect(route('kitchen'));

        $order = Order::with('items')->firstOrFail();
        $this->assertSame('No onions, please.', $order->kitchen_note);
        $this->assertSame(3, $order->items->first()->quantity);
        $this->assertSame('Tohsilog', $order->items->first()->name);
        $this->assertEquals('69.00', $order->items->first()->unit_price);
        $this->assertSame([], session('cart', []));

        $this->get(route('kitchen'))
            ->assertOk()
            ->assertSee('No onions, please.')
            ->assertSee('Tohsilog')
            ->assertSee('Pending')
            ->assertSee('Cooking')
            ->assertSee('Served')
            ->assertDontSee('Ready to Serve');

        $this->patch(route('kitchen.status', $order), ['status' => 'cooking'])
            ->assertRedirect(route('kitchen'));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cooking']);

        $this->patch(route('kitchen.status', $order), ['status' => 'served'])
            ->assertRedirect(route('kitchen'));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'served']);

        $this->patch(route('kitchen.status', $order), ['status' => 'ready'])
            ->assertSessionHasErrors('status');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'served']);
    }

    public function test_checkout_uses_catalog_price_and_rejects_unknown_products(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('cart.add'), ['product_key' => 'unknown-product'])
            ->assertNotFound();

        $this->post(route('cart.add'), ['product_key' => 'fishball'])
            ->assertRedirect(route('pos'));
        $this->from(route('cart'))->post(route('checkout'), [])
            ->assertSessionHasErrors(['discount_type', 'payment_method'])
            ->assertRedirect(route('cart'));

        $this->post(route('checkout'), [
            'discount_type' => 'none',
            'payment_method' => 'cash',
        ])
            ->assertRedirect(route('kitchen'));

        $this->assertDatabaseHas('orders', ['kitchen_note' => null]);
        $this->assertDatabaseHas('order_items', [
            'name' => 'Fishball',
            'unit_price' => '2.00',
            'quantity' => 1,
        ]);

        $this->get(route('kitchen'))
            ->assertOk()
            ->assertDontSee('Kitchen note')
            ->assertDontSee('No special instructions.');
    }

    public function test_cart_quantity_can_be_updated_with_json(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('cart.add'), ['product_key' => 'tohsilog']);

        $this->patchJson(route('cart.update', 'tohsilog'), ['quantity' => 4])
            ->assertOk()
            ->assertJson([
                'quantity' => 4,
                'line_total' => '276.00',
                'total' => '276.00',
                'cart_count' => 4,
            ]);
    }

    public function test_senior_discount_is_fixed_and_payment_is_saved(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('cart.add'), ['product_key' => 'tohsilog']);

        $this->postJson(route('checkout'), [
            'discount_type' => 'senior',
            'discount_rate' => 99,
            'payment_method' => 'gcash',
        ])->assertOk()->assertJson([
            'subtotal' => '69.00',
            'discount_label' => 'Senior Citizen',
            'discount_rate' => '20.00',
            'discount_amount' => '13.80',
            'total' => '55.20',
            'payment_method' => 'GCash',
        ]);

        $this->assertDatabaseHas('orders', [
            'subtotal' => '69.00',
            'discount_type' => 'senior',
            'discount_rate' => '20.00',
            'discount_amount' => '13.80',
            'total' => '55.20',
            'payment_method' => 'gcash',
        ]);
    }

    public function test_custom_discount_and_other_payment_require_names(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('cart.add'), ['product_key' => 'tohsilog']);

        $this->postJson(route('checkout'), [
            'discount_type' => 'other',
            'discount_rate' => 10,
            'payment_method' => 'others',
        ])->assertUnprocessable()->assertJsonValidationErrors(['discount_label', 'payment_other']);

        $this->postJson(route('checkout'), [
            'discount_type' => 'other',
            'discount_label' => 'Staff discount',
            'discount_rate' => 10,
            'payment_method' => 'others',
            'payment_other' => 'Store credit',
        ])->assertOk()->assertJson([
            'discount_label' => 'Staff discount',
            'discount_rate' => '10.00',
            'discount_amount' => '6.90',
            'total' => '62.10',
            'payment_method' => 'Store credit',
        ]);
    }
}