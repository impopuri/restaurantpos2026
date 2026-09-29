@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('superadmin.dashboard') }}"><img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo"><span><span class="brand-name">ETIVACSILOG</span><span class="brand-subtitle">ACCOUNT MANAGEMENT</span></span></a>
        <div class="user-area"><a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a><a class="header-link" href="{{ route('superadmin.inventory') }}">Inventory</a><a class="header-link" href="{{ route('superadmin.receipt-settings') }}">Receipt</a><div class="welcome-copy"><span>Administrator</span><strong>{{ auth()->user()->username }}</strong></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Log out</button></form></div>
    </header>
    <section class="page-content admin-page">
        <div class="page-heading"><div><p class="eyebrow">ACCESS CONTROL</p><h1>Accounts</h1></div><a class="secondary-button link-button" href="{{ route('superadmin.dashboard') }}">Back to admin</a></div>
        @if (session('status'))<p class="status-message">{{ session('status') }}</p>@endif
        @foreach ($errors->all() as $error)<p class="field-error">{{ $error }}</p>@endforeach
        <section class="admin-form-panel">
            <h2>Create account</h2>
            <form method="POST" action="{{ route('superadmin.accounts.store') }}" class="account-create-form">
                @csrf
                <label>Full name<input name="name" required maxlength="255"></label>
                <label>Username<input name="username" required maxlength="255" pattern="[A-Za-z0-9_-]+"></label>
                <label>Role<select name="role"><option value="cashier">Cashier</option><option value="superadmin">Superadmin</option></select></label>
                <label>Password<input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
                <label>Confirm password<input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></label>
                <button class="primary-button" type="submit">Create account</button>
            </form>
        </section>
        <div class="account-list">
            @foreach ($users as $user)
                <section class="account-row">
                    <form method="POST" action="{{ route('superadmin.accounts.update', $user) }}" class="account-edit-form">
                        @csrf @method('PUT')
                        <label>Name<input name="name" value="{{ $user->name }}" required></label>
                        <label>Username<input name="username" value="{{ $user->username }}" required></label>
                        <label>Role<select name="role"><option value="cashier" @selected($user->role === 'cashier')>Cashier</option><option value="superadmin" @selected($user->role === 'superadmin')>Superadmin</option></select></label>
                        <label>New password <span>(leave blank to keep)</span><input name="password" type="password" minlength="8" autocomplete="new-password"></label>
                        <label>Confirm new password<input name="password_confirmation" type="password" minlength="8" autocomplete="new-password"></label>
                        <div class="account-actions"><button class="secondary-button" type="submit">Save changes</button></div>
                    </form>
                    <form method="POST" action="{{ route('superadmin.accounts.destroy', $user) }}" onsubmit="return confirm('Remove this account?')">@csrf @method('DELETE')<button class="text-button remove-button" type="submit" @disabled(auth()->id() === $user->id)>Remove account</button></form>
                </section>
            @endforeach
        </div>
    </section>
</main>
@endsection
