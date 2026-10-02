<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Products — BoomBuy Admin</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">
    {{-- Same look as Manage Accounts: header, filter cards, toolbar, table card. --}}
    <link rel="stylesheet" href="{{ asset('css/pages/admin-accounts.css') }}">

    <style>
        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 220px;
        }

        .product-thumb {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            background: #fff0eb;
            color: var(--accent, #e8420f);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-name {
            font-weight: 700;
            color: #172033;
            line-height: 1.35;
        }

        .product-meta {
            color: #a88d85;
            font-size: 11px;
            margin-top: 3px;
        }

        .product-meta a {
            color: #a88d85;
        }

        .product-meta a:hover {
            color: var(--accent, #e8420f);
        }

        .price-cell {
            font-weight: 800;
            color: #172033;
            white-space: nowrap;
        }

        .stock-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .stock-ok { background: #e9f9ef; color: #15803d; }
        .stock-low { background: #fff4d6; color: #a16207; }
        .stock-out { background: #fee2e2; color: #dc2626; }

        .stock-note {
            display: block;
            color: #a88d85;
            font-size: 10px;
            margin-top: 4px;
        }

        .state-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .state-active { background: #e9f9ef; color: #15803d; }
        .state-archived { background: #f1f0ee; color: #6b6058; }
        .state-flagged { background: #fee2e2; color: #dc2626; }

        .flag-reason {
            display: block;
            max-width: 180px;
            color: #b42318;
            font-size: 10px;
            margin-top: 4px;
            line-height: 1.4;
        }

        /* One size for every action, icon + short label. */
        .row-actions {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
        }

        .row-actions form {
            margin: 0;
        }

        .act {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            height: 32px;
            padding: 0 11px;
            border: 1px solid #f0ddd6;
            border-radius: 9px !important;
            background: #fff;
            color: #6a4e46;
            font-size: 12px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            white-space: nowrap;
        }

        .act:hover {
            border-color: #e8b9aa;
            background: #fff7f4;
            transform: none !important;
        }

        .act.danger {
            color: #dc2626;
            border-color: #fbd5d5;
        }

        .act.danger:hover {
            background: #fff1f1;
        }

        .act.icon-only {
            width: 32px;
            padding: 0;
        }

        .stats {
            grid-template-columns: repeat(5, 1fr);
        }

        @media (max-width: 1100px) {
            .stats { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 800px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
        }

        table { min-width: 860px; }
    </style>
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="products" />

    <main class="main">

        <div class="page-header">
            <small>Admin Panel</small>
            <h1>Products</h1>
            <p>Every seller's listings in one place. Archive or flag what shouldn't be sold — products with order history can't be deleted.</p>
        </div>

        @if(session('success'))
            <div class="success-box"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="error-box"><i class="bi bi-x-circle-fill"></i> {{ session('error') }}</div>
        @endif

        {{-- FILTER CARDS --}}
        <div class="stats" id="liveStats">
            @foreach($states as $key => $label)
                <a
                    href="{{ route('admin.products', array_filter(['state' => $key === 'all' ? null : $key, 'category' => $category === 'all' ? null : $category, 'q' => $search])) }}"
                    class="stat-card {{ $state === $key ? 'active' : '' }}"
                >
                    <span>{{ $label }}</span>
                    <strong>{{ number_format($stateCounts[$key]) }}</strong>
                </a>
            @endforeach
        </div>

        {{-- SEARCH --}}
        <form method="GET" action="{{ route('admin.products') }}" class="account-toolbar" data-live-search data-live-target="#liveStats, #liveClear, #liveResults">
            @if($state !== 'all')
                <input type="hidden" name="state" value="{{ $state }}">
            @endif

            <div class="account-search">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="{{ $search }}" placeholder="Search product, seller or product ID" aria-label="Search products">
            </div>

            <select name="category" aria-label="Category">
                <option value="all">All categories</option>
                @foreach($categories as $label)
                    <option value="{{ $label }}" {{ $category === $label ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="toolbar-btn"><i class="bi bi-search"></i> Search</button>

            <span id="liveClear" style="display:contents;">
                @if($search !== '' || $category !== 'all' || $state !== 'all')
                    <a href="{{ route('admin.products') }}" class="toolbar-btn light"><i class="bi bi-x-lg"></i> Clear</a>
                @endif
            </span>
        </form>

        {{-- TABLE --}}
        <div class="table-card" id="liveResults">

            <div class="table-header">
                <h2>{{ $states[$state] }}{{ $category !== 'all' ? ' · ' . $category : '' }}</h2>
                <span>{{ number_format($products->total()) }} product(s)</span>
            </div>

            @if($products->isNotEmpty())

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>PRODUCT</th>
                                <th>CATEGORY</th>
                                <th>PRICE</th>
                                <th>STOCK</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($products as $product)
                                @php
                                    $stock = $product['stock'];
                                    $stockClass = $stock <= 0 ? 'stock-out' : ($stock < 5 ? 'stock-low' : 'stock-ok');
                                @endphp

                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <div class="product-thumb">
                                                @if($imageUrl = productImageUrl($product['image']))
                                                    <img src="{{ $imageUrl }}" alt="" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('i'), {className: 'bi {{ \App\Support\Categories::icon($product['category']) }}'}))">
                                                @else
                                                    <i class="bi {{ \App\Support\Categories::icon($product['category']) }}"></i>
                                                @endif
                                            </div>

                                            <div>
                                                <div class="product-name">{{ $product['name'] }}</div>
                                                <div class="product-meta">
                                                    #{{ $product['id'] }} ·
                                                    @if($product['seller_id'])
                                                        <a href="{{ route('admin.accounts', ['q' => $product['seller_name']]) }}">{{ $product['seller_name'] ?? 'Unknown seller' }}</a>
                                                    @else
                                                        No seller
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>{{ $product['category'] ?: 'Other' }}</td>

                                    <td class="price-cell">₱{{ number_format($product['price'], 2) }}</td>

                                    <td>
                                        <span class="stock-pill {{ $stockClass }}">
                                            {{ $stock <= 0 ? 'Out of stock' : number_format($stock) . ' left' }}
                                        </span>
                                        @if($product['options'] > 0)
                                            <span class="stock-note">across {{ $product['options'] }} option(s)</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($product['is_flagged'])
                                            <span class="state-pill state-flagged"><i class="bi bi-flag-fill"></i> Flagged</span>
                                            @if($product['flag_reason'])
                                                <span class="flag-reason">{{ \Illuminate\Support\Str::limit($product['flag_reason'], 80) }}</span>
                                            @endif
                                        @elseif($product['is_archived'])
                                            <span class="state-pill state-archived"><i class="bi bi-archive-fill"></i> Archived</span>
                                        @else
                                            <span class="state-pill state-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('product.details', $product['id']) }}" target="_blank" class="act icon-only" title="View in shop" aria-label="View {{ $product['name'] }} in the shop">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>

                                            <a href="{{ route('admin.products.edit', ['id' => $product['id']]) }}" class="act">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>

                                            @if($product['is_archived'])
                                                <form method="POST" action="{{ route('admin.products.unarchive', ['id' => $product['id']]) }}">
                                                    @csrf
                                                    <button type="submit" class="act"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.products.archive', ['id' => $product['id']]) }}" data-confirm="Archive {{ $product['name'] }}? It will be hidden from the shop until restored." data-confirm-ok="Archive">
                                                    @csrf
                                                    <button type="submit" class="act"><i class="bi bi-archive"></i> Archive</button>
                                                </form>
                                            @endif

                                            <form
                                                method="POST"
                                                action="{{ route('admin.products.delete', ['id' => $product['id']]) }}"
                                                data-confirm="Delete {{ $product['name'] }}? This cannot be undone."
                                                data-confirm-ok="Delete"
                                                data-confirm-danger
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="act danger icon-only" title="Delete" aria-label="Delete {{ $product['name'] }}">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('partials.simple-pager', ['paginator' => $products])

            @else

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-box-seam" style="font-size:40px; color:#e5c8bf;"></i></div>
                    <h3>No products found</h3>
                    <p>
                        @if($search !== '' || $category !== 'all' || $state !== 'all')
                            Nothing matches these filters. Try another search or card.
                        @else
                            Products appear here once sellers list them.
                        @endif
                    </p>
                </div>

            @endif

        </div>

        <div class="footer">
            © {{ date('Y') }} BoomBuy · Product Management
        </div>

    </main>

</div>

    @include('partials.live-search')
    @include('partials.pwa-register')

</body>

</html>
