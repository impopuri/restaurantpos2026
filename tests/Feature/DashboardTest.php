<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reports_paid_sales_and_top_seller_per_category(): void
    {
        $this->actingAs(User::factory()->create());
        $cashier = auth()->user();
        $today = now()->startOfDay()->addHours(10);
        $yesterday = now()->subDay();

        $order = Order::create([
            'user_id' => $cashier->id,
            'status' => 'pending',
            'subtotal' => 140,
            'discount_amount' => 10,
            'total' => 130,
            'paid_at' => $today,
        ]);
        $order->items()->createMany([
            ['product_key' => 'tohsilog', 'category' => 'MEALS', 'name' => 'Tohsilog', 'unit_price' => 69, 'quantity' => 2],
            ['product_key' => 'mahsilog', 'category' => 'MEALS', 'name' => 'Mahsilog', 'unit_price' => 69, 'quantity' => 1],
            ['product_key' => 'fries', 'category' => 'SNACKS', 'name' => 'Fries - Cheese', 'unit_price' => 50, 'quantity' => 1],
        ]);

        $olderOrder = Order::create([
            'user_id' => $cashier->id,
            'status' => 'served',
            'subtotal' => 200,
            'discount_amount' => 0,
            'total' => 200,
            'paid_at' => $yesterday,
        ]);
        $olderOrder->items()->create([
            'product_key' => 'mahsilog',
            'category' => 'MEALS',
            'name' => 'Mahsilog',
            'unit_price' => 69,
            'quantity' => 4,
        ]);

        Order::create([
            'user_id' => $cashier->id,
            'status' => 'pending',
            'subtotal' => 900,
            'discount_amount' => 0,
            'total' => 900,
            'paid_at' => null,
        ]);

        $this->get(route('dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('₱130.00')
            ->assertSee('1', false)
            ->assertSee('₱10.00')
            ->assertSee('data-sales-chart', false)
            ->assertSee('class="chart-line"', false)
            ->assertSee('₱130.00')
            ->assertSee('Tohsilog')
            ->assertSee('Fries - Cheese')
            ->assertSee('No paid sales in this period.');

        $this->get(route('dashboard', ['period' => 'all']))
            ->assertOk()
            ->assertSee('₱330.00')
            ->assertSee('Mahsilog');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect('/');
    }

    public function test_sales_csv_lists_items_and_ends_with_quantity_and_price_totals(): void
    {
        $this->actingAs(User::factory()->create());
        $order = Order::create([
            'user_id' => auth()->id(),
            'status' => 'pending',
            'subtotal' => 188,
            'discount_amount' => 0,
            'total' => 188,
            'paid_at' => now()->setTime(14, 5, 9),
        ]);
        $order->items()->createMany([
            ['product_key' => 'tohsilog', 'category' => 'MEALS', 'name' => 'Tohsilog', 'unit_price' => 69, 'quantity' => 2],
            ['product_key' => 'fries', 'category' => 'SNACKS', 'name' => 'Fries', 'unit_price' => 50, 'quantity' => 1],
        ]);

        $response = $this->get(route('dashboard.export', ['period' => 'today']))
            ->assertOk()
            ->assertDownload();
        $csv = array_map('str_getcsv', array_filter(explode("\n", trim($response->streamedContent()))));

        $this->assertSame(['Date', 'Time', 'Item', 'Quantity', 'Total Price'], $csv[2]);
        $this->assertSame([now()->format('Y-m-d'), '14:05:09', 'Tohsilog', '2', '138.00'], $csv[3]);
        $this->assertSame([now()->format('Y-m-d'), '14:05:09', 'Fries', '1', '50.00'], $csv[4]);
        $this->assertSame(['TOTAL', '', '', '3', '188.00'], $csv[5]);
    }
}