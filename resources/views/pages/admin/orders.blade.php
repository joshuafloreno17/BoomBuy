<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Orders - BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">

    <style>
@import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 40px auto;
        }

        h1 {
            margin: 0 0 6px;
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
        }

        .subtitle {
            color: #8e7067;
            margin-bottom: 24px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: #e9f8ef;
            color: #087a3d;
        }

        .alert.error {
            background: #fdecec;
            color: #b42318;
        }

        /* Search */
        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 220px;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #a88d85;
        }

        .search-box input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1px solid #ecd7d0;
            border-radius: 12px;
            font-size: 14px;
            background: #fff;
            font-family: inherit;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--accent, #f13f09);
        }

        .btn {
            border: none;
            border-radius: 12px;
            padding: 11px 18px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            font-family: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: var(--accent, #f13f09);
            color: #fff;
        }

        .btn-light {
            background: #f7f0ee;
            color: #553b33;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 6px;
            margin-bottom: 20px;
            scrollbar-width: thin;
        }

        .tab {
            flex: 0 0 auto;
            padding: 9px 14px;
            border-radius: 999px;
            border: 1px solid #f0dcd5;
            background: #fff;
            color: #6f5850;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            white-space: nowrap;
        }

        .tab .count {
            display: inline-block;
            min-width: 22px;
            margin-left: 6px;
            padding: 1px 7px;
            border-radius: 999px;
            background: #f7ece8;
            color: #8e7067;
            font-size: 12px;
            text-align: center;
        }

        .tab.active {
            background: var(--accent, #f13f09);
            border-color: var(--accent, #f13f09);
            color: #fff;
        }

        .tab.active .count {
            background: rgba(255, 255, 255, .25);
            color: #fff;
        }

        /* Order cards */
        .empty {
            background: white;
            border: 1px solid #f6e1db;
            border-radius: 16px;
            padding: 50px 20px;
            text-align: center;
            color: #8e7067;
        }

        .empty i {
            font-size: 34px;
            color: #e8b9aa;
        }

        .order-card {
            background: white;
            border: 1px solid #f6e1db;
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 14px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
        }

        .order-id {
            font-weight: 800;
            font-size: 17px;
        }

        .order-date {
            color: #977970;
            font-size: 13px;
            margin-top: 2px;
        }

        .badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
        }

        .mini-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            background: #e9f8ef;
            color: #087a3d;
        }

        .order-body {
            display: grid;
            grid-template-columns: 1.1fr 1.4fr auto;
            gap: 18px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #f6efed;
            align-items: start;
        }

        .label {
            color: #a88d85;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 4px;
        }

        .info {
            color: #5f4a43;
            font-size: 14px;
            line-height: 1.6;
            word-break: break-word;
        }

        .info .muted {
            color: #977970;
        }

        .item-line {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .item-line > span:last-child {
            white-space: nowrap;
        }

        .order-total {
            text-align: right;
            white-space: nowrap;
        }

        .order-total .amount {
            font-size: 19px;
            font-weight: 800;
            color: #172033;
        }

        .order-total .btn {
            margin-top: 10px;
        }

        @media (max-width: 860px) {
            .order-body {
                grid-template-columns: 1fr;
            }

            .order-total {
                text-align: left;
            }
        }

        @media (max-width: 640px) {
            .container { width: 92%; margin: 24px auto; }
            .order-card { padding: 16px; }
            .toolbar .btn { flex: 1; justify-content: center; }
            h1 { font-size: 24px; }
        }
    </style>
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="orders" />

    <main class="main">

<div class="container">

    <h1>Orders</h1>
    <div class="subtitle">
        Track every BoomBuy order. Orders move forward through the seller, rider and Logistics flows; open an order to cancel it while it is still with the seller.
    </div>

    @if(session('success'))
        <div class="alert"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert error"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.orders') }}" class="toolbar" data-live-search data-live-target="#liveClear, #liveTabs, #liveResults">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <div class="search-box">
            <i class="bi bi-search"></i>
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Search order #, buyer name, phone or email"
                aria-label="Search orders"
            >
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>

        <span id="liveClear" style="display:contents;">
        @if($search !== '')
            <a href="{{ route('admin.orders', ['tab' => $tab]) }}" class="btn btn-light"><i class="bi bi-x-lg"></i> Clear</a>
        @endif
        </span>
    </form>

    <div class="tabs" id="liveTabs">
        @foreach($tabs as $key => $definition)
            <a
                href="{{ route('admin.orders', array_filter(['tab' => $key, 'q' => $search])) }}"
                class="tab {{ $tab === $key ? 'active' : '' }}"
            >
                {{ $definition['label'] }}<span class="count">{{ $tabCounts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <div id="liveResults">

    @if($orders->isEmpty())

        <div class="empty">
            <i class="bi bi-inbox"></i>
            <h2>No orders found</h2>
            <p>
                @if($search !== '')
                    Nothing matches "{{ $search }}" here. Try another search or tab.
                @else
                    There are no orders in this tab yet.
                @endif
            </p>
        </div>

    @else

        @foreach($orders as $order)

            <div class="order-card">

                <div class="order-header">
                    <div>
                        <div class="order-id">Order #{{ $order->id }}</div>
                        <div class="order-date">
                            {{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y · g:i A') }}
                        </div>
                    </div>

                    <div class="badges">
                        <x-status-pill :status="$order->status" />

                        @if(!empty($order->buyer_received_at))
                            <span class="mini-badge"><i class="bi bi-check2-circle"></i> Received by Buyer</span>
                        @endif
                    </div>
                </div>

                <div class="order-body">

                    <div class="info">
                        <div class="label">Buyer</div>
                        <strong>{{ $order->shipping_name ?: 'Unknown Buyer' }}</strong><br>
                        <span class="muted">{{ $order->buyer_email ?? 'No account email' }}</span><br>
                        <span class="muted">{{ $order->shipping_phone ?: 'No phone' }}</span><br>
                        <span class="muted">{{ $order->payment_method ?: 'N/A' }}</span>

                        @if($order->pickup_rider_name || $order->delivery_rider_name)
                            <div class="label" style="margin-top:10px;">Rider</div>
                            @if($order->pickup_rider_name)
                                Pickup: {{ $order->pickup_rider_name }}<br>
                            @endif
                            @if($order->delivery_rider_name)
                                Delivery: {{ $order->delivery_rider_name }}
                            @endif
                        @endif
                    </div>

                    <div class="info">
                        <div class="label">Items ({{ $order->items->sum('quantity') }})</div>

                        @foreach($order->items->take(3) as $item)
                            <div class="item-line">
                                <span>
                                    {{ $item->product_name }}
                                    @if(!empty($item->variation_label))
                                        <span class="muted">({{ $item->variation_label }})</span>
                                    @endif
                                    × {{ $item->quantity }}
                                </span>
                                <span>₱{{ number_format($item->price * $item->quantity, 2) }}</span>
                            </div>
                        @endforeach

                        @if($order->items->count() > 3)
                            <div class="muted">+ {{ $order->items->count() - 3 }} more item(s)</div>
                        @endif
                    </div>

                    <div class="order-total">
                        <div class="label">Total</div>
                        <div class="amount">₱{{ number_format($order->total_amount, 2) }}</div>
                        <a href="{{ route('admin.order.details', $order->id) }}" class="btn btn-light">
                            View Details <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>

                </div>

            </div>

        @endforeach

        @include('partials.simple-pager', ['paginator' => $orders])

    @endif

    </div>

</div>

    </main>

</div>

    @include('partials.live-search')
    @include('partials.pwa-register')

</body>
</html>
