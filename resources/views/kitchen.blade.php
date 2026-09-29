@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('pos') }}">
            <img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo">
            <span>
                <span class="brand-name">ETIVACSILOG</span>
                <span class="brand-subtitle">KITCHEN TICKETS</span>
            </span>
        </a>
        <div class="user-area">
            <a class="header-link" href="{{ route('pos') }}">POS menu</a>
            <a class="header-link" href="{{ route('kitchen.archive') }}">Archive</a>
            @if (auth()->user()->role === 'superadmin')<a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a>@endif
            <div class="welcome-copy"><span>Signed in</span><strong>{{ auth()->user()->username }}</strong></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-button">Log out</button>
            </form>
        </div>
    </header>

    <section class="kitchen-board-page">
        <div class="page-heading kitchen-page-heading">
            <div>
                <p class="eyebrow">ORDER HANDOFF</p>
                <h1>Kitchen board</h1>
            </div>
            <a class="secondary-button link-button" href="{{ route('pos') }}">Back to POS</a>
        </div>

        @if (session('status'))
            <p class="status-message" role="status">{{ session('status') }}</p>
        @endif

        @php($columns = ['pending' => 'Pending', 'cooking' => 'Cooking', 'served' => 'Served'])
        <div class="kitchen-board">
            @foreach ($columns as $status => $label)
                <section class="kitchen-column column-{{ $status }}" aria-labelledby="column-{{ $status }}">
                    <header class="column-heading">
                        <h2 id="column-{{ $status }}">{{ $label }}</h2>
                        <span class="column-count">{{ ($ordersByStatus[$status] ?? collect())->count() }}</span>
                    </header>
                    <div class="column-tickets">
                        @forelse ($ordersByStatus[$status] ?? [] as $order)
                            <article class="kitchen-ticket">
                                <header class="ticket-heading">
                                    <div>
                                        <p class="ticket-number">ORDER #{{ $order->id }}</p>
                                        <h3>{{ $order->created_at->format('g:i A') }}</h3>
                                    </div>
                                    <form method="POST" action="{{ route('kitchen.status', $order) }}" class="ticket-status-form">
                                        @csrf
                                        @method('PATCH')
                                        <label class="visually-hidden" for="status-{{ $order->id }}">Move order {{ $order->id }}</label>
                                        <select id="status-{{ $order->id }}" name="status" class="ticket-move-select" onchange="this.form.requestSubmit()">
                                            @foreach ($columns as $value => $optionLabel)
                                                <option value="{{ $value }}" @selected($order->status === $value)>Move to {{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </header>
                                <ul class="ticket-items">
                                    @foreach ($order->items as $item)
                                        <li><strong>{{ $item->quantity }} ×</strong> {{ $item->name }}</li>
                                    @endforeach
                                </ul>
                                @if (filled($order->kitchen_note))
                                    <div class="ticket-note">
                                        <span>Kitchen note</span>
                                        <p>{{ $order->kitchen_note }}</p>
                                    </div>
                                @endif
                                <p class="ticket-cashier">By {{ $order->user->username }}</p>
                                <details class="void-order-controls">
                                    <summary>Void order</summary>
                                    <form method="POST" action="{{ route('orders.void', $order) }}" onsubmit="return confirm('Void order #{{ $order->id }}? Sales will be reversed and used stock restored.')">
                                        @csrf
                                        <label for="void-reason-{{ $order->id }}">Reason for voiding</label>
                                        <textarea id="void-reason-{{ $order->id }}" name="void_reason" maxlength="500" minlength="3" rows="2" required placeholder="Required for the audit record"></textarea>
                                        <button class="danger-button" type="submit">Confirm void</button>
                                    </form>
                                </details>
                            </article>
                        @empty
                            <p class="column-empty">No {{ strtolower($label) }} orders</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </section>
</main>
@endsection
