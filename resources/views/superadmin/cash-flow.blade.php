@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('superadmin.dashboard') }}"><img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo"><span><span class="brand-name">ETIVACSILOG</span><span class="brand-subtitle">CASH FLOW</span></span></a>
        <div class="user-area"><a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a><a class="header-link" href="{{ route('superadmin.inventory') }}">Inventory</a><div class="welcome-copy"><span>Administrator</span><strong>{{ auth()->user()->username }}</strong></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Log out</button></form></div>
    </header>

    <section class="page-content admin-page cash-flow-page">
        <div class="page-heading dashboard-heading">
            <div><p class="eyebrow">MONEY MANAGEMENT</p><h1>Cash Flow</h1></div>
            <form method="GET" action="{{ route('superadmin.cash-flow') }}" class="period-filter">
                <label for="cash-flow-period">Report period</label>
                <select id="cash-flow-period" name="period" onchange="this.form.submit()">
                    @foreach (['today' => 'Today', '7days' => 'Last 7 days', 'month' => 'This month', 'all' => 'All time'] as $value => $label)
                        <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        @if (session('status'))<p class="status-message">{{ session('status') }}</p>@endif
        @foreach ($errors->all() as $error)<p class="field-error">{{ $error }}</p>@endforeach

        <section class="cash-flow-summary">
            <article class="cash-flow-total income-total"><span>Sales income</span><strong>₱{{ number_format($incomeTotal, 2) }}</strong><small>{{ $periodLabel }} paid sales</small></article>
            <article class="cash-flow-total expense-total"><span>Expenses</span><strong>₱{{ number_format($expenseTotal, 2) }}</strong><small>{{ $periodLabel }} recorded expenses</small></article>
            <article class="cash-flow-total net-total"><span>Net cash flow</span><strong>₱{{ number_format($netTotal, 2) }}</strong><small>Income minus expenses</small></article>
        </section>

        <section class="cash-flow-breakdowns">
            <article class="cash-flow-panel">
                <header><div><p class="eyebrow">PAID ORDERS</p><h2>Income by payment method</h2></div></header>
                @forelse ($salesByPayment as $row)
                    <div class="cash-flow-row"><span>{{ $row['method'] }} <small>{{ $row['transactions'] }} orders</small></span><strong>₱{{ number_format($row['amount'], 2) }}</strong></div>
                @empty
                    <p class="cash-flow-empty">No paid sales for this period.</p>
                @endforelse
            </article>
            <article class="cash-flow-panel">
                <header><div><p class="eyebrow">OUTGOING</p><h2>Expenses by payment method</h2></div></header>
                @forelse ($expensesByPayment as $row)
                    <div class="cash-flow-row"><span>{{ $row['method'] }} <small>{{ $row['transactions'] }} expenses</small></span><strong>₱{{ number_format($row['amount'], 2) }}</strong></div>
                @empty
                    <p class="cash-flow-empty">No expenses for this period.</p>
                @endforelse
            </article>
        </section>

        <section class="expense-entry-panel">
            <div><p class="eyebrow">RECORD OUTGOING MONEY</p><h2>Add expense</h2></div>
            <form method="POST" action="{{ route('superadmin.cash-flow.expenses.store') }}" class="expense-entry-form">
                @csrf
                <label>Description<input name="description" maxlength="160" required placeholder="e.g. Market supplies"></label>
                <label>Category<select name="category" required>@foreach ($expenseCategories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach</select></label>
                <label>Amount (₱)<input name="amount" type="number" min="0.01" step="0.01" required></label>
                <label>Paid using<select name="payment_method" id="expense-payment-method" required>@foreach ($paymentMethods as $method)<option value="{{ $method }}">{{ ['gcash' => 'GCash', 'maribank' => 'MariBank', 'others' => 'Others'][$method] ?? ucfirst($method) }}</option>@endforeach</select></label>
                <label id="expense-payment-other-wrap" hidden>Other payment method<input name="payment_other" id="expense-payment-other" maxlength="80" placeholder="Enter method"></label>
                <label>Date<input name="expense_date" type="date" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></label>
                <label class="expense-notes-field">Notes<input name="notes" maxlength="500" placeholder="Optional details"></label>
                <button class="primary-button" type="submit">Record expense</button>
            </form>
        </section>

        <section class="expense-history">
            <div class="page-heading"><div><p class="eyebrow">LEDGER</p><h2>Recent expenses</h2></div></div>
            <div class="expense-table-wrap">
                <table class="expense-table">
                    <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Paid using</th><th>Recorded by</th><th class="amount-cell">Amount</th></tr></thead>
                    <tbody>
                        @forelse ($expenses as $expense)
                            <tr><td>{{ $expense->expense_date->format('M j, Y') }}</td><td>{{ $expense->description }}@if ($expense->notes)<small>{{ $expense->notes }}</small>@endif</td><td>{{ $expense->category }}</td><td>{{ $expense->payment_method === 'others' ? $expense->payment_other : ['gcash' => 'GCash', 'maribank' => 'MariBank'][$expense->payment_method] ?? ucfirst($expense->payment_method) }}</td><td>{{ $expense->user->username }}</td><td class="amount-cell">₱{{ number_format((float) $expense->amount, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="cash-flow-empty">No expenses recorded for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>
<script>
    const expensePaymentMethod = document.querySelector('#expense-payment-method');
    const otherPaymentWrap = document.querySelector('#expense-payment-other-wrap');
    const otherPaymentInput = document.querySelector('#expense-payment-other');
    function updateOtherPaymentField() {
        const isOther = expensePaymentMethod.value === 'others';
        otherPaymentWrap.hidden = !isOther;
        otherPaymentInput.required = isOther;
    }
    expensePaymentMethod.addEventListener('change', updateOtherPaymentField);
    updateOtherPaymentField();
</script>
@endsection
