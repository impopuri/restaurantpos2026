@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('superadmin.dashboard') }}">
            <img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo">
            <span><span class="brand-name">ETIVACSILOG</span><span class="brand-subtitle">SUPERADMIN</span></span>
        </a>
        <div class="user-area">
            <a class="header-link" href="{{ route('dashboard') }}">Sales</a>
            <a class="header-link" href="{{ route('pos') }}">POS</a>
            <div class="welcome-copy"><span>Administrator</span><strong>{{ auth()->user()->username }}</strong></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Log out</button></form>
        </div>
    </header>
    <section class="page-content admin-page">
        <div class="page-heading"><div><p class="eyebrow">SYSTEM CONTROL</p><h1>Administration</h1></div></div>
        <div class="admin-shortcuts">
            <a href="{{ route('superadmin.accounts') }}"><span>ACCESS</span><strong>Account management</strong><small>{{ $cashierCount }} cashier accounts · {{ $superAdminCount }} superadmin accounts</small></a>
            <a href="{{ route('superadmin.menu.index') }}"><span>CATALOG</span><strong>Manage menu</strong><small>Edit menu names, choices, categories, and prices</small></a>
            <a href="{{ route('superadmin.inventory') }}"><span>STOCK</span><strong>Inventory &amp; recipes</strong><small>{{ $lowStockCount }} items at or below minimum stock</small></a>
            <a href="{{ route('superadmin.cash-flow') }}"><span>FINANCE</span><strong>Cash Flow</strong><small>Sales income, expenses and balances by payment method</small></a>
            <a href="{{ route('superadmin.receipt-settings') }}"><span>CHECKOUT</span><strong>Receipt editor</strong><small>Business details, survey link, and printer paper width</small></a>
            <a href="{{ route('superadmin.voided-orders') }}"><span>AUDIT</span><strong>Voided orders</strong><small>Review void reasons and inventory reversals</small></a>
        </div>
        <section class="admin-sales-snapshot"><p class="eyebrow">TODAY</p><h2>₱{{ number_format((float) $todaySales, 2) }}</h2><p>Paid sales so far today</p></section>
        <section class="admin-sales-snapshot"><p class="eyebrow">TODAY'S EXPENSES</p><h2>₱{{ number_format((float) $todayExpenses, 2) }}</h2><p>Recorded expenses today</p></section>
        <section class="admin-reset-entry">
            <div><p class="eyebrow">DEPLOYMENT PREPARATION</p><h2>Reset business data</h2><p>Clear test transactions and expenses, then start inventory at zero. Accounts, menu, recipes, and receipt settings are kept.</p></div>
            <a class="danger-link" href="{{ route('superadmin.reset.index') }}">Review reset</a>
        </section>
    </section>
</main>
@endsection
