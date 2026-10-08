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
                <article class="inventory-stock-row" data-inventory-id="{{ $item->id }}">
                    <div class="inventory-item-title"><strong>{{ $item->name }}</strong><span data-stock-quantity data-unit="{{ $item->unit }}">{{ number_format((float) $item->quantity, 3) }} {{ $item->unit }} available</span></div>
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
                                <li data-recipe-id="{{ $recipe->id }}" data-inventory-id="{{ $recipe->inventory_item_id }}"><span>{{ $recipe->inventoryItem->name }}: {{ rtrim(rtrim(number_format((float) $recipe->quantity_per_item, 3), '0'), '.') }} {{ $recipe->inventoryItem->unit }}</span>
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
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function showFormStatus(form, message, isError = false) {
        let status = form.querySelector('[data-form-status]');
        if (!status) {
            status = document.createElement('p');
            status.dataset.formStatus = '';
            status.setAttribute('role', isError ? 'alert' : 'status');
            form.append(status);
        }
        status.textContent = message;
        status.classList.toggle('field-error', isError);
        status.classList.toggle('status-message', !isError);
    }

    async function submitInventoryForm(form) {
        const button = form.querySelector('button[type="submit"]');
        const originalText = button?.textContent;
        if (button) {
            button.disabled = true;
            button.textContent = 'Saving...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: new FormData(form),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Unable to save changes.');
            }
            return result;
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    }

    document.querySelectorAll('.stock-adjust-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const result = await submitInventoryForm(form);
                const row = form.closest('.inventory-stock-row');
                const stockQuantity = row.querySelector('[data-stock-quantity]');
                stockQuantity.textContent = `${Number(result.quantity).toFixed(3)} ${stockQuantity.dataset.unit} available`;
                showFormStatus(form, result.message);
            } catch (error) {
                showFormStatus(form, error.message, true);
            }
        });
    });

    document.querySelectorAll('.recipe-add-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const result = await submitInventoryForm(form);
                const recipe = result.recipe;
                const list = form.closest('.recipe-card').querySelector('ul');
                list.querySelector('.recipe-empty')?.remove();
                let row = list.querySelector(`[data-inventory-id="${recipe.inventory_item_id}"]`);
                if (!row) {
                    row = document.createElement('li');
                    row.dataset.recipeId = recipe.id;
                    row.dataset.inventoryId = recipe.inventory_item_id;
                    const text = document.createElement('span');
                    row.append(text);
                    const removeForm = document.createElement('form');
                    removeForm.method = 'POST';
                    removeForm.action = `${@json(url('/superadmin/inventory/recipes'))}/${recipe.id}`;
                    removeForm.innerHTML = `<input type="hidden" name="_token" value="${csrfToken}"><input type="hidden" name="_method" value="DELETE"><button class="text-button remove-button" type="submit">Remove</button>`;
                    removeForm.addEventListener('submit', handleRecipeRemove);
                    row.append(removeForm);
                    list.append(row);
                } else {
                    row.dataset.recipeId = recipe.id;
                }
                row.querySelector('span').textContent = `${recipe.inventory_item_name}: ${Number(recipe.quantity_per_item)} ${recipe.unit}`;
                showFormStatus(form, result.message);
            } catch (error) {
                showFormStatus(form, error.message, true);
            }
        });
    });

    async function handleRecipeRemove(event) {
        event.preventDefault();
        const form = event.currentTarget;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: new FormData(form),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Unable to remove this recipe mapping.');
            }
            form.closest('[data-recipe-id]')?.remove();
        } catch (error) {
            showFormStatus(form, error.message, true);
        }
    }

    document.querySelectorAll('.recipe-card li form').forEach((form) => {
        form.addEventListener('submit', handleRecipeRemove);
    });
</script>
@endsection
