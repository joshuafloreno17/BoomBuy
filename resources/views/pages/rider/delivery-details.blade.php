<!DOCTYPE html>

<html lang="en">
<head>
    @include('partials.head', ['title' => 'Delivery Details — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/rider-sidebar.css') }}">

<link rel="stylesheet" href="{{ vasset('css/views/rider-delivery-details.css') }}">

</head>

<body>

<x-layout.rider-sidebar active="deliveries" :user="$user" />

<main class="main">


<div class="topbar">

    <div>

        <h1>
            Delivery Details
        </h1>

        <p class="subtitle">
            View and update your assigned delivery.
        </p>

    </div>

    <div class="profile">

        <i class="bi bi-bicycle"></i>

        <strong>
            {{ $user['name'] ?? 'Rider' }}
        </strong>

    </div>

</div>


@if(session('success'))

    <div class="alert success">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>

@endif


@if(session('error'))

    <div class="alert error">
        <i class="bi bi-x-circle-fill"></i> {{ session('error') }}
    </div>

@endif


<!-- ORDER INFORMATION -->

<div class="card">

    <div class="header">

        <div class="order-id">

            <i class="bi bi-box-seam-fill"></i> Order #{{ $delivery['id'] ?? 'N/A' }}

        </div>

        @php
            $currentStatus = $delivery['status'] ?? 'Pending';
        @endphp

        <x-status-pill :status="$currentStatus" />

    </div>


    <div class="info">

        <div class="info-row">

            <span class="label">
                Customer
            </span>

            <span class="value">
                {{ $delivery['buyer_name'] ?? 'Customer' }}
            </span>

        </div>


        <div class="info-row">

            <span class="label">
                Phone
            </span>

            <span class="value">
                {{ $delivery['phone'] ?? 'No phone provided' }}
            </span>

        </div>


        <div class="info-row">

            <span class="label">
                Delivery Address
            </span>

            <span class="value">
                {{ $delivery['address'] ?? 'No address provided' }}
            </span>

        </div>


        <div class="info-row">

            <span class="label">
                Payment
            </span>

            <span class="value">
                {{ $delivery['payment'] ?? 'Cash on Delivery' }}
            </span>

        </div>


        <div class="info-row">

            <span class="label">
                Order Date
            </span>

            <span class="value">
                {{ $delivery['date'] ?? 'N/A' }}
            </span>

        </div>


        @if(!empty($delivery['rider_name']))

            <div class="info-row">

                <span class="label">
                    Assigned Rider
                </span>

                <span class="value">
                    {{ $delivery['rider_name'] }}
                </span>

            </div>

        @endif

    </div>

</div>


<!-- ORDER ITEMS -->
<div class="card">

    <div class="items-title">
        <i class="bi bi-cart-fill"></i> Order Items
    </div>

    @if(
        !empty($delivery['items']) &&
        is_array($delivery['items'])
    )

        @foreach($delivery['items'] as $item)

            @php

                $itemPrice = (float) ($item['price'] ?? 0);

                $itemQuantity = (int) ($item['quantity'] ?? 1);

                /*
                |--------------------------------------------------------------------------
                | COMPUTE ITEM SUBTOTAL
                |--------------------------------------------------------------------------
                | Instead of relying on $item['subtotal'],
                | calculate it from price × quantity.
                |--------------------------------------------------------------------------
                */

                $itemSubtotal = $itemPrice * $itemQuantity;

            @endphp

            <div class="item">

                <div>

                    <div class="item-name">
                        {{ $item['name'] ?? 'Product' }}
                    </div>

                    <div class="item-details">

                        ₱{{ number_format($itemPrice, 2) }}

                        ×

                        {{ $itemQuantity }}

                    </div>

                </div>

                <strong>

                    ₱{{ number_format($itemSubtotal, 2) }}

                </strong>

            </div>

        @endforeach

    @else

        <p class="no-items">
            No item details available.
        </p>

    @endif


    <div class="total">

        <span>
            Total
        </span>

        <span>
            ₱{{ number_format($delivery['total'] ?? 0, 2) }}
        </span>

    </div>

</div>


