<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Store Categories — BoomBuy Seller'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-vouchers.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-store-categories.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="store-categories" :user="$user" />

    <main class="main-content">

        <div class="container">

            <x-seller-page-head title="Store Categories" subtitle="Group your products on your store page, e.g. Bags or Home Decor › Lamps. Platform categories don't change." />

            @if(session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="error-box">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="card">
                <h2>Add Category</h2>

                <form method="POST" action="{{ route('seller.store-categories.store') }}">
                    @csrf

                    <div class="form-row scat-form-row">
                        <div class="form-group">
                            <label for="name">Category Name</label>
                            <input type="text" id="name" name="name" maxlength="{{ \App\Models\StoreCategory::NAME_MAX }}" required value="{{ old('name') }}" placeholder="e.g. Pasalubong Picks">
                            @error('name')<span class="field-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="parent_id">Inside <span class="scat-hint">(optional)</span></label>
                            <select id="parent_id" name="parent_id">
                                <option value="">None — a main category</option>
                                {{-- Only main categories: store categories have at most 2 levels. --}}
                                @foreach($parents as $parent)
                                    <option value="{{ $parent->id }}" @selected((int) old('parent_id') === $parent->id)>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                            @error('parent_id')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <button type="submit" class="save-btn">Add Category</button>
                </form>
            </div>

            <div class="card">
                <h2>My Categories</h2>
                <p class="scat-note">
                    Buyers see these on your store page in this order. A product can be in up to {{ \App\Models\StoreCategory::MAX_PER_PRODUCT }} categories — choose them when you add or edit the product.
                    <strong>{{ $uncategorized }}</strong> {{ \Illuminate\Support\Str::plural('product', $uncategorized) }} {{ $uncategorized === 1 ? 'is' : 'are' }} Uncategorized.
                </p>

                @forelse($categories as $category)
                    @php
                        $info = $affected[$category->id];
                        $warning = 'Delete "' . $category->name . '"?';
                        if ($info['subcategories']) {
                            $warning .= ' Its subcategories (' . implode(', ', $info['subcategories']) . ') will be deleted too.';
                        }
                        $warning .= ' ' . $info['products'] . ' ' . \Illuminate\Support\Str::plural('product', $info['products'])
                            . ' will move to Uncategorized. No product is deleted.';
                    @endphp
                    <div @class(['scat-row', 'is-sub' => $category->parent_id, 'is-hidden' => $category->is_hidden])>
                        <form method="POST" action="{{ route('seller.store-categories.update', $category->id) }}" class="scat-rename">
                            @csrf
                            @method('PUT')
                            @if($category->parent_id)<i class="bi bi-arrow-return-right scat-sub-icon" aria-hidden="true"></i>@endif
                            <input type="text" name="name" value="{{ $category->name }}" maxlength="{{ \App\Models\StoreCategory::NAME_MAX }}" aria-label="Name of {{ $category->name }}" required>
                            <button type="submit" class="mini-btn toggle">Rename</button>
                        </form>

                        <span class="scat-count">{{ $category->products_count }} {{ \Illuminate\Support\Str::plural('product', $category->products_count) }}</span>

                        @if($category->is_hidden)
                            <span class="status-badge status-inactive">Hidden</span>
                        @endif

                        <div class="scat-actions">
                            @foreach(['top' => 'bi-chevron-double-up', 'up' => 'bi-chevron-up', 'down' => 'bi-chevron-down'] as $direction => $icon)
                                <form method="POST" action="{{ route('seller.store-categories.move', $category->id) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                    <button type="submit" class="mini-btn" aria-label="Move {{ $category->name }} {{ $direction === 'top' ? 'to the top' : $direction }}" title="Move {{ $direction === 'top' ? 'to the top' : $direction }}"><i class="bi {{ $icon }}"></i></button>
                                </form>
                            @endforeach

                            <form method="POST" action="{{ route('seller.store-categories.toggle', $category->id) }}">
                                @csrf
                                <button type="submit" class="mini-btn toggle">{{ $category->is_hidden ? 'Show' : 'Hide' }}</button>
                            </form>

                            <form method="POST" action="{{ route('seller.store-categories.delete', $category->id) }}" data-confirm="{{ $warning }}" data-confirm-ok="Delete" data-confirm-danger>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mini-btn delete">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="empty-message">No store categories yet. Add one above.</p>
                @endforelse
            </div>

        </div>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>
