<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_flow_groups_sales_and_expenses_by_payment_method(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        Order::create([
            'user_id' => $admin->id,
            'status' => 'pending',
            'subtotal' => 150,
            'discount_amount' => 0,
            'total' => 150,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);
        Order::create([
            'user_id' => $admin->id,
            'status' => 'pending',
            'subtotal' => 200,
            'discount_amount' => 0,
            'total' => 200,
            'payment_method' => 'others',
            'payment_other' => 'Store credit',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('superadmin.cash-flow.expenses.store'), [
            'description' => 'Market supplies',
            'category' => 'Ingredients',
            'amount' => '45.50',
            'payment_method' => 'gcash',
            'expense_date' => now()->toDateString(),
        ])->assertRedirect(route('superadmin.cash-flow'));

        $this->assertDatabaseHas('expenses', [
            'description' => 'Market supplies',
            'amount' => '45.50',
        ]);

        $this->get(route('superadmin.cash-flow', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Cash Flow')
            ->assertSee('₱350.00')
            ->assertSee('₱45.50')
            ->assertSee('₱304.50')
            ->assertSee('Cash')
            ->assertSee('Store credit')
            ->assertSee('GCash')
            ->assertSee('Market supplies');

        $this->assertDatabaseHas('expenses', [
            'description' => 'Market supplies',
            'payment_method' => 'gcash',
            'amount' => '45.50',
        ]);
    }

    public function test_cashiers_cannot_manage_expenses(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cashier']))
            ->get(route('superadmin.cash-flow'))
            ->assertForbidden();
    }
}