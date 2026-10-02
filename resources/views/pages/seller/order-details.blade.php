<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Order Details — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/pages/seller-order-details.css') }}">

</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="orders" :user="$user" />


    <!-- =========================================
         MAIN CONTENT
    ========================================== -->

    <main class="main-content">

        <div class="container">

            <!-- BACK -->

            <a href="{{ route('seller.orders') }}" class="back">
                ← Back to Seller Orders
            </a>

            <a
                href="{{ route('seller.order.waybill', $order['id']) }}"
                target="_blank"
                class="back"
                style="float:right;"
            >
                <i class="bi bi-printer-fill"></i> Print Waybill
            </a>


            <!-- ALERTS -->

            @if(session('success'))

                <div class="success">
                    {{ session('success') }}
                </div>

            @endif


            @if(session('error'))

                <div class="error">
                    {{ session('error') }}
                </div>

            @endif


            <!-- =========================================
                 ORDER INFORMATION
            ========================================== -->

            <div class="card">

                <h1>
                    Seller Order Details
                </h1>

                <div class="order-id">
                    Order #{{ $order['id'] ?? 'N/A' }}
                </div>

                <x-status-pill :status="$order['status'] ?? 'Pending'" />


                <div class="info-grid">

                    <!-- BUYER -->

                    <div class="info-box">

                        <div class="label">
                            Buyer
                        </div>

                        <div class="value">
                            {{ $order['buyer_name'] ?? 'Buyer' }}
                        </div>

                    </div>


                    <!-- ORDER DATE -->

                    <div class="info-box">

                        <div class="label">
                            Order Date
                        </div>

                        <div class="value">
                            {{ $order['date'] ?? 'N/A' }}
                        </div>

                    </div>


                    <!-- PHONE -->

                    <div class="info-box">

                        <div class="label">
                            Phone
                        </div>

                        <div class="value">
                            {{ $order['phone'] ?? 'N/A' }}
                        </div>

                    </div>


                    <!-- PAYMENT -->

                    <div class="info-box">

                        <div class="label">
                            Payment Method
                        </div>

                        <div class="value">
                            {{ $order['payment'] ?? 'N/A' }}
                        </div>

                    </div>

                </div>

                @php
                    $riskCount = ($buyerHistory['cancelled'] ?? 0) + ($buyerHistory['refused'] ?? 0);
                    $isRisky = ($buyerHistory['recent'] ?? 0) >= 2;
                @endphp

                <div class="buyer-history {{ $isRisky ? 'is-risky' : '' }}">
                    <i class="bi {{ $isRisky ? 'bi-exclamation-triangle-fill' : 'bi-person-check-fill' }}"></i>
                    <div>
                        <strong>Buyer history:</strong>
                        {{ $buyerHistory['orders'] ?? 0 }} {{ Str::plural('order', $buyerHistory['orders'] ?? 0) }} ·
                        {{ $buyerHistory['cancelled'] ?? 0 }} cancelled ·
                        {{ $buyerHistory['refused'] ?? 0 }} refused on delivery

                        @if($isRisky)
                            <div class="buyer-history-sub">
                                {{ $buyerHistory['recent'] }} cancellations/refusals in the last 30 days — consider confirming with the buyer before preparing a COD order.
                            </div>
                        @elseif($riskCount === 0)
                            <div class="buyer-history-sub">No cancelled or refused orders.</div>
                        @endif
                    </div>
                </div>

                <style>
                    .buyer-history { display: flex; gap: 10px; align-items: flex-start; margin-top: 18px; padding: 12px 14px; border-radius: 12px; background: #eefaf3; color: #1f6b3a; font-size: 13px; line-height: 1.5; }
                    .buyer-history i { margin-top: 2px; }
                    .buyer-history.is-risky { background: #fff4e5; color: #8a5a00; }
                    .buyer-history-sub { font-size: 12px; opacity: 0.9; margin-top: 2px; }
                </style>

            </div>


            <!-- =========================================
                 PRODUCTS
            ========================================== -->

            <div class="card">

                <h2>
                    Your Products in This Order
                </h2>

                @forelse(($order['items'] ?? []) as $item)

                    <div class="item">

                        <div>

                            <div class="item-name">
                                {{ $item['name'] ?? 'Product' }}
                                @if(!empty($item['variation_label']))
                                    <span style="color:var(--muted);">({{ $item['variation_label'] }})</span>
                                @endif
                            </div>

                            <div class="item-info">

                                Quantity:
                                {{ $item['quantity'] ?? 0 }}

                                • ₱{{ number_format((float)($item['price'] ?? 0), 2) }}

                                each

                            </div>

                        </div>

                        <div class="price">

                            ₱{{ number_format((float)($item['subtotal'] ?? 0), 2) }}

                        </div>

                    </div>

                @empty

                    <div class="info-box">
                        <div class="value">
                            No products found in this order.
                        </div>
                    </div>

                @endforelse


                <!-- SELLER TOTAL -->

                <div class="summary total">

                    <span>
                        Your Sales
                    </span>

                    <span>
                        ₱{{ number_format((float)($order['seller_total'] ?? 0), 2) }}
                    </span>

                </div>

            </div>


            <!-- =========================================
                 DELIVERY INFORMATION
            ========================================== -->

            <div class="card">

                <h2>
                    Delivery Information
                </h2>

                <div class="info-box delivery-box">

                    <div class="label">
                        Delivery Address
                    </div>

                    <div class="value">
                        {{ $order['address'] ?? 'N/A' }}
                    </div>

                </div>

            </div>


            <!-- =========================================
                 SHIPMENT / COURIER TRACKING
            ========================================== -->

            <div class="card">

                <h2>
                    Shipment Tracking
                </h2>

                <div class="info-grid">

                    <div class="info-box">
                        <div class="label">Courier Status</div>
                        <div class="value"><x-status-pill :status="$order['status'] ?? 'Pending'" /></div>
                    </div>

                    <div class="info-box">
                        <div class="label">Assigned Courier</div>
                        <div class="value">
                            @if($assignedRider)
                                {{ $assignedRider->name }}
                            @elseif(in_array($order['status'] ?? '', ['Ready for Pickup']))
                                Waiting for a courier to accept this delivery…
                            @else
                                Not yet applicable
                            @endif
                        </div>
                    </div>

                    @if($assignedRider)
                        <div class="info-box">
                            <div class="label">Courier Contact</div>
                            <div class="value">{{ $assignedRider->phone ?? 'N/A' }}</div>
                        </div>
                    @endif

                </div>

                @if(!empty($order['rider_id']))

                    @if(!empty($order['seller_confirmed_pickup_at']))

                        <div class="success" style="margin-top:16px;">
                            <i class="bi bi-check-circle-fill"></i> You confirmed handing this order over to the rider on
                            {{ \Illuminate\Support\Carbon::parse($order['seller_confirmed_pickup_at'])->format('M d, Y • h:i A') }}.
                        </div>

                    @elseif(in_array($order['status'] ?? '', ['Assigned', 'Picked Up'], true))

                        <form method="POST" action="{{ route('seller.order.confirm-pickup', $order['id']) }}" style="margin-top:16px;">
                            @csrf
                            <button type="submit">
                                <i class="bi bi-check-circle-fill"></i> Confirm Rider Pickup
                            </button>
                        </form>

                    @endif

                @endif

            </div>


            <!-- =========================================
                 UPDATE STATUS
            ========================================== -->

            <div class="card">

                <h2>
                    Update Order Status
                </h2>

                @php $currentStatus = $order['status'] ?? ''; @endphp

                @if(!in_array($currentStatus, ['Pending', 'Processing'], true))

                    <p style="color:#8d6c62; font-size:13px; line-height:1.6;">
                        <i class="bi bi-info-circle"></i>
                        This order is <strong>{{ $currentStatus }}</strong>.
                        @if(in_array($currentStatus, ['Ready for Pickup', 'Assigned'], true))
                            Keep the parcel ready — a rider will collect it. There's nothing more for you to update.
                        @else
                            From here the rider, Sorting Center or buyer moves it along — there's nothing for you to update.
                        @endif
                    </p>

                @else

                <form
                    method="POST"
                    action="{{ route('seller.order.status', $order['id']) }}"
                >

                    @csrf

                    <select name="status" id="sellerStatusSelect" required onchange="document.getElementById('cancellationReasonBox').style.display = this.value === 'Cancelled' ? 'block' : 'none';">

                        <option value="">
                            Select Status
                        </option>

                        @if($currentStatus === 'Pending')
                            <option value="Processing">
                                Processing
                            </option>
                        @else
                            <option value="Ready for Pickup">
                                Ready for Pickup
                            </option>
                        @endif

                        <option value="Cancelled">
                            Cancelled (e.g. out of stock)
                        </option>

                    </select>

                    <div id="cancellationReasonBox" style="display:none; margin-top:10px;">
                        <label style="font-size:13px; font-weight:700; display:block; margin-bottom:6px;">
                            Reason for cancellation
                        </label>
                        <textarea name="cancellation_reason" placeholder="e.g. Item is out of stock" style="width:100%; min-height:70px; padding:10px; border:1px solid #f0ddd6; border-radius:8px; font-family:inherit; font-size:13px;"></textarea>
                    </div>

                    <button type="submit">
                        Update Order Status
                    </button>

                </form>

                @endif

            </div>

        </div>


        <!-- FOOTER -->

        <footer>

            © 2026 <strong>BoomBuy</strong>
            <br>
            Seller Order Management

        </footer>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>