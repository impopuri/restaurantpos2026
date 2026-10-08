@extends('layouts.app')

@section('content')
<main class="pos-shell">
    <header class="pos-header">
        <a class="header-brand header-brand-link" href="{{ route('superadmin.dashboard') }}">
            <img class="pos-logo" src="{{ asset('images/ES LOGO.jpg') }}" alt="EtivacSilog Logo">
            <span><span class="brand-name">ETIVACSILOG</span><span class="brand-subtitle">MENU MANAGEMENT</span></span>
        </a>
        <div class="user-area">
            <a class="header-link" href="{{ route('superadmin.dashboard') }}">Admin</a>
            <a class="header-link" href="{{ route('dashboard') }}">Dashboard</a>
            <div class="welcome-copy"><span>Signed in</span><strong>{{ auth()->user()->username }}</strong></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit">Log out</button></form>
        </div>
    </header>

    <section class="page-content menu-admin-page">
        <div class="page-heading">
            <div><p class="eyebrow">CATALOG</p><h1>Manage menu</h1></div>
            <a class="secondary-button link-button" href="{{ route('superadmin.dashboard') }}">Back to admin</a>
        </div>
        @if (session('status'))<p class="status-message" role="status">{{ session('status') }}</p>@endif

        <section class="menu-create-panel">
            <h2>Add menu item</h2>
                <form method="POST" action="{{ route('superadmin.menu.store') }}" class="menu-item-form">
                @csrf
                <label>Category<select name="category" required>@foreach ($categories as $category)<option value="{{ $category }}">{{ ucfirst(strtolower($category)) }}</option>@endforeach</select></label>
                <label>Item name<input name="name" maxlength="120" required value="{{ old('name') }}"></label>
                <label>Price (₱)<input name="price" type="number" min="0" max="999999.99" step="0.01" required value="{{ old('price') }}"></label>
                <label>Choices <span>(optional, comma-separated)</span><input name="options" maxlength="500" placeholder="Plain, Cheese, Sour Cream, BBQ"></label>
                <button class="primary-button" type="submit">Add item</button>
            </form>
            @foreach ($errors->all() as $error)<p class="field-error">{{ $error }}</p>@endforeach
        </section>

        @foreach ($categories as $category)
            <section class="menu-category-section">
                <h2>{{ ucfirst(strtolower($category)) }} <span>{{ $items->where('category', $category)->count() }}</span></h2>
                <div class="menu-admin-list">
                    @forelse ($items->where('category', $category) as $item)
                        <form method="POST" action="{{ route('superadmin.menu.update', $item) }}" class="menu-admin-row">
                            @csrf
                            @method('PUT')
                            <input aria-label="Item name" name="name" maxlength="120" value="{{ $item->name }}" required>
                            <select aria-label="Category" name="category" required>@foreach ($categories as $option)<option value="{{ $option }}" @selected($item->category === $option)>{{ ucfirst(strtolower($option)) }}</option>@endforeach</select>
                            <label class="menu-price-input"><span>₱</span><input aria-label="Price" name="price" type="number" min="0" max="999999.99" step="0.01" value="{{ $item->price }}" required></label>
                            <input aria-label="Choices separated by commas" name="options" maxlength="500" value="{{ implode(', ', $item->options ?? []) }}" placeholder="No choices">
                            <button class="secondary-button" type="submit">Save</button>
                            <button class="text-button remove-button" type="submit" form="remove-{{ $item->id }}">Remove</button>
                        </form>
                        <form id="remove-{{ $item->id }}" method="POST" action="{{ route('superadmin.menu.destroy', $item) }}" hidden>@csrf @method('DELETE')</form>
                    @empty
                        <p class="menu-empty">No items in this category.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </section>
</main>
<script>
    document.querySelectorAll('.menu-admin-row').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            let status = form.querySelector('[data-form-status]');
            if (!status) {
                status = document.createElement('span');
                status.dataset.formStatus = '';
                status.setAttribute('role', 'status');
                form.append(status);
            }
            status.textContent = '';
            button.disabled = true;
            button.textContent = 'Saving...';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: new FormData(form),
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Unable to save menu item.');
                }
                status.textContent = 'Saved. Updates will be used in the menu and cart.';
            } catch (error) {
                status.textContent = error.message;
                status.setAttribute('role', 'alert');
                status.classList.add('field-error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });
</script>
@endsection
