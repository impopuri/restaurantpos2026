@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('superadmin.dashboard') }}"><img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo"><span><span class="brand-name">ETIVACSILOG</span><span class="brand-subtitle">VOID AUDIT</span></span></a>
        <div class="user-area"><a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a><a class="header-link" href="{{ route('kitchen') }}">Kitchen</a><div class="welcome-copy"><span>Administrator</span><strong>{{ auth()->user()->username }}</strong></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Log out</button></form></div>
    </header>
    <section class="page-content admin-page">
        <div class="page-heading"><div><p class="eyebrow">ORDER AUDIT</p><h1>Voided orders</h1></div><a class="secondary-button link-button" href="{{ route('superadmin.dashboard') }}">Back to admin</a></div>
        @forelse ($orders as $order)
            <article class="void-audit-card">
                <header><strong>Order #{{ $order->id }}</strong><span>Voided {{ optional($order->voided_at)->format('M j, Y g:i A') }}</span></header>
                <p>Originally paid: ₱{{ number_format((float) $order->total, 2) }} via {{ $order->payment_method === 'others' ? $order->payment_other : strtoupper((string) $order->payment_method) }}</p>
                <p>Voided by <strong>{{ $order->voidedBy?->username ?? 'Deleted account' }}</strong></p>
                <blockquote>{{ $order->void_reason }}</blockquote>
                <ul>@foreach ($order->items as $item)<li>{{ $item->quantity }} × {{ $item->name }}</li>@endforeach</ul>
                @php($restored = $order->inventoryMovements->where('movement_type', 'void_reversal'))
                @if ($restored->isNotEmpty())<p class="restored-stock-line">Inventory restored: {{ $restored->map(fn ($movement) => $movement->inventoryItem->name.' +'.rtrim(rtrim(number_format((float) $movement->quantity_change, 3), '0'), '.').' '.$movement->inventoryItem->unit)->join(', ') }}</p>@endif
            </article>
        @empty
            <div class="empty-cart"><h2>No voided orders</h2><p>Orders voided from the kitchen board will be recorded here.</p></div>
        @endforelse
        {{ $orders->links() }}
    </section>
</main>
@endsection