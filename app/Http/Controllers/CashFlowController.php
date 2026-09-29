<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CashFlowController extends Controller
{
    private const PAYMENT_METHODS = ['cash', 'gcash', 'maya', 'maribank', 'others'];
    private const EXPENSE_CATEGORIES = ['Ingredients', 'Packaging', 'Utilities', 'Transport', 'Supplies', 'Repairs', 'Other'];

    public function index(Request $request): View
    {
        $period = $request->validate(['period' => ['nullable', 'in:today,7days,month,all']])['period'] ?? 'today';
        [$from, $to] = $this->dateBounds($period);

        $salesQuery = Order::query()->whereNotNull('paid_at')->where('status', '!=', 'voided');
        $expenseQuery = Expense::query();
        if ($from && $to) {
            $salesQuery->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
            $expenseQuery->whereDate('expense_date', '>=', $from->toDateString())
                ->whereDate('expense_date', '<=', $to->toDateString());
        }

        $salesByPayment = (clone $salesQuery)
            ->select('payment_method', 'payment_other', DB::raw('SUM(total) as amount'), DB::raw('COUNT(*) as transactions'))
            ->groupBy('payment_method', 'payment_other')
            ->get()
            ->map(fn ($row) => [
                'method' => $this->paymentLabel($row->payment_method, $row->payment_other),
                'amount' => (float) $row->amount,
                'transactions' => (int) $row->transactions,
            ]);

        $expenses = (clone $expenseQuery)->with('user')->latest('expense_date')->latest()->get();
        $expensesByPayment = $expenses
            ->groupBy(fn (Expense $expense) => $this->paymentLabel($expense->payment_method, $expense->payment_other))
            ->map(fn (Collection $rows, string $method) => [
                'method' => $method,
                'amount' => $rows->sum(fn (Expense $expense) => (float) $expense->amount),
                'transactions' => $rows->count(),
            ])
            ->values();

        $incomeTotal = $salesByPayment->sum('amount');
        $expenseTotal = $expensesByPayment->sum('amount');

        return view('superadmin.cash-flow', [
            'period' => $period,
            'periodLabel' => $this->periodLabel($period),
            'incomeTotal' => $incomeTotal,
            'expenseTotal' => $expenseTotal,
            'netTotal' => $incomeTotal - $expenseTotal,
            'salesByPayment' => $salesByPayment,
            'expensesByPayment' => $expensesByPayment,
            'expensesByCategory' => $expenses->groupBy('category')->map(fn (Collection $rows) => $rows->sum(fn (Expense $expense) => (float) $expense->amount)),
            'expenses' => $expenses->take(30),
            'paymentMethods' => self::PAYMENT_METHODS,
            'expenseCategories' => self::EXPENSE_CATEGORIES,
        ]);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:160'],
            'category' => ['required', 'in:'.implode(',', self::EXPENSE_CATEGORIES)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'payment_method' => ['required', 'in:'.implode(',', self::PAYMENT_METHODS)],
            'payment_other' => ['nullable', 'required_if:payment_method,others', 'string', 'max:80'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $data['user_id'] = $request->user()->id;
        if ($data['payment_method'] !== 'others') {
            $data['payment_other'] = null;
        }

        Expense::create($data);

        return redirect()->route('superadmin.cash-flow')->with('status', 'Expense recorded.');
    }

    private function dateBounds(string $period): array
    {
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            default => [null, null],
        };
    }

    private function paymentLabel(?string $method, ?string $other): string
    {
        if ($method === 'others') {
            return $other ?: 'Others';
        }

        return match ($method) {
            'gcash' => 'GCash',
            'maya' => 'Maya',
            'maribank' => 'MariBank',
            'cash' => 'Cash',
            default => 'Unspecified',
        };
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            'today' => 'Today',
            '7days' => 'Last 7 days',
            'month' => 'This month',
            default => 'All time',
        };
    }
}