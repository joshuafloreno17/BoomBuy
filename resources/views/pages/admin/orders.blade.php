<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Admin Orders - BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/admin-orders.css') }}">
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
