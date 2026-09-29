@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('kitchen') }}"><img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo"><span><span class="brand-name">ETIVACSILOG</span><span class="brand-subtitle">KITCHEN ARCHIVE</span></span></a>
        <div class="user-area"><a class="header-link" href="{{ route('kitchen') }}">Kitchen board</a>@if (auth()->user()->role === 'superadmin')<a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a>@endif<div class="welcome-copy"><span>Signed in</span><strong>{{ auth()->user()->username }}</strong></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Log out</button></form></div>
    </header>
    <section class="page-content">
        <div class="page-heading"><div><p class="eyebrow">COMPLETED ORDERS</p><h1>Kitchen archive</h1></div><a class="secondary-button link-button" href="{{ route('kitchen') }}">Back to kitchen</a></div>
        @forelse ($orders as $order)
            <article class="archived-order">
                <header><strong>Order #{{ $order->id }}</strong><span>Served {{ optional($order->served_at)->format('M j, Y g:i A') }}</span><span>Archived {{ optional($order->archived_at)->format('M j, Y g:i A') }}</span></header>
                <ul>@foreach ($order->items as $item)<li>{{ $item->quantity }} × {{ $item->name }}</li>@endforeach</ul>
                @if (filled($order->kitchen_note))<p><strong>Kitchen note:</strong> {{ $order->kitchen_note }}</p>@endif
            </article>
        @empty
            <div class="empty-cart"><h2>No archived tickets</h2><p>Served orders are archived after the day ends.</p></div>
        @endforelse
    </section>
</main>
@endsection
