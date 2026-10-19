
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Seller Dashboard — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-dashboard.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/portal-dash.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/seller-dashboard-final.css') }}">
</head>

<body>

<div class="layout">

<x-layout.seller-sidebar
    active="dashboard"
    :user="$user"
/>


<!-- MAIN -->

<main class="main-content">

<div class="sd">

    @include('partials.announcement-banner')

    @php
        // First guess from the server; the script at the bottom switches it to the viewer's own clock.
        $hour = (int) now()->format('G');
        $greeting = $hour >= 5 && $hour < 12 ? 'Good morning' : ($hour >= 12 && $hour < 18 ? 'Good afternoon' : 'Good evening');
        $weekMax = max(1, (float) $salesTrend->max('revenue'));
        $peso = fn ($v) => '₱' . number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2);
        $shortPeso = fn ($v) => $v >= 1000 ? '₱' . rtrim(rtrim(number_format($v / 1000, 1), '0'), '.') . 'k' : '₱' . number_format($v);
        $deliveredCount = (int) ($deliveredToday->orders ?? 0);
        $ratingCount = (int) ($rating->total ?? 0);
        $weekEmpty = $salesTrend->sum('revenue') <= 0;
        $allClear = $newOrders + $toDropOff + $pendingReturns + $lowStockCount === 0;

        // Today vs yesterday, for the little arrow on the sales card.
        $todayRev = (float) ($salesTrend->last()->revenue ?? 0);
        $yesterdayRev = (float) ($salesTrend->slice(-2, 1)->first()->revenue ?? 0);
        $trend = $yesterdayRev > 0 ? (int) round(($todayRev - $yesterdayRev) / $yesterdayRev * 100) : null;

        // Sparkline across the 7 days.
        $sparkPoints = $salesTrend->values()->map(fn ($d, $i) => round($i * (240 / 6), 1) . ',' . round(54 - ((float) $d->revenue / $weekMax) * 46, 1))->implode(' ');

        $columns = [
            'new' => ['title' => 'New orders', 'tone' => 'is-amber', 'empty' => 'No new orders'],
            'accepted' => ['title' => 'Accepted', 'tone' => 'is-plain', 'empty' => 'Nothing to start'],
            'preparing' => ['title' => 'Preparing', 'tone' => 'is-violet', 'empty' => 'Nothing being packed'],
            'pickup' => ['title' => 'Ready for pickup', 'tone' => 'is-blue', 'empty' => 'Nothing waiting for a rider'],
        ];
        $lowStock = $products->filter(fn ($p) => (int) $p->sellable_stock <= 5)->sortBy('sellable_stock')->take(3);
        $starTotal = max(1, (int) collect($starCounts)->sum());
        $commissionShare = $month['sales'] > 0 ? (int) round($month['commission'] / $month['sales'] * 100) : 10;
        $ring = 2 * M_PI * 48;
    @endphp

    <div class="sd-top">
        <div>
            <p class="sd-eyebrow">{{ now()->format('l, F j') }}</p>
            <h1 class="sd-title"><span data-greet>{{ $greeting }}</span>, {{ $shopName }}</h1>
        </div>
        <div class="sd-top-actions">
            <form class="sd-search" action="{{ route('seller.orders') }}" method="GET" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="q" placeholder="Search order # or buyer" aria-label="Search your orders">
            </form>
            @include('partials.notification-popover', [
                'notes' => $recentNotes,
                'unread' => $unreadNotes,
                'allUrl' => route('seller.notifications'),
            ])
        </div>
    </div>

    @if(session('success'))
        <div class="pd-alert is-ok"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="pd-alert is-error"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
    @endif

    {{-- Hero + today's sales --}}
    <div class="sd-row">
        <section class="sd-hero">
            <span class="sd-orb is-a" aria-hidden="true"></span>
            <span class="sd-orb is-b" aria-hidden="true"></span>
            <div class="sd-hero-text">
                <p class="sd-hero-eyebrow">Your shop today</p>
                <h2>
                    @if($allClear)
                        All caught up. Nice work!
                    @else
                        {{ $newOrders }} new {{ \Illuminate\Support\Str::plural('order', $newOrders) }}, {{ $toDropOff }} to pack
                    @endif
                </h2>
                <p>
                    @if($toShipOld > 0)
                        {{ $toShipOld }} {{ $toShipOld === 1 ? 'order has' : 'orders have' }} been waiting since yesterday, so start there.
                    @elseif($pendingReturns > 0)
                        {{ $pendingReturns }} return {{ \Illuminate\Support\Str::plural('request', $pendingReturns) }} to answer.
                    @else
                        Accept new orders, pack them, then book a pickup from the order page.
                    @endif
                </p>
            </div>
            <div class="sd-hero-actions">
                <a href="{{ route('seller.orders', ['tab' => 'to-process']) }}" class="sd-btn sd-btn-lilac"><i class="bi bi-box-seam"></i> Accept new orders</a>
                <a href="{{ route('seller.products.create') }}" class="sd-btn sd-btn-glass"><i class="bi bi-plus-lg"></i> Add product</a>
                <a href="{{ route('seller.vouchers') }}" class="sd-btn sd-btn-glass">Vouchers</a>
            </div>
        </section>

        <section class="sd-today" aria-labelledby="today-title">
            <div class="sd-today-head">
                <span id="today-title">Sales today</span>
                @if($trend !== null)
                    <span class="sd-chip">{{ $trend >= 0 ? '▲' : '▼' }} {{ abs($trend) }}%</span>
                @endif
            </div>
            <strong class="sd-today-value">{{ $peso($deliveredToday->revenue ?? 0) }}</strong>
            <svg class="sd-spark" viewBox="0 0 240 60" preserveAspectRatio="none" aria-hidden="true">
                <polyline points="0,60 {{ $sparkPoints }} 240,60" fill="rgba(255,255,255,.14)" stroke="none"></polyline>
                <polyline points="{{ $sparkPoints }}" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
            </svg>
            <span class="sd-today-note">{{ $deliveredCount }} {{ $deliveredCount === 1 ? 'order' : 'orders' }} delivered today</span>
        </section>
    </div>

    {{-- KPIs --}}
    <section class="sd-kpis" aria-label="Your shop at a glance">
        <a href="{{ route('seller.orders', ['tab' => 'to-process']) }}" class="sd-kpi {{ $pendingOrders > 0 ? 'is-hot' : '' }}">
            <span class="sd-kpi-icon is-amber"><i class="bi bi-box-seam"></i></span>
            <span class="sd-kpi-label">To ship</span>
            <strong>{{ $pendingOrders }}</strong>
            <span class="sd-kpi-note">{{ $toShipOld > 0 ? $toShipOld . ' waiting since yesterday' : ($pendingOrders > 0 ? 'All from today' : 'All caught up') }}</span>
        </a>
        <a href="{{ route('seller.orders', ['tab' => 'returns']) }}" class="sd-kpi">
            <span class="sd-kpi-icon is-red"><i class="bi bi-arrow-counterclockwise"></i></span>
            <span class="sd-kpi-label">Return requests</span>
            <strong>{{ $pendingReturns }}</strong>
            <span class="sd-kpi-note">{{ $pendingReturns > 0 ? 'Approve or decline' : 'None waiting' }}</span>
        </a>
        <a href="#my-products" class="sd-kpi">
            <span class="sd-kpi-icon is-violet"><i class="bi bi-grid"></i></span>
            <span class="sd-kpi-label">Products live</span>
            <strong>{{ $totalProducts }}</strong>
            <span class="sd-kpi-note">{{ $lowStockCount > 0 ? $lowStockCount . ' low on stock' : 'Stock looks good' }}</span>
        </a>
        <a href="{{ route('seller.reviews') }}" class="sd-kpi is-dark">
            <span class="sd-kpi-icon"><i class="bi bi-star-fill"></i></span>
            <span class="sd-kpi-label">Shop rating</span>
            <strong>{{ $ratingCount > 0 ? number_format((float) $rating->average, 1) : '—' }}</strong>
            <span class="sd-kpi-note">{{ $ratingCount > 0 ? 'from ' . $ratingCount . ' ' . ($ratingCount === 1 ? 'review' : 'reviews') : 'No reviews yet' }}</span>
        </a>
    </section>

    @if(isset($codPayout) && ((float) $codPayout->pending > 0 || (float) $codPayout->released > 0))
        {{-- The money still with riders is what to watch, so it gets the big number. --}}
        <a href="{{ route('seller.orders', ['tab' => 'completed']) }}" class="pd-cod">
            <i class="bi bi-cash-stack"></i>
            <span class="pd-cod-main">
                @if((float) $codPayout->pending > 0)
                    <span class="pd-cod-label">Cash on Delivery still with riders</span>
                    <strong class="pd-cod-amount">{{ $peso($codPayout->pending) }}</strong>
                    @if((float) $codPayout->released > 0)
                        <span class="pd-cod-sub">{{ $peso($codPayout->released) }} already handed in at the Sorting Center</span>
                    @endif
                @else
                    <span class="pd-cod-label">Cash on Delivery handed in at the Sorting Center</span>
                    <strong class="pd-cod-amount">{{ $peso($codPayout->released) }}</strong>
                    <span class="pd-cod-sub">Nothing still with riders</span>
                @endif
            </span>
            <span class="pd-cod-go">See orders <i class="bi bi-arrow-right"></i></span>
        </a>
    @endif

    {{-- Order board --}}
    <section class="sd-card" aria-labelledby="board-title">
        <div class="sd-card-head">
            <div>
                <h2 id="board-title">Order board</h2>
                <p class="sd-muted">Your open orders, oldest first. Accept and start preparing right from here.</p>
            </div>
            <a href="{{ route('seller.orders') }}" class="sd-link">All orders →</a>
        </div>
        <div class="sd-board">
            @foreach($columns as $key => $col)
                <div class="sd-col {{ $col['tone'] }}">
                    <div class="sd-col-head"><strong>{{ $col['title'] }}</strong><span>{{ $board[$key]['total'] }}</span></div>

                    @forelse($board[$key]['orders'] as $o)
                        <article class="sd-order">
                            <div class="sd-order-top"><a href="{{ route('seller.order.details', $o['id']) }}">#{{ $o['id'] }}</a><span>{{ $o['pay'] }} · {{ $o['age'] }}</span></div>
                            <div class="sd-order-main">
                                <span class="sd-thumb">
                                    @if($o['image'])<img src="{{ $o['image'] }}" alt="" onerror="this.remove()">@endif
                                    <i class="bi {{ $o['icon'] }}"></i>
                                </span>
                                <span class="sd-order-text"><strong>{{ \Illuminate\Support\Str::limit($o['item'], 38) }}</strong><span>{{ $o['buyer'] }} · ₱{{ number_format($o['subtotal'], 2) }}</span></span>
                            </div>

                            @if($key === 'new')
                                <form method="POST" action="{{ route('seller.order.status', $o['id']) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="Confirmed">
                                    <button type="submit" class="sd-order-btn">Accept order</button>
                                </form>
                            @elseif($key === 'accepted')
                                <form method="POST" action="{{ route('seller.order.status', $o['id']) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="Preparing">
                                    <button type="submit" class="sd-order-btn">Start preparing</button>
                                </form>
                            @elseif($key === 'preparing')
                                <a href="{{ route('seller.order.details', $o['id']) }}" class="sd-order-btn">Print label &amp; book pickup</a>
                            @else
                                <a href="{{ route('seller.order.details', $o['id']) }}" class="sd-order-wait"><i class="bi bi-truck"></i> {{ $o['status'] === 'Pickup Assigned' ? 'A rider is on the way' : 'Waiting for a rider' }}</a>
                            @endif
                        </article>
                    @empty
                        <div class="sd-col-empty">{{ $col['empty'] }}</div>
                    @endforelse

                    @if($board[$key]['total'] > 3)
                        <a href="{{ route('seller.orders') }}" class="sd-col-more">+{{ $board[$key]['total'] - 3 }} more</a>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Sales chart + best sellers --}}
    <div class="sd-row sd-row-top">
        <section class="sd-card sd-grow-2" aria-labelledby="week-title">
            <div class="sd-card-head">
                <div>
                    <h2 id="week-title">Sales this week</h2>
                    <p class="sd-muted">Delivered orders, last 7 days</p>
                </div>
                <a href="{{ route('seller.reports') }}" class="sd-link">Full report →</a>
            </div>
            @if($weekEmpty)
                <p class="sd-week-empty"><i class="bi bi-graph-up-arrow"></i> No delivered sales this week yet. Share your shop link to get your first sale.</p>
            @endif
            <div class="sd-bars" role="img" aria-label="Delivered sales for the last 7 days">
                @foreach($salesTrend as $day)
                    <div class="sd-bar {{ $loop->last ? 'is-today' : '' }}">
                        <span class="sd-bar-amount">{{ (float) $day->revenue > 0 ? $shortPeso((float) $day->revenue) : '' }}</span>
                        <span class="sd-bar-fill" style="height: {{ max(3, round((float) $day->revenue / $weekMax * 100)) }}%; animation-delay: {{ $loop->index * 0.04 }}s"></span>
                        <span class="sd-bar-day">{{ $loop->last ? 'Today' : \Carbon\Carbon::parse($day->day)->format('D') }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sd-card sd-grow-1" aria-labelledby="best-title">
            <div class="sd-card-head">
                <h2 id="best-title">Best sellers</h2>
                <span class="sd-muted">this month</span>
            </div>
            @forelse($bestSellers as $b)
                <div class="sd-best">
                    <span class="sd-best-rank">{{ $loop->iteration }}</span>
                    <span class="sd-thumb">
                        @if($b['image'])<img src="{{ $b['image'] }}" alt="" onerror="this.remove()">@endif
                        <i class="bi {{ $b['icon'] }}"></i>
                    </span>
                    <span class="sd-best-main"><strong>{{ \Illuminate\Support\Str::limit($b['name'], 30) }}</strong><span class="sd-meter"><span style="width: {{ $b['share'] }}%"></span></span></span>
                    <b>{{ $b['sold'] }} sold</b>
                </div>
            @empty
                <div class="sd-empty"><i class="bi bi-trophy"></i>Your best sellers show up here once orders are delivered this month.</div>
            @endforelse
        </section>
    </div>

    {{-- Low stock · reviews · this month --}}
    <div class="sd-row sd-row-stretch">
        <section class="sd-card sd-grow-1" aria-labelledby="low-title">
            <div class="sd-card-head">
                <h2 id="low-title">Running low</h2>
                @if($lowStockCount > 0)<span class="sd-pill is-amber">{{ $lowStockCount }} {{ \Illuminate\Support\Str::plural('product', $lowStockCount) }}</span>@endif
            </div>
            @forelse($lowStock as $p)
                @php $left = (int) $p->sellable_stock; @endphp
                <a href="{{ route('seller.products.edit', ['id' => $p->id]) }}" class="sd-stock">
                    <span class="sd-stock-top"><strong>{{ \Illuminate\Support\Str::limit($p->name, 28) }}</strong><b class="{{ $left <= 0 ? 'is-red' : 'is-amber' }}">{{ $left > 0 ? $left . ' left' : 'Out of stock' }}</b></span>
                    <span class="sd-meter"><span class="{{ $left <= 2 ? 'is-red' : 'is-amber' }}" style="width: {{ max(4, $left * 20) }}%"></span></span>
                </a>
            @empty
                <div class="sd-empty"><i class="bi bi-check2-circle"></i>Stock looks good. Nothing at 5 or fewer.</div>
            @endforelse
            <a href="#my-products" class="sd-link sd-push">Update stock →</a>
        </section>

        <section class="sd-card sd-grow-1" aria-labelledby="reviews-title">
            <div class="sd-card-head">
                <h2 id="reviews-title">Reviews</h2>
                <span class="sd-rating"><i class="bi bi-star-fill"></i> {{ $ratingCount > 0 ? number_format((float) $rating->average, 1) : '—' }}</span>
            </div>
            <div class="sd-stars">
                @foreach([5, 4, 3, 2, 1] as $star)
                    <div><span>{{ $star }}★</span><span class="sd-meter"><span class="is-gold" style="width: {{ round(((int) ($starCounts[$star] ?? 0)) / $starTotal * 100) }}%"></span></span><em>{{ (int) ($starCounts[$star] ?? 0) }}</em></div>
                @endforeach
            </div>
            @if($latestReview)
                <div class="sd-review">
                    <div><strong>{{ $latestReview->buyer }}</strong> <span class="sd-review-stars">{{ str_repeat('★', (int) $latestReview->rating) }}</span></div>
                    <p>{{ $latestReview->review ? '“' . \Illuminate\Support\Str::limit($latestReview->review, 90) . '”' : 'Rated ' . \Illuminate\Support\Str::limit($latestReview->product, 30) }}</p>
                    <a href="{{ route('seller.reviews') }}" class="sd-link">Reply →</a>
                </div>
            @else
                <div class="sd-empty"><i class="bi bi-chat-heart"></i>No reviews yet.</div>
            @endif
        </section>

        <section class="sd-month" aria-labelledby="month-title">
            <span class="sd-orb is-c" aria-hidden="true"></span>
            <div class="sd-month-head"><h2 id="month-title">This month</h2><span>{{ now()->format('F') }}</span></div>
            <div class="sd-month-body">
                <svg class="sd-ring" viewBox="0 0 120 120" role="img" aria-label="{{ 100 - $commissionShare }}% of sales is yours after commission">
                    <circle cx="60" cy="60" r="48" fill="none" stroke="#3f2868" stroke-width="14"/>
                    <circle cx="60" cy="60" r="48" fill="none" stroke="#4ade80" stroke-width="14" stroke-linecap="round" stroke-dasharray="{{ round($ring * (100 - $commissionShare) / 100, 1) }} {{ round($ring, 1) }}" transform="rotate(-90 60 60)"/>
                    <text x="60" y="58" text-anchor="middle" class="sd-ring-num">{{ 100 - $commissionShare }}%</text>
                    <text x="60" y="76" text-anchor="middle" class="sd-ring-label">yours</text>
                </svg>
                <div class="sd-month-nums">
                    <span><small>Gross sales</small><b>₱{{ number_format($month['sales'], 2) }}</b></span>
                    <span><small class="is-green">● Net earnings</small><b>₱{{ number_format($month['earnings'], 2) }}</b></span>
                    <span><small class="is-lilac">● Commission</small><b>₱{{ number_format($month['commission'], 2) }}</b></span>
                </div>
            </div>
            <span class="sd-month-note">{{ $monthOrders }} delivered {{ \Illuminate\Support\Str::plural('order', $monthOrders) }} this month</span>
            <a href="{{ route('seller.reports') }}" class="sd-btn sd-btn-lilac sd-btn-block">Open reports</a>
        </section>
    </div>

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

    <p id="productSearchEmpty" style="display:none; text-align:center; color:#645a80; font-size:13px; margin:10px 0 24px;">
        No products match your search.
    </p>

</div>

</main>

</div><!-- /.layout -->




    <script>
        // Greeting follows the viewer's own clock and changes on its own.
        (function () {
            var greet = document.querySelector('[data-greet]');
            if (!greet) return;

            function setGreeting() {
                var h = new Date().getHours();
                greet.textContent = h >= 5 && h < 12 ? 'Good morning' : (h >= 12 && h < 18 ? 'Good afternoon' : 'Good evening');
            }

            setGreeting();
            setInterval(setGreeting, 60000);
        })();

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