<!-- UPDATE STATUS -->
@if(
    (
        !empty($delivery['rider_id']) &&
        (string) $delivery['rider_id'] === (string) ($user['id'] ?? '')
    ) || (
        !empty($delivery['delivery_rider_id']) &&
        (string) $delivery['delivery_rider_id'] === (string) ($user['id'] ?? '')
    )
)

    @if($currentStatus === 'Assigned')

        <div class="card update-box">

            <h3>
                <i class="bi bi-box-seam-fill"></i> Confirm Pickup
            </h3>

            <p style="font-size:12px; color:#6b6570; margin-bottom:12px;">
                Proceed to the seller's location, verify the order, then confirm that you have picked it up.
            </p>

            <form
                method="POST"
                action="{{ route('rider.delivery.confirm-pickup', $delivery['id']) }}"
            >

                @csrf

                <button type="submit" class="btn update-btn">
                    <i class="bi bi-check-circle-fill"></i> Confirm Item Pickup
                </button>

            </form>

        </div>

    @endif

    @if($currentStatus === 'Picked Up' || $currentStatus === 'At Sorting Center')

        <div class="card update-box">
            <h3><i class="bi bi-geo-alt-fill"></i> At the Sorting Center</h3>
            <p style="font-size:12px; color:#6b6570;">
                This parcel is being processed by the Sorting Center. You'll be notified once it's assigned to a rider for final delivery.
            </p>
        </div>

    @endif

    @if($currentStatus === 'Assigned for Delivery' || $currentStatus === 'Out for Delivery')

        <div class="card update-box">

            <h3>
                <i class="bi bi-arrow-repeat"></i> Update Delivery Status
            </h3>

            <form
                method="POST"
                action="{{ route('rider.delivery.status', $delivery['id']) }}"
            >

                @csrf

                <select name="status" id="deliveryStatusSelect" required onchange="document.getElementById('failureReasonBox').style.display = this.value === 'Delivery Failed' ? 'block' : 'none';">

                    <option value="">
                        Select new status
                    </option>

                    @if($currentStatus === 'Assigned for Delivery')

                        <option value="Out for Delivery">
                            Out for Delivery
                        </option>

                    @endif

                    @if($currentStatus === 'Out for Delivery')

                        <option value="Delivered">
                            Delivered
                        </option>

                        <option value="Delivery Failed">
                            Delivery Failed
                        </option>

                    @endif

                </select>

                <div id="failureReasonBox" class="failure-box" style="display:none;">
                    <label class="failure-label">
                        Reason for failed delivery
                    </label>

                    <div class="failure-reasons">
                        @foreach(\App\Http\Controllers\RiderController::FAILURE_REASONS as $failureOption)
                            <label class="failure-reason {{ $failureOption === \App\Http\Controllers\RiderController::REFUSED_REASON ? 'is-refused' : '' }}">
                                <input type="radio" name="failure_code" value="{{ $failureOption }}" onchange="syncFailureReason()">
                                <span>{{ $failureOption }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div id="refusedNote" class="failure-note" style="display:none;">
                        <i class="bi bi-arrow-return-left"></i>
                        A refused parcel won't be re-delivered — bring it back to the Sorting Center so it can be returned to the seller.
                    </div>

                    <textarea name="failure_reason" id="failureDetails" placeholder="Add details (optional)…" class="failure-details"></textarea>
                </div>

                <script>
                    function syncFailureReason() {
                        var picked = document.querySelector('input[name="failure_code"]:checked');
                        var value = picked ? picked.value : '';
                        var details = document.getElementById('failureDetails');

                        document.getElementById('refusedNote').style.display =
                            value === @json(\App\Http\Controllers\RiderController::REFUSED_REASON) ? 'flex' : 'none';

                        details.required = value === 'Other';
                        details.placeholder = value === 'Other'
                            ? 'Please describe what happened…'
                            : 'Add details (optional)…';
                    }

                    document.getElementById('deliveryStatusSelect').addEventListener('change', function () {
                        var failing = this.value === 'Delivery Failed';

                        document.querySelectorAll('input[name="failure_code"]').forEach(function (radio) {
                            radio.required = failing;
                        });

                        if (!failing) {
                            document.getElementById('failureDetails').required = false;
                        }
                    });
                </script>

                <link rel="stylesheet" href="{{ vasset('css/views/rider-delivery-details-2.css') }}">

                <button
                    type="submit"
                    class="btn update-btn"
                >
                    <i class="bi bi-arrow-repeat"></i> Update Status
                </button>

            </form>

        </div>

    @endif

@endif


<a
    href="{{ route('rider.deliveries') }}"
    class="back-btn"
>
    ← Back to My Deliveries
</a>


</main>

    @include('partials.pwa-register')

</body>
</html>
