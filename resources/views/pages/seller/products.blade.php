<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'My Products — BoomBuy Seller'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-dashboard.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/portal-dash.css') }}">
</head>
<body>
<div class="layout">
<x-layout.seller-sidebar
    active="products"
    :user="$user"
/>

<!-- MAIN -->
<main class="main-content">
<div class="container">

    <x-seller-page-head title="My Products" subtitle="Every product in your shop, including drafts and archived ones." />

    @if(session('success'))
        <div class="pd-alert is-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="pd-alert is-error"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
    @endif

    <!-- PRODUCTS HEADER -->

    <div class="top" id="my-products">

        <h2>
            {{ count($products) }} live {{ \Illuminate\Support\Str::plural('product', count($products)) }}
        </h2>

        @if(count($products ?? []) + count($archivedProducts ?? []) > 0)
            <div class="product-search">
                <i class="bi bi-search"></i>
                <input type="search" id="productSearch" placeholder="Search your products" aria-label="Search your products" autocomplete="off">
            </div>
        @endif

        <a
            href="{{ route('seller.products.create') }}"
            class="add-btn"
        >
            + Add Product
        </a>

    </div>


    <!-- PRODUCTS -->

    @if(count($products ?? []) > 0)

        {{-- Several products at once: tick them, pick an action --}}
        <form method="POST" action="{{ route('seller.products.bulk') }}" id="bulkProducts" class="bulk-bar" data-confirm="Apply this action to the selected products?" data-confirm-ok="Apply">
            @csrf
            <label for="bulk_action" class="bulk-label">Selected products</label>
            <select id="bulk_action" name="bulk_action" required>
                <option value="">Choose an action</option>
                <option value="deactivate">Bulk Deactivate</option>
                <option value="activate">Bulk Reactivate</option>
                <option value="delete">Bulk Delete</option>
            </select>
            <button type="submit" class="edit-btn">Apply</button>
        </form>

        <div class="products-box">

            <table class="products-table is-live">

                <thead>

                    <tr>

                        <th>
                            Product
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Stock
                        </th>

                        <th>
                            Rating
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($products as $product)

                        <tr data-product-search="{{ strtolower($product->name . ' ' . $product->category . ' #' . $product->id) }}">

                            <!-- PRODUCT -->

                            <td>

                                <div class="product-info">

                                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" form="bulkProducts" class="bulk-check" aria-label="Select {{ $product->name }}">

                                    <div class="product-icon">
                                        @php
                                            $pImg = $product->image ?? null;
                                            $pIsImg = is_string($pImg) && (str_contains($pImg, '.jpg') || str_contains($pImg, '.jpeg') || str_contains($pImg, '.png') || str_contains($pImg, '.webp') || str_contains($pImg, '/'));
                                        @endphp
                                        @if($pIsImg)
                                            <img src="{{ str_starts_with($pImg, 'http') ? $pImg : asset('storage/' . ltrim($pImg, '/')) }}" alt="{{ $product->name }}" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:inherit;" onload="this.style.display='block'; this.nextElementSibling.style.display='none';" onerror="this.style.display='none';">
                                            <span><i class="bi bi-box-seam-fill"></i></span>
                                        @else
                                            <i class="bi bi-box-seam-fill"></i>
                                        @endif
                                    </div>

                                    <div>

                                        <div class="product-name">
                                            {{ $product->name ?? 'Unnamed Product'}}
                                            @if($product->is_draft)
                                                <span class="draft-badge" data-product-status="{{ $product->id }}">Draft</span>
                                            @endif
                                        </div>

                                        <div class="product-slug">
                                            {{ Str::slug($product->name) }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            <!-- CATEGORY -->

                            <td>

                                <span class="category-badge">
                                    {{ $product->category ?? 'Other' }}
                                </span>

                                {{-- The store's own categories (none = Uncategorized) --}}
                                <small class="store-cat-line" data-store-cats="{{ $product->id }}">
                                    {{ $product->storeCategories->isNotEmpty() ? $product->storeCategories->map->fullName()->implode(', ') : 'Uncategorized' }}
                                </small>

                            </td>


                            <!-- PRICE -->

                            <td>

                                <span class="product-price">
                                    ₱{{ number_format($product->price ?? 0, 2) }}
                                </span>

                            </td>


                            <!-- STOCK -->

                            <td>

                                @php
                                    $sellable = (int) ($product->sellable_stock ?? $product->stock);
                                    $stockClass = $sellable <= 0
                                        ? 'out'
                                        : ($sellable <= 5 ? 'low' : '');
                                @endphp

                                <span class="stock-count {{ $stockClass }}">
                                    {{ $sellable > 0 ? $sellable : 'Out of Stock' }}
                                </span>

                                @if($product->variations_count > 0)
                                    <small class="stock-note">across {{ $product->variations_count }} {{ \Illuminate\Support\Str::plural('option', $product->variations_count) }}</small>
                                @endif

                            </td>


                            <!-- RATING -->

                            <td>

                                <span class="rating">
                                    <i class="bi bi-star-fill"></i> {{ number_format($product->reviews_avg_rating ?? 0, 1) }}
                                </span>

                                <span class="reviews">
                                    ({{ $product->reviews_count ?? 0 }})
                                </span>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <div class="actions">

                                    <a
                                        href="{{ route('seller.products.edit', ['id' => $product->id]) }}"
                                        class="edit-btn"
                                    >
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>

                                    <a
                                        href="{{ route('seller.products.variations', ['id' => $product->id]) }}"
                                        class="edit-btn"
                                    >
                                        <i class="bi bi-palette"></i> Variations
                                    </a>

                                    <form action="{{ route('seller.products.duplicate', ['id' => $product->id]) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="edit-btn"><i class="bi bi-copy"></i> Duplicate</button>
                                    </form>

                                    <form
                                        action="{{ route('seller.products.archive', ['id' => $product->id]) }}"
                                        method="POST"
                                        data-confirm="Deactivate this product? Buyers won't see or buy it until you reactivate it." data-confirm-ok="Deactivate"
                                    >

                                        @csrf

                                        <button
                                            type="submit"
                                            class="edit-btn"
                                        >
                                            <i class="bi bi-archive"></i> Deactivate
                                        </button>

                                    </form>

                                    <form
                                        action="{{ route('seller.products.delete', ['id' => $product->id]) }}"
                                        method="POST"
                                        data-confirm="Are you sure you want to delete this product?" data-confirm-ok="Delete" data-confirm-danger
                                    >

                                        @csrf

                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="delete-btn"
                                        >
                                            <i class="bi bi-trash-fill"></i> Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @else

        <div class="empty">

            <div class="empty-icon">
                <i class="bi bi-box-seam-fill"></i>
            </div>

            <h3>
                No Products Yet
            </h3>

            <p style="margin-top:8px;">
                Start selling by adding your first product.
            </p>

        </div>

    @endif


    @if($archivedProducts->count() > 0)

        <div class="top" style="margin-top:35px;">
            <h2>Archived Products</h2>
        </div>

        <div class="products-box">

            <table class="products-table">

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($archivedProducts as $product)

                        <tr style="opacity:0.65;" data-product-search="{{ strtolower($product->name . ' ' . $product->category . ' #' . $product->id) }}">

                            <td>
                                <div class="product-name">
                                    {{ $product->name }}
                                </div>
                            </td>

                            <td>
                                <span class="category-badge">
                                    {{ $product->category ?? 'Other' }}
                                </span>
                            </td>

                            <td>
                                <span class="product-price">
                                    ₱{{ number_format($product->price ?? 0, 2) }}
                                </span>
                            </td>

                            <td>
                                <form
                                    action="{{ route('seller.products.unarchive', ['id' => $product->id]) }}"
                                    method="POST"
                                >
                                    @csrf
                                    <button type="submit" class="edit-btn">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                    </button>
                                </form>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @endif

    <p id="productSearchEmpty" style="display:none; text-align:center; color:var(--theme-muted); font-size:13px; margin:10px 0 24px;">
        No products match your search.
    </p>


</div>

</main>

</div><!-- /.layout -->




    <script>
        // Instant filter over the seller's own product rows (all already on the page).
        (function () {
            var input = document.getElementById('productSearch');
            if (!input) return;

            var rows = Array.prototype.slice.call(document.querySelectorAll('tr[data-product-search]'));
            var empty = document.getElementById('productSearchEmpty');

            input.addEventListener('input', function () {
                var term = input.value.trim().toLowerCase();
                var shown = 0;

                rows.forEach(function (row) {
                    var match = term === '' || row.getAttribute('data-product-search').indexOf(term) !== -1;
                    row.style.display = match ? '' : 'none';
                    if (match) shown++;
                });

                empty.style.display = shown === 0 && term !== '' ? 'block' : 'none';
            });
        })();
    </script>

    @include('partials.pwa-register')

</body>
</html>
