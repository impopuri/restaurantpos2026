@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('superadmin.dashboard') }}"><img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo"><span><span class="brand-name">ETIVACSILOG</span><span class="brand-subtitle">INVENTORY</span></span></a>
        <div class="user-area"><a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a><a class="header-link" href="{{ route('superadmin.menu.index') }}">Manage menu</a><div class="welcome-copy"><span>Administrator</span><strong>{{ auth()->user()->username }}</strong></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Log out</button></form></div>
    </header>
    <section class="page-content admin-page">
        <div class="page-heading"><div><p class="eyebrow">STOCK CONTROL</p><h1>Inventory &amp; recipes</h1></div><a class="secondary-button link-button" href="{{ route('superadmin.dashboard') }}">Back to admin</a></div>
        @if (session('status'))<p class="status-message">{{ session('status') }}</p>@endif
        @foreach ($errors->all() as $error)<p class="field-error">{{ $error }}</p>@endforeach

        <section class="admin-form-panel">
            <h2>Add inventory item</h2>
            <form method="POST" action="{{ route('superadmin.inventory.store') }}" class="inventory-create-form">
                @csrf
                <label>Ingredient name<input name="name" required maxlength="120"></label>
                <label>Unit<input name="unit" value="pcs" required maxlength="30" placeholder="pcs, slices, servings"></label>
                <label>Low-stock threshold<input name="low_stock_threshold" type="number" step="0.001" min="0" value="0" required></label>
                <button class="primary-button" type="submit">Add ingredient</button>
            </form>
        </section>

        <section class="inventory-stock-list">
            <h2>Stock on hand</h2>
            @foreach ($inventoryItems as $item)
                <article class="inventory-stock-row">
                    <div class="inventory-item-title"><strong>{{ $item->name }}</strong><span>{{ number_format((float) $item->quantity, 3) }} {{ $item->unit }} available</span></div>
                    <form method="POST" action="{{ route('superadmin.inventory.adjust', $item) }}" class="stock-adjust-form">
                        @csrf
                        <label>Amount<input name="adjustment" type="number" min="0.001" step="0.001" required></label>
                        <label>Action<select name="direction"><option value="add">Receive stock</option><option value="remove">Stock correction out</option></select></label>
                        <label>Note<input name="note" maxlength="120" placeholder="Delivery / count correction"></label>
                        <label>Warn at or below<input name="low_stock_threshold" type="number" min="0" step="0.001" value="{{ $item->low_stock_threshold }}" required></label>
                        <button class="secondary-button" type="submit">Apply</button>
                    </form>
                    @if ($item->recipes->isNotEmpty())
                        <p class="ingredient-usage">Used by: {{ $item->recipes->map(fn ($recipe) => $recipe->menuItem->name.' × '.rtrim(rtrim(number_format((float) $recipe->quantity_per_item, 3), '0'), '.'))->join(', ') }}</p>
                    @endif
                </article>
            @endforeach
        </section>

        <section class="recipe-section">
            <div class="page-heading"><div><p class="eyebrow">AUTOMATIC DEDUCTION</p><h2>Recipe usage per menu item</h2></div></div>
            <p class="recipe-explainer">Each paid order deducts the configured quantity for every menu unit. Meals use their mapped ingredient plus one egg; snacks and drinks use matching stock items. Only the Egg extra is tracked.</p>
            <div class="recipe-grid">
                @foreach ($trackableMenuItems as $menuItem)
                    <article class="recipe-card">
                        <header><strong>{{ $menuItem->name }}</strong><span>{{ ucfirst(strtolower($menuItem->category)) }}</span></header>
                        <ul>
                            @forelse ($recipes->where('menu_item_id', $menuItem->id) as $recipe)
                                <li>{{ $recipe->inventoryItem->name }}: {{ rtrim(rtrim(number_format((float) $recipe->quantity_per_item, 3), '0'), '.') }} {{ $recipe->inventoryItem->unit }}
                                    <form method="POST" action="{{ route('superadmin.inventory.recipes.destroy', $recipe) }}">@csrf @method('DELETE')<button class="text-button remove-button" type="submit">Remove</button></form>
                                </li>
                            @empty
                                <li class="recipe-empty">No inventory deduction mapping.</li>
                            @endforelse
                        </ul>
                        <form method="POST" action="{{ route('superadmin.inventory.recipes.store') }}" class="recipe-add-form">
                            @csrf
                            <input type="hidden" name="menu_item_id" value="{{ $menuItem->id }}">
                            <select name="inventory_item_id" required aria-label="Ingredient for {{ $menuItem->name }}">
                                @foreach ($inventoryItems as $ingredient)<option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>@endforeach
                            </select>
                            <input name="quantity_per_item" type="number" min="0.001" step="0.001" value="1" required aria-label="Amount per {{ $menuItem->name }}">
                            <button class="secondary-button" type="submit">Save usage</button>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>
    </section>
</main>
@endsection
