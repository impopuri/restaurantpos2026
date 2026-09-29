@extends('layouts.app')

@section('content')
<main class="auth-shell">
    <section class="login-card" aria-labelledby="login-title">
        <img class="login-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo">
        <p class="brand-name">ETIVACSILOG</p>
        <p class="brand-subtitle">POS SYSTEM</p>
        <h1 id="login-title">Cashier login</h1>

        <form method="POST" action="{{ route('login.store') }}" class="login-form">
            @csrf
            <div class="field-group">
                <label for="identifier">Username or email</label>
                <input id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" autocomplete="username" autofocus required>
                @error('identifier')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="primary-button">LOGIN</button>
        </form>
    </section>
</main>
@endsection