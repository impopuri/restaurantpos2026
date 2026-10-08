@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('pos') }}">
            <img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo">
            <span><span class="brand-name">ETIVACSILOG</span><span class="brand-subtitle">SALES DASHBOARD</span></span>
        </a>
        <div class="user-area">
            <a class="header-link" href="{{ route('pos') }}">POS menu</a>
            @if (auth()->user()->role === 'superadmin')
                <a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a>
            @endif
            <div class="welcome-copy"><span>Signed in</span><strong>{{ auth()->user()->username }}</strong></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Log out</button></form>
        </div>
    </header>

    <section class="page-content dashboard-page">
        <div class="page-heading dashboard-heading">
            <div><p class="eyebrow">BUSINESS OVERVIEW</p><h1>Sales &amp; top sellers</h1></div>
            <form method="GET" action="{{ route('dashboard') }}" class="period-filter">
                <label for="period">Report period</label>
                <select id="period" name="period" onchange="this.form.submit()">
                    @foreach (['today' => 'Today', '7days' => 'Last 7 days', 'month' => 'This month', 'all' => 'All time'] as $value => $label)
                        <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            <a class="secondary-button dashboard-export-button" href="{{ route('dashboard.export', ['period' => $period]) }}" download>Export CSV report</a>
        </div>

        <p class="dashboard-period-label">{{ $periodLabel }} sales</p>
        <section class="sales-summary" aria-label="Sales summary">
            <article class="sales-metric metric-primary"><span>Net sales</span><strong>₱{{ number_format((float) $summary->sales_total, 2) }}</strong><small>{{ $periodLabel }}</small></article>
            <article class="sales-metric"><span>Paid orders</span><strong>{{ number_format((int) $summary->order_count) }}</strong><small>Completed checkouts</small></article>
            <article class="sales-metric"><span>Discounts given</span><strong>₱{{ number_format((float) $summary->discount_total, 2) }}</strong><small>Across paid orders</small></article>
        </section>

        <section class="sales-chart-section" aria-labelledby="sales-chart-title">
            <div class="section-title-row">
                <div><p class="eyebrow">SALES TREND</p><h2 id="sales-chart-title">{{ $periodLabel }} net sales</h2></div>
                <span class="chart-unit">Amount (₱)</span>
            </div>
            <div class="sales-chart-wrap">
                <svg class="sales-chart" viewBox="0 0 1000 320" role="img" aria-labelledby="sales-chart-title sales-chart-description" data-sales-chart>
                    <desc id="sales-chart-description">Net sales by {{ $period === 'today' ? 'hour' : ($period === 'all' ? 'month' : 'day') }} for {{ strtolower($periodLabel) }}.</desc>
                    @foreach ([0, 1, 2, 3, 4] as $gridLine)
                        @php($gridY = 24 + ($gridLine * 232 / 4))
                        <line class="chart-grid-line" x1="38" y1="{{ $gridY }}" x2="962" y2="{{ $gridY }}" />
                        <text class="chart-y-label" x="31" y="{{ $gridY - 5 }}" text-anchor="end">{{ number_format($salesChart['max'] * (4 - $gridLine) / 4, 0) }}</text>
                    @endforeach
                    <polyline class="chart-line" points="{{ $salesChart['polyline'] }}" />
                    @foreach ($salesChart['points'] as $point)
                        <circle class="chart-point" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4">
                            <title>{{ $point['label'] }}: ₱{{ number_format($point['sales'], 2) }}</title>
                        </circle>
                        @if ($point['show_label'])
                            <text class="chart-x-label" x="{{ $point['x'] }}" y="300" text-anchor="middle">{{ $point['label'] }}</text>
                        @endif
                    @endforeach
                </svg>
            </div>
        </section>

        <section class="top-sellers-section">
            <div class="section-title-row"><div><p class="eyebrow">MENU PERFORMANCE</p><h2>Top seller by category</h2></div><span class="period-chip">{{ $periodLabel }}</span></div>
            <div class="top-sellers-grid">
                @foreach ($topSellers as $category => $product)
                    <article class="top-seller-card">
                        <div class="top-seller-heading"><span>{{ ucfirst(strtolower($category)) }}</span><span class="top-seller-rank">TOP</span></div>
                        @if ($product)
                            <h3>{{ $product->name }}</h3>
                            <div class="seller-stats"><strong>{{ number_format((int) $product->units_sold) }} <small>sold</small></strong><span>₱{{ number_format((float) $product->item_sales, 2) }} sales</span></div>
                        @else
                            <p class="no-sales">No paid sales in this period.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    </section>
</main>
@endsection
