@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('superadmin.dashboard') }}"><img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo"><span><span class="brand-name">ETIVACSILOG</span><span class="brand-subtitle">BUSINESS DATA RESET</span></span></a>
        <div class="user-area"><a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a><div class="welcome-copy"><span>Administrator</span><strong>{{ auth()->user()->username }}</strong></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Log out</button></form></div>
    </header>
    <section class="page-content admin-page reset-page">
        <div class="page-heading"><div><p class="eyebrow">DESTRUCTIVE ACTION</p><h1>Reset business data</h1></div><a class="secondary-button link-button" href="{{ route('superadmin.dashboard') }}">Cancel</a></div>
        <section class="reset-warning">
            <span class="reset-warning-mark" aria-hidden="true">!</span>
            <div><h2>This cannot be undone</h2><p>Use this when preparing the POS for a fresh business start. Consider exporting any reports you need first.</p></div>
        </section>
        <section class="reset-scope">
            <h2>Will be cleared</h2>
            <ul>
                <li>{{ number_format($orderCount) }} orders and their order items</li>
                <li>{{ number_format($expenseCount) }} recorded expenses</li>
                <li>{{ number_format($movementCount) }} inventory movement records</li>
                <li>All inventory quantities will be set to zero</li>
            </ul>
            <h2>Will be preserved</h2>
            <ul>
                <li>All superadmin and cashier accounts</li>
                <li>Menu items, prices, choices, and inventory recipes</li>
                <li>Inventory items and low-stock thresholds</li>
                <li>Receipt settings</li>
            </ul>
        </section>
        @foreach ($errors->all() as $error)<p class="field-error">{{ $error }}</p>@endforeach
        <form method="POST" action="{{ route('superadmin.reset.run') }}" class="reset-confirm-form">
            @csrf
            <label for="reset-confirmation">Type <strong>RESET BUSINESS DATA</strong> to enable the reset</label>
            <input id="reset-confirmation" name="confirmation" autocomplete="off" required pattern="RESET BUSINESS DATA" placeholder="RESET BUSINESS DATA">
            <button class="danger-button" type="submit" id="reset-submit" disabled>Reset business data</button>
        </form>
    </section>
</main>
<script>
    const resetConfirmation = document.querySelector('#reset-confirmation');
    const resetSubmit = document.querySelector('#reset-submit');
    resetConfirmation.addEventListener('input', () => {
        resetSubmit.disabled = resetConfirmation.value !== 'RESET BUSINESS DATA';
    });
</script>
@endsection
