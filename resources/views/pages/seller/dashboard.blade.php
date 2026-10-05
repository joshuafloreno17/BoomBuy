
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Seller Dashboard — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-dashboard.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/portal-dash.css') }}">
</head>

<body>

<div class="layout">

<x-layout.seller-sidebar
    active="dashboard"
    :user="$user"
/>


<!-- MAIN -->

<main class="main-content">

<div class="pd">

    @include('partials.announcement-banner')

    @php
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $weekMax = max(1, (float) $salesTrend->max('revenue'));
        $peso = fn ($v) => '₱' . number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2);
        $shortPeso = fn ($v) => $v >= 1000 ? '₱' . rtrim(rtrim(number_format($v / 1000, 1), '0'), '.') . 'k' : '₱' . number_format($v);
        $deliveredCount = (int) ($deliveredToday->orders ?? 0);
        $ratingCount = (int) ($rating->total ?? 0);
    @endphp

    <header class="pd-head">
        <div class="pd-head-text">
            <p class="pd-eyebrow">{{ now()->format('l, F j') }}</p>
            <h1 class="pd-title">{{ $greeting }}, {{ $shopName }}</h1>
        </div>
        @include('partials.notification-popover', [
            'notes' => $recentNotes,
            'unread' => $unreadNotes,
            'allUrl' => route('seller.notifications'),
        ])
        <a href="{{ route('seller.products.create') }}" class="pd-btn pd-btn-primary"><i class="bi bi-plus-lg"></i> Add product</a>
    </header>

    @if(session('success'))
        <div class="pd-alert is-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="pd-alert is-error"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
    @endif

    <section class="pd-kpis" aria-label="Your shop at a glance">
        <div class="pd-kpi is-accent">
            <span class="pd-kpi-label">Sales today</span>
            <span class="pd-kpi-value">{{ $peso($deliveredToday->revenue ?? 0) }}</span>
            <span class="pd-kpi-note">{{ $deliveredCount }} {{ $deliveredCount === 1 ? 'order' : 'orders' }} delivered</span>
        </div>
        <a href="{{ route('seller.orders', ['tab' => 'to-process']) }}" class="pd-kpi">
            <span class="pd-kpi-label">To ship</span>
            <span class="pd-kpi-value">{{ $pendingOrders }}</span>
            <span class="pd-kpi-note">{{ $toShipOld > 0 ? $toShipOld . ' waiting since yesterday' : 'All caught up' }}</span>
        </a>
        <a href="#my-products" class="pd-kpi">
            <span class="pd-kpi-label">Products live</span>
            <span class="pd-kpi-value">{{ $totalProducts }}</span>
            <span class="pd-kpi-note">{{ $lowStockCount > 0 ? $lowStockCount . ' low on stock' : 'Stock looks good' }}</span>
        </a>
        <a href="{{ route('seller.reviews') }}" class="pd-kpi">
            <span class="pd-kpi-label">Shop rating</span>
            <span class="pd-kpi-value">{{ $ratingCount > 0 ? number_format((float) $rating->average, 1) : '—' }}</span>
            <span class="pd-kpi-note">{{ $ratingCount > 0 ? 'from ' . $ratingCount . ' ' . ($ratingCount === 1 ? 'review' : 'reviews') : 'No reviews yet' }}</span>
        </a>
    </section>

    <div class="pd-row">
        <section class="pd-card pd-grow-2" aria-labelledby="week-title">
            <div class="pd-card-head">
                <h2 class="pd-card-title" id="week-title">Sales this week</h2>
                <a href="{{ route('seller.reports') }}" class="pd-link">Full report →</a>
            </div>
            <div class="pd-bars {{ $salesTrend->sum('revenue') > 0 ? '' : 'pd-bars-empty' }}" data-empty="No delivered sales this week yet" role="img" aria-label="Delivered sales for the last 7 days">
                @foreach($salesTrend as $day)
                    <div class="pd-bar {{ $loop->last ? 'is-today' : '' }}">
                        <span class="pd-bar-amount">{{ (float) $day->revenue > 0 ? $shortPeso((float) $day->revenue) : '' }}</span>
                        <span class="pd-bar-fill" style="height: {{ max(2, round((float) $day->revenue / $weekMax * 80)) }}%"></span>
                    </div>
                @endforeach
            </div>
            <div class="pd-bar-days">
                @foreach($salesTrend as $day)
                    <span class="{{ $loop->last ? 'is-today' : '' }}">{{ $loop->last ? 'Today' : \Carbon\Carbon::parse($day->day)->format('D') }}</span>
                @endforeach
            </div>
        </section>

        <section class="pd-card pd-grow-1" aria-labelledby="todo-title">
            <div class="pd-card-head">
                <h2 class="pd-card-title" id="todo-title">Needs your action</h2>
            </div>
            <div class="pd-todo">
                <a href="{{ route('seller.orders', ['tab' => 'to-process']) }}" class="pd-todo-item {{ $pendingOrders > 0 ? 'is-hot' : 'is-done' }}">
                    <span class="pd-todo-icon"><i class="bi bi-box-seam"></i></span>
                    <span class="pd-todo-text"><strong>Pack and ship</strong><span>New orders to prepare</span></span>
                    <span class="pd-todo-count">{{ $pendingOrders }}</span>
                </a>
                <a href="{{ route('seller.orders', ['tab' => 'to-process']) }}" class="pd-todo-item {{ $toDropOff > 0 ? '' : 'is-done' }}">
                    <span class="pd-todo-icon"><i class="bi bi-box-arrow-in-right"></i></span>
                    <span class="pd-todo-text"><strong>Drop off at Sorting Center</strong><span>Being packed — bring them in next</span></span>
                    <span class="pd-todo-count">{{ $toDropOff }}</span>
                </a>
                <a href="{{ route('seller.orders', ['tab' => 'returns']) }}" class="pd-todo-item {{ $pendingReturns > 0 ? 'is-hot' : 'is-done' }}">
                    <span class="pd-todo-icon"><i class="bi bi-arrow-counterclockwise"></i></span>
                    <span class="pd-todo-text"><strong>Return requests</strong><span>Approve or decline</span></span>
                    <span class="pd-todo-count">{{ $pendingReturns }}</span>
                </a>
                <a href="#my-products" class="pd-todo-item {{ $lowStockCount > 0 ? '' : 'is-done' }}">
                    <span class="pd-todo-icon"><i class="bi bi-exclamation-triangle"></i></span>
                    <span class="pd-todo-text"><strong>Low stock</strong><span>5 or fewer left</span></span>
                    <span class="pd-todo-count">{{ $lowStockCount }}</span>
                </a>
            </div>
        </section>
    </div>

    <section class="pd-card" aria-labelledby="recent-title">
        <div class="pd-card-head">
            <h2 class="pd-card-title" id="recent-title">Recent orders</h2>
            <a href="{{ route('seller.orders') }}" class="pd-link">View all orders →</a>
        </div>
        @if($recentOrders->isEmpty())
            <div class="pd-empty"><i class="bi bi-receipt"></i>No orders yet. They show up here as soon as a buyer orders.</div>
        @else
            <div class="pd-table-wrap">
                <table class="pd-table pd-stack">
                    <thead>
                        <tr><th>Order</th><th>Buyer</th><th>Items</th><th>Your total</th><th>Payment</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                            @php
                                $moreLines = (int) $order->line_count - 1;
                                $isCod = $order->payment_method === 'COD' || stripos((string) $order->payment_method, 'cash') !== false;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('seller.order.details', $order->id) }}" class="pd-strong" style="color:#1b1a1f;">#{{ $order->id }}</a>
                                    <div class="pd-sub" style="font-size:12.5px;">{{ \Carbon\Carbon::parse($order->created_at)->format('M j, g:i A') }}</div>
                                </td>
                                <td class="pd-hide-sm">{{ $order->shipping_name ?: 'Buyer' }}</td>
                                <td class="pd-sub">{{ \Illuminate\Support\Str::limit($order->first_item, 34) }}{{ $moreLines > 0 ? ' + ' . $moreLines . ' more' : ' ×' . (int) $order->quantity }}</td>
                                <td class="pd-strong">₱{{ number_format((float) $order->subtotal, 2) }}</td>
                                <td class="pd-sub pd-hide-sm">{{ $isCod ? 'COD' : $order->payment_method }}</td>
                                <td><x-status-pill :status="$order->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

</div>

<div class="container">

    <!-- PRODUCTS HEADER -->

    <div class="top" id="my-products">

        <h2>
            My Products
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
                                    {{ $sellable > 0 ? $sellable : 'Out of stock' }}
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

                                    <form
                                        action="{{ route('seller.products.archive', ['id' => $product->id]) }}"
                                        method="POST"
                                        data-confirm="Archive this product? It will be hidden from the storefront but you can restore it anytime." data-confirm-ok="Archive"
                                    >

                                        @csrf

                                        <button
                                            type="submit"
                                            class="edit-btn"
                                        >
                                            <i class="bi bi-archive"></i> Archive
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

    <p id="productSearchEmpty" style="display:none; text-align:center; color:#6b6570; font-size:13px; margin:10px 0 24px;">
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
