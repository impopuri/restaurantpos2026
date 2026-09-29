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
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_export_downloads_selected_period_as_excel_compatible_csv(): void
    {
        $cashier = User::factory()->create(['username' => 'cashier']);
        $this->actingAs($cashier);
        $order = Order::create([
            'user_id' => $cashier->id,
            'status' => 'pending',
            'subtotal' => 140,
            'discount_amount' => 10,
            'total' => 130,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'product_key' => 'tohsilog',
            'category' => 'MEALS',
            'name' => 'Tohsilog',
            'unit_price' => 140,
            'quantity' => 1,
        ]);

        $response = $this->get(route('dashboard.export', ['period' => 'today']));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename='.now()->format('Ymd').'.csv');

        $this->assertStringContainsString('ETIVACSILOG POS Sales Report', $response->streamedContent());
        $this->assertStringContainsString('Tohsilog x1', $response->streamedContent());
        $this->assertStringContainsString('cash', $response->streamedContent());
        $this->assertStringContainsString('130', $response->streamedContent());
    }
}