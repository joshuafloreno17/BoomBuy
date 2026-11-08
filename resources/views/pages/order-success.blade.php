<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Order Successful — BoomBuy', 'fonts' => true])

    <link rel="stylesheet" href="{{ vasset('css/views/order-success.css') }}">

</head>

<body>

@include('partials.buyer-navbar', ['activeNav' => 'orders'])

@php
    $checkoutOrders = collect($checkoutOrders ?? []);
    $isSplit = $checkoutOrders->count() > 1;
    $isPickup = !empty($order['pickup_center']);
    $isCod = \App\Support\CodPolicy::isCod((string) ($order['payment'] ?? ''));
    $seller = $order['seller'] ?? null;
    $route = $order['route'] ?? [];
    $total = $isSplit ? $checkoutOrders->sum('total_amount') : ($order['total'] ?? 0);
    $firstName = trim(explode(' ', (string) ($order['buyer_name'] ?? ''))[0] ?? '');
@endphp

<main class="success-wrap">

    {{-- LEFT: the confirmation, the items and the order details --}}
    <section class="success-card">

        <div class="success-head">
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <div>
                <small>BoomBuy Order {{ $isSplit ? $checkoutOrders->map(fn ($o) => '#' . $o->id)->implode(', ') : '#' . ($order['id'] ?? '') }}</small>
                <h1>Order placed successfully!</h1>
                <p>
                    Thank you{{ $firstName !== '' ? ', ' . $firstName : '' }}.
                    @if($seller && !$isSplit)
                        {{ $seller['shop'] }} has your order.
                    @else
                        Your order has been received and is now being processed.
                    @endif
                </p>
            </div>
        </div>

        @if($isSplit)
            <p class="split-note">
                Your items come from {{ $checkoutOrders->count() }} different sellers, so they were placed as
                {{ $checkoutOrders->count() }} separate orders — each seller ships their own parcel.
            </p>
        @endif

        @if(!empty($order['items']))
            <ul class="success-items">
                @foreach($order['items'] as $item)
                    <li>
                        <span class="item-photo">
                            @if(!empty($item['image_url']))
                                <img src="{{ $item['image_url'] }}" alt="" loading="lazy">
                            @else
                                <i class="bi bi-bag"></i>
                            @endif
                        </span>
                        <span class="item-info">
                            <strong>{{ $item['product_name'] }}</strong>
                            <small>{{ trim(($item['variation_label'] ? $item['variation_label'] . ' · ' : '') . 'Qty ' . $item['quantity']) }}</small>
                        </span>
                        <strong class="item-price">₱{{ number_format((float) $item['price'] * (int) $item['quantity'], 2) }}</strong>
                    </li>
                @endforeach
            </ul>
        @endif

        <dl class="order-box">
            <div class="order-row">
                <dt>Payment</dt>
                <dd>{{ $order['payment'] ?? 'N/A' }}</dd>
            </div>
            <div class="order-row">
                <dt>Status</dt>
                <dd>{{ \App\Support\OrderStatus::label($order['status'] ?? 'Pending') }}</dd>
            </div>

            @if($isPickup)
                <div class="order-row">
                    <dt>Pick up at</dt>
                    <dd>
                        {{ $order['pickup_center']->name }}
                        <small>{{ $order['pickup_center']->address ?: $order['pickup_center']->town }}</small>
                    </dd>
                </div>
            @else
                <div class="order-row">
                    <dt>Deliver to</dt>
                    <dd>{{ $order['address'] ?? '—' }}</dd>
                </div>
            @endif

            @if(!empty($order['pickup_code']))
                <div class="order-row">
                    <dt>Pickup code</dt>
                    <dd class="pickup-code">
                        {{ $order['pickup_code'] }}
                        <small>Give it to the staff when you collect. Keep it private.</small>
                    </dd>
                </div>
            @endif

            <div class="order-row">
                <dt>Order total</dt>
                <dd class="total">₱{{ number_format($total, 2) }}</dd>
            </div>
        </dl>

        <div class="buttons">
            @if($isSplit)
                <a href="{{ route('buyer.orders') }}" class="btn btn-primary">
                    <i class="bi bi-box-seam-fill"></i> View My Orders
                </a>
            @else
                <a href="{{ route('buyer.order.track', $order['id']) }}" class="btn btn-primary">
                    <i class="bi bi-truck"></i> Track Package
                </a>
            @endif
            <a href="{{ route('products') }}" class="btn btn-secondary">
                <i class="bi bi-bag-fill"></i> Continue Shopping
            </a>
        </div>

    </section>

    {{-- RIGHT: what happens next, and a way to ask the seller --}}
    <aside class="success-side">

        <section class="next-card">
            <h2>What happens next</h2>
            <p class="next-eta">Estimated arrival <strong>{{ $order['eta'] ?? '—' }}</strong></p>

            <ol class="next-steps">
                <li class="is-now">
                    <span class="step-dot">1</span>
                    <div>
                        <strong>Seller confirms &amp; packs</strong>
                        <small>{{ $seller && !$isSplit ? $seller['shop'] . ($seller['town'] ? ', ' . $seller['town'] : '') : 'Each seller packs their own parcel' }}</small>
                    </div>
                </li>
                <li>
                    <span class="step-dot">2</span>
                    <div>
                        <strong>Rider picks it up</strong>
                        <small>{{ !empty($route['origin']) && !$isSplit ? 'To ' . $route['origin'] : 'To the seller\'s Sorting Center' }}</small>
                    </div>
                </li>
                <li>
                    <span class="step-dot">3</span>
                    <div>
                        <strong>On its way to you</strong>
                        <small>
                            @if($isPickup)
                                To {{ $order['pickup_center']->name }}
                            @elseif(!empty($route['branch']))
                                Via {{ $route['destination'] }}, then {{ $route['branch'] }}
                            @elseif(!empty($route['destination']))
                                Via {{ $route['destination'] }}
                            @else
                                Through the BoomBuy Sorting Centers
                            @endif
                        </small>
                    </div>
                </li>
                <li>
                    <span class="step-dot">4</span>
                    <div>
                        @if($isPickup)
                            <strong>Ready to collect</strong>
                            <small>We'll notify you — bring your pickup code{{ $isCod ? ' and ₱' . number_format($total, 2) : '' }}.</small>
                        @else
                            <strong>Delivered to your door</strong>
                            <small>Give the rider your delivery OTP{{ $isCod ? ' and pay ₱' . number_format($total, 2) : '' }}.</small>
                        @endif
                    </div>
                </li>
            </ol>
        </section>

        @if($seller && !$isSplit)
            <section class="ask-card">
                <div>
                    <strong>Questions about your order?</strong>
                    <small>Chat with {{ $seller['shop'] }}</small>
                </div>
                <a href="{{ route('messages.thread', $seller['id']) }}" class="ask-btn">Message seller</a>
            </section>
        @endif

    </aside>

</main>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>

</html>
