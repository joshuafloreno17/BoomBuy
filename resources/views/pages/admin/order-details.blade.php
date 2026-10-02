<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order #{{ $order->id }} - BoomBuy Admin</title>

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
            max-width: 940px;
            margin: 40px auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
            color: #8e7067;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        h1 {
            margin: 0 0 6px;
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
        }

        h2 {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            font-size: 18px;
            margin: 0 0 12px;
        }

        .subtitle {
            color: #8e7067;
            margin-bottom: 22px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
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

        .card {
            background: white;
            border: 1px solid #f6e1db;
            border-radius: 16px;
            padding: 20px 22px;
            margin-bottom: 16px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
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
            line-height: 1.65;
            word-break: break-word;
        }

        .muted {
            color: #977970;
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

        .item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f6efed;
            font-size: 14px;
        }

        .item:last-of-type {
            border-bottom: none;
        }

        .item > span:last-child {
            white-space: nowrap;
        }

        .sum-line {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #5f4a43;
            padding: 4px 0;
        }

        .sum-line.total {
            margin-top: 8px;
            padding-top: 12px;
            border-top: 1px solid #f6efed;
            font-size: 18px;
            font-weight: 800;
            color: #172033;
        }

        .timeline {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .timeline li {
            display: flex;
            gap: 10px;
            padding: 7px 0;
            font-size: 14px;
            color: #5f4a43;
        }

        .timeline i {
            color: var(--accent, #f13f09);
        }

        .timeline .when {
            margin-left: auto;
            color: #977970;
            white-space: nowrap;
            font-size: 13px;
        }

        .note {
            margin-top: 10px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fff4f0;
            color: #7c3b26;
            font-size: 14px;
        }

        .cancel-card textarea {
            width: 100%;
            min-height: 80px;
            padding: 12px 14px;
            border: 1px solid #ecd7d0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }

        .btn {
            border: none;
            border-radius: 12px;
            padding: 11px 18px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
        }

        .btn-danger {
            background: #dc2626;
            color: #fff;
        }

        @media (max-width: 700px) {
            .container { width: 92%; margin: 24px auto; }
            .grid-2 { grid-template-columns: 1fr; }
            .card { padding: 16px; }
            .timeline li { flex-wrap: wrap; }
            .timeline .when { margin-left: 26px; width: 100%; }
            .btn { width: 100%; justify-content: center; }
            h1 { font-size: 24px; }
        }
    </style>
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="orders" />

    <main class="main">

@php
    $fmt = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('M d, Y · g:i A') : null;
    $itemsTotal = $order->items->sum(fn ($i) => $i->price * $i->quantity);

    $events = array_filter([
        ['bi-bag-check', 'Order placed', $order->created_at],
        ['bi-box-seam', 'Seller handed parcel to rider', $order->seller_confirmed_pickup_at],
        ['bi-building', 'Received at Sorting Center', $order->sorting_center_received_at],
        ['bi-exclamation-triangle', 'Delivery failed' . ($order->delivery_attempts ? ' (attempt ' . $order->delivery_attempts . ')' : ''), $order->delivery_failed_at],
        ['bi-hand-thumbs-down', 'Buyer refused the parcel', $order->buyer_refused_at],
        ['bi-check-circle', 'Buyer confirmed receipt', $order->buyer_received_at],
        ['bi-slash-circle', 'Cancelled' . ($order->cancelled_by ? ' by ' . $order->cancelled_by : ''), $order->cancelled_at],
        ['bi-arrow-counterclockwise', 'Items restocked', $order->restocked_at],
    ], fn ($e) => !empty($e[2]));

    usort($events, fn ($a, $b) => strtotime($a[2]) <=> strtotime($b[2]));
@endphp

<div class="container">

    <a href="{{ route('admin.orders') }}" class="back-link">
        <i class="bi bi-arrow-left"></i> Back to Orders
    </a>

    <h1>Order #{{ $order->id }}</h1>
    <div class="subtitle">
        <span>Placed {{ $fmt($order->created_at) }}</span>
        <x-status-pill :status="$order->status" />
        @if(!empty($order->buyer_received_at))
            <span class="mini-badge"><i class="bi bi-check2-circle"></i> Received by Buyer</span>
        @endif
    </div>

    @if(session('success'))
        <div class="alert"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert error"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
    @endif

    <div class="grid-2">

        <div class="card info">
            <h2>Buyer &amp; Delivery</h2>
            <div class="label">Ship to</div>
            <strong>{{ $order->shipping_name ?: 'Unknown Buyer' }}</strong><br>
            {{ $order->shipping_phone ?: 'No phone' }}<br>
            <span class="muted">{{ $order->shipping_address ?: 'No address' }}</span>

            <div class="label" style="margin-top:12px;">Account email</div>
            {{ $order->buyer_email ?? 'N/A' }}

            <div class="label" style="margin-top:12px;">Payment</div>
            {{ $order->payment_method ?: 'N/A' }}
        </div>

        <div class="card info">
            <h2>Courier</h2>
            <div class="label">Pickup rider</div>
            {{ $order->pickup_rider_name ?? 'Not assigned yet' }}

            <div class="label" style="margin-top:12px;">Delivery rider</div>
            {{ $order->delivery_rider_name ?? 'Not assigned yet' }}

            @if($order->delivery_attempts)
                <div class="label" style="margin-top:12px;">Delivery attempts</div>
                {{ $order->delivery_attempts }}
            @endif

            @if($order->failure_reason)
                <div class="note"><strong>Failure reason:</strong> {{ $order->failure_reason }}</div>
            @endif
        </div>

    </div>

    <div class="card">
        <h2>Items</h2>

        @foreach($order->items as $item)
            <div class="item">
                <span>
                    {{ $item->product_name }}
                    @if(!empty($item->variation_label))
                        <span class="muted">({{ $item->variation_label }})</span>
                    @endif
                    × {{ $item->quantity }}
                    <br>
                    <span class="muted" style="font-size:12px;">
                        <i class="bi bi-shop"></i> {{ $sellerNames[$item->seller_id] ?? 'Unknown seller' }}
                    </span>
                </span>
                <span>₱{{ number_format($item->price * $item->quantity, 2) }}</span>
            </div>
        @endforeach

        <div style="margin-top:10px;">
            <div class="sum-line"><span>Items</span><span>₱{{ number_format($itemsTotal, 2) }}</span></div>

            @if((float) $order->discount_amount > 0)
                <div class="sum-line">
                    <span>Voucher{{ $order->voucher_code ? ' (' . $order->voucher_code . ')' : '' }}</span>
                    <span>−₱{{ number_format($order->discount_amount, 2) }}</span>
                </div>
            @endif

            <div class="sum-line">
                <span>Delivery fee</span>
                <span>{{ (float) $order->delivery_fee > 0 ? '₱' . number_format($order->delivery_fee, 2) : 'Free' }}</span>
            </div>

            <div class="sum-line total"><span>Total</span><span>₱{{ number_format($order->total_amount, 2) }}</span></div>
        </div>
    </div>

    <div class="card">
        <h2>History</h2>

        <ul class="timeline">
            @foreach($events as [$icon, $text, $when])
                <li><i class="bi {{ $icon }}"></i> <span>{{ $text }}</span> <span class="when">{{ $fmt($when) }}</span></li>
            @endforeach
        </ul>

        @if($order->cancellation_reason)
            <div class="note"><strong>Cancellation reason:</strong> {{ $order->cancellation_reason }}</div>
        @endif
    </div>

    @if($canCancel)
        <div class="card cancel-card">
            <h2>Cancel this order</h2>
            <p class="info muted" style="margin-top:0;">
                The items are still with the seller, so cancelling returns them to stock and releases any voucher used.
                The buyer and seller are both notified with your reason.
            </p>

            <form
                method="POST"
                action="{{ route('admin.order.cancel', $order->id) }}"
                data-confirm="Cancel order #{{ $order->id }}? This cannot be undone."
                data-confirm-title="Cancel order"
                data-confirm-ok="Cancel order"
                data-confirm-cancel="Keep order"
                data-confirm-danger
            >
                @csrf
                <label class="label" for="reason">Reason (shown to the buyer and seller)</label>
                <textarea id="reason" name="reason" maxlength="500" required placeholder="e.g. Suspected fraudulent order">{{ old('reason') }}</textarea>
                <button type="submit" class="btn btn-danger"><i class="bi bi-slash-circle"></i> Cancel Order</button>
            </form>
        </div>
    @elseif(!in_array($order->status, ['Delivered', 'Cancelled', 'Returned to Seller']))
        <div class="card info muted">
            <i class="bi bi-info-circle"></i>
            This order has already left the seller, so it can no longer be cancelled here. If it can't be delivered, Logistics returns it to the seller from the Parcels page.
        </div>
    @endif

</div>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>
