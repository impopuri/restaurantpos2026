<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BusinessResetController extends Controller
{
    public function index(): View
    {
        return view('superadmin.reset-business-data', [
            'orderCount' => DB::table('orders')->count(),
            'expenseCount' => DB::table('expenses')->count(),
            'movementCount' => DB::table('inventory_movements')->count(),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'confirmation' => ['required', 'in:RESET BUSINESS DATA'],
        ]);

        DB::transaction(function (): void {
            DB::table('inventory_movements')->delete();
            DB::table('expenses')->delete();
            DB::table('orders')->delete();
            DB::table('inventory_items')->update(['quantity' => 0]);
        });

        return redirect()->route('superadmin.dashboard')->with(
            'status',
            'Business activity was reset. Accounts, menu, recipes, receipt settings, and stock thresholds were preserved; inventory quantities are now zero.'
        );
    }
}