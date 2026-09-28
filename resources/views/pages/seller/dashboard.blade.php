
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Dashboard — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/pages/seller-dashboard.css') }}">
</head>

<body>

<div class="layout">

<x-layout.seller-sidebar
    active="dashboard"
    :user="$user"
    logo-href="/"
    :notif-link-fix="true"
/>


<!-- MAIN -->

<main class="main-content">

<div class="container">

    @include('partials.announcement-banner')

    <!-- WELCOME -->

    <section class="welcome">

        <small>
            Seller Dashboard
        </small>

        <h1>
            Welcome, {{ $user['name'] ?? 'Seller' }}!
        </h1>

        <p>
            Manage your products and sell them through BoomBuy.
        </p>

    </section>


    <!-- SUCCESS MESSAGE -->

    @if(session('success'))

        <div class="alert-success">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>

    @endif


    <!-- ERROR MESSAGE -->

    @if(session('error'))

        <div class="alert-error">
            <i class="bi bi-x-circle-fill"></i> {{ session('error') }}
        </div>

    @endif


    <!-- STATISTICS -->

    <section class="stats">

        <div class="stat-card">

            <div class="stat-icon">
                <i class="bi bi-box-seam-fill"></i>
            </div>

            <div>

                <div class="stat-title">
                    Total Products
                </div>

                <div class="stat-number">
                    {{ $totalProducts ?? count($products ?? []) }}
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="bi bi-receipt"></i>
            </div>

            <div>

                <div class="stat-title">
                    Total Orders
                </div>

                <div class="stat-number">
                    {{ $totalOrders ?? 0 }}
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="bi bi-hourglass-split"></i>
            </div>

            <div>

                <div class="stat-title">
                    Pending Orders
                </div>

                <div class="stat-number">
                    {{ $pendingOrders ?? 0 }}
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="bi bi-cash-stack"></i>
            </div>

            <div>

                <div class="stat-title">
                    Total Sales
                </div>

                <div class="stat-number">
                    ₱{{ number_format($totalSales ?? 0, 2) }}
                </div>

            </div>

        </div>

    </section>


    <!-- CHARTS -->

    <section class="stats" style="grid-template-columns: 1.4fr 1fr; margin-bottom:20px;">

        <div class="stat-card" style="display:block;">
            <div class="stat-title" style="margin-bottom:12px;">Sales Trend (Last 7 Days)</div>
            <canvas id="sellerSalesTrendChart" height="110"></canvas>
        </div>

        <div class="stat-card" style="display:block;">
            <div class="stat-title" style="margin-bottom:12px;">Orders by Status</div>
            <canvas id="sellerOrderStatusChart" height="110"></canvas>
        </div>

    </section>


    <!-- PRODUCTS HEADER -->

    <div class="top">

        <h2>
            My Products
        </h2>

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

            <table class="products-table">

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

                        <tr>

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
                                    $stockClass = $product->stock <= 0
                                        ? 'out'
                                        : ($product->stock <= 5 ? 'low' : '');
                                @endphp

                                <span class="stock-count {{ $stockClass }}">
                                    {{ $product->stock > 0 ? $product->stock : 'Out of stock' }}
                                </span>

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

                        <tr style="opacity:0.65;">

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

</div>

</main>

</div><!-- /.layout -->


<!-- FOOTER -->

<footer>

    <div>
        © 2026 <strong>BoomBuy</strong>
    </div>

    <div>
        Seller Dashboard
    </div>

</footer>

    @include('partials.pwa-register')

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        var trendLabels = @json($salesTrend->map(fn($r) => \Carbon\Carbon::parse($r->day)->format('M d')));
        var trendRevenue = @json($salesTrend->map(fn($r) => (float) $r->revenue));

        var statusLabels = @json($orderStatusBreakdown->pluck('status'));
        var statusCounts = @json($orderStatusBreakdown->pluck('total'));

        if (window.Chart) {
            new Chart(document.getElementById('sellerSalesTrendChart'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: trendRevenue,
                        borderColor: '#e8420f',
                        backgroundColor: 'rgba(232,66,15,0.1)',
                        tension: 0.3,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            new Chart(document.getElementById('sellerOrderStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusCounts,
                        backgroundColor: ['#e8420f', '#f4a582', '#facc15', '#38bdf8', '#4ade80', '#a78bfa', '#f87171'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
                }
            });
        }
    </script>

</body>
</html>
