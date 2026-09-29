@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <div class="header-brand">
            <img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo">
            <div>
                <p class="brand-name">ETIVACSILOG</p>
                <p class="brand-subtitle">POS SYSTEM</p>
            </div>
        </div>
        <div class="user-area">
            <div class="welcome-copy">
                <span>Currently signed in as</span>
                <strong>Welcome, {{ auth()->user()->username }}!</strong>
            </div>
            <a class="header-link" href="{{ route('kitchen') }}">Kitchen</a>
            @if (auth()->user()->role === 'superadmin')
                <a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a>
            @endif
            <a class="cart-link" href="{{ route('cart') }}" aria-label="Cart, {{ $cartCount }} items">
                Cart <span class="cart-count">{{ $cartCount }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-button">Log out <span aria-hidden="true">&#8594;</span></button>
            </form>
        </div>
    </header>

    @if ($lowStockItems->isNotEmpty())
        <section class="cashier-stock-alert" role="alert" aria-label="Low inventory warning">
            <div class="stock-alert-heading"><strong>Low stock warning</strong><span>{{ $lowStockItems->count() }} item{{ $lowStockItems->count() === 1 ? '' : 's' }}</span></div>
            <ul>
                @foreach ($lowStockItems as $stockItem)
                    <li><strong>{{ $stockItem->name }}</strong><span>{{ number_format((float) $stockItem->quantity, 3) }} {{ $stockItem->unit }} left</span>@if ((float) $stockItem->quantity <= 0)<em>Out of stock</em>@endif</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="pos-layout" aria-labelledby="category-title">
        <aside class="category-sidebar">
            <p class="sidebar-label">CATEGORIES</p>
            <nav class="category-list" aria-label="Product categories">
                @foreach ($categories as $category => $products)
                    <button type="button" class="category-button {{ $loop->first ? 'active' : '' }}" data-category="{{ $category }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        {{ ucfirst(strtolower($category)) }}
                    </button>
                @endforeach
            </nav>
        </aside>

        <div class="pos-content">
            <div class="workspace-heading">
                <p class="eyebrow">ORDER STATION</p>
                <h2 id="category-title">Welcome, {{ auth()->user()->username }}!</h2>
                <p class="selected-category">MEALS MENU</p>
            </div>
            <div class="product-grid" id="product-grid">
                @foreach ($categories['MEALS'] as $product)
                    <button type="button" class="product-card" data-product-key="{{ $product['key'] }}">
                        <span class="product-name">{{ $product['name'] }}
                            @if (!empty($product['options']))<small class="product-choice-hint">{{ implode(' / ', $product['options']) }}</small>@endif
                        </span>
                        <span class="product-price">&#8369;{{ $product['price'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </section>
</main>

<dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-title">
    <form method="POST" action="{{ route('cart.add') }}" class="confirm-card">
        @csrf
        <p class="eyebrow">ADD TO CART</p>
        <h2 id="confirm-title">Add this item?</h2>
        <p class="confirm-product" id="confirm-product"></p>
        <input type="hidden" name="product_key" id="confirm-product-key">
        <div class="product-choice-field" id="product-choice-field" hidden>
            <label for="product-choice">Choose option</label>
            <select name="choice" id="product-choice"></select>
        </div>
        <div class="dialog-actions">
            <button class="secondary-button" type="button" id="cancel-add">Cancel</button>
            <button class="primary-button" type="submit">Add to cart</button>
        </div>
    </form>
</dialog>

<script>
    const categories = @json($categories);
    const productGrid = document.querySelector('#product-grid');
    const selectedCategory = document.querySelector('.selected-category');
    const confirmDialog = document.querySelector('#confirm-dialog');
    const confirmProduct = document.querySelector('#confirm-product');
    const confirmProductKey = document.querySelector('#confirm-product-key');
        const choiceField = document.querySelector('#product-choice-field');
        const choiceSelect = document.querySelector('#product-choice');

        function escapeHtml(value) {
            const element = document.createElement('span');
            element.textContent = value;
            return element.innerHTML;
        }

    productGrid.addEventListener('click', (event) => {
        const productCard = event.target.closest('.product-card');
        if (!productCard) return;

        const product = Object.values(categories).flat().find((item) => item.key === productCard.dataset.productKey);
        if (!product) return;

        confirmProduct.textContent = `${product.name} - ₱${product.price}`;
        confirmProductKey.value = product.key;
        const options = product.options || [];
        choiceField.hidden = options.length === 0;
        choiceSelect.required = options.length > 0;
        choiceSelect.innerHTML = options.map((option) => `<option value="${escapeHtml(option)}">${escapeHtml(option)}</option>`).join('');
        confirmDialog.showModal();
    });

    document.querySelector('#cancel-add').addEventListener('click', () => confirmDialog.close());

    document.querySelectorAll('.category-button').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.category-button').forEach((item) => {
                item.classList.remove('active');
                item.setAttribute('aria-selected', 'false');
            });
            button.classList.add('active');
            button.setAttribute('aria-selected', 'true');

            const category = button.dataset.category;
            selectedCategory.textContent = `${category} MENU`;
            productGrid.innerHTML = categories[category].map((product) => `
                <button type="button" class="product-card" data-product-key="${product.key}">
                    <span class="product-name">${escapeHtml(product.name)}${product.options?.length ? `<small class="product-choice-hint">${product.options.map(escapeHtml).join(' / ')}</small>` : ''}</span>
                    <span class="product-price">&#8369;${product.price}</span>
                </button>
            `).join('');
        });
    });
</script>
@endsection