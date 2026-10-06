<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order #{{ $order->id }} - BoomBuy Admin</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/admin-order-details.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.admin-sidebar active="orders" />

    <main class="main">

@php
    $fmt = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('M d, Y · g:i A') : null;
    $itemsTotal = $order->items->sum(fn ($i) => $i->price * $i->quantity);

    $centerNames = \App\Models\SortingCenter::whereIn('id', array_filter([$order->origin_center_id, $order->destination_center_id]))->pluck('name', 'id');
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
            <div class="label">Route</div>
            {{ $centerNames[$order->origin_center_id] ?? 'Seller\'s Sorting Center' }}
            → {{ $centerNames[$order->destination_center_id] ?? 'Buyer\'s Sorting Center' }}
            <span class="muted">· {{ ($order->fulfillment ?? 'delivery') === 'pickup' ? 'buyer picks up at the center' : 'door delivery' }}</span>

            <div class="label" style="margin-top:12px;">Delivery rider</div>
            {{ ($order->fulfillment ?? 'delivery') === 'pickup' ? 'None — picked up at the center' : ($order->delivery_rider_name ?? 'Not assigned yet') }}

            @if(\App\Support\CodPolicy::isCod($order->payment_method))
                <div class="label" style="margin-top:12px;">Cash on Delivery</div>
                @if($order->cod_remitted_at)
                    Handed in to the Sorting Center {{ $fmt($order->cod_remitted_at) }} — payout released
                @elseif($order->cod_collected_at)
                    Collected by the rider {{ $fmt($order->cod_collected_at) }} — not handed in yet
                @else
                    Not collected yet
                @endif
            @endif

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

        @include('partials.order-timeline', [
            'steps' => \App\Support\OrderTimeline::forOrders([(int) $order->id])->get((int) $order->id),
            'proof' => $order->delivery_proof ? route('orders.delivery-proof', $order->id) : null,
        ])

        @if(!empty($order->restocked_at))
            <div class="note"><i class="bi bi-arrow-counterclockwise"></i> Items restocked {{ $fmt($order->restocked_at) }}.</div>
        @endif

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
