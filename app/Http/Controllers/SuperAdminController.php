<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function __invoke(): View
    {
        return view('superadmin.dashboard', [
            'cashierCount' => User::where('role', 'cashier')->count(),
            'superAdminCount' => User::where('role', 'superadmin')->count(),
            'todaySales' => Order::whereNotNull('paid_at')
                ->where('status', '!=', 'voided')
                ->whereDate('paid_at', today())
                ->sum('total'),
            'lowStockCount' => \App\Models\InventoryItem::where('low_stock_threshold', '>', 0)
                ->whereColumn('quantity', '<=', 'low_stock_threshold')
                ->count(),
            'todayExpenses' => \App\Models\Expense::whereDate('expense_date', today())->sum('amount'),
        ]);
    }
}