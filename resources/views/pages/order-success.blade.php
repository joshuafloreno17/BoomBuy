<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Order Successful — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/views/order-success.css') }}">

</head>

<body>

@include('partials.buyer-navbar', ['activeNav' => 'orders'])

<main class="success-wrap">
<div class="success-card">

    <div class="success-icon">
        <i class="bi bi-check-circle-fill"></i>
    </div>

    <small>
        BoomBuy Order
    </small>

    <h1>
        Order Placed Successfully!
    </h1>

    <p>
        Thank you for shopping with BoomBuy.
        Your order has been received and is now being processed.
    </p>


    @php
        $checkoutOrders = collect($checkoutOrders ?? []);
        $isSplit = $checkoutOrders->count() > 1;
    @endphp

    @if($isSplit)
        <p style="margin-top:-6px; font-size:13px;">
            Your items come from {{ $checkoutOrders->count() }} different sellers, so they were placed as
            {{ $checkoutOrders->count() }} separate orders — each seller ships their own parcel.
        </p>
    @endif

    <div class="order-box">

        <div class="order-row">

            <span class="order-label">
                {{ $isSplit ? 'Order Numbers' : 'Order Number' }}
            </span>

            <span class="order-value">
                @if($isSplit)
                    {{ $checkoutOrders->map(fn ($o) => '#' . $o->id)->implode(', ') }}
                @else
                    {{ $order['id'] ?? 'N/A' }}
                @endif
            </span>

        </div>


        <div class="order-row">

            <span class="order-label">
                Customer
            </span>

            <span class="order-value">
                {{ $order['buyer_name'] ?? 'Buyer' }}
            </span>

        </div>


        <div class="order-row">

            <span class="order-label">
                Payment Method
            </span>

            <span class="order-value">
                {{ $order['payment'] ?? 'N/A' }}
            </span>

        </div>


        <div class="order-row">

            <span class="order-label">
                Status
            </span>

            <span class="order-value">
                {{ $order['status'] ?? 'Pending' }}
            </span>

        </div>


        <div class="order-row">

            <span class="order-label">
                Order Total
            </span>

            <span class="order-value total">
                ₱{{ number_format($isSplit ? $checkoutOrders->sum('total_amount') : ($order['total'] ?? 0), 2) }}
            </span>

        </div>

    </div>


    <div class="buttons">

        <a
            href="{{ route('buyer.orders') }}"
            class="btn btn-primary"
        >
            <i class="bi bi-box-seam-fill"></i> View My Orders
        </a>


        <a
            href="{{ route('products') }}"
            class="btn btn-secondary"
        >
            <i class="bi bi-bag-fill"></i> Continue Shopping
        </a>

    </div>

</div>
</main>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>

</html>