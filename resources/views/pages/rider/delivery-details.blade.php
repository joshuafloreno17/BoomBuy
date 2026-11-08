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
<div class="details-wrap">

{{-- PHONES: back + order header, the "Deliver to" card (call, chat, map) and the cash to collect.
     They replace the title bar and the info card below, which stay on bigger screens. --}}
@php
    $mStatus = $delivery['status'] ?? 'Pending';
    $mPhone = preg_replace('/[^0-9+]/', '', (string) ($delivery['phone'] ?? ''));
    $mAddress = trim((string) ($delivery['address'] ?? ''));
    $mItems = collect($delivery['items'] ?? [])->sum(fn ($item) => (int) ($item['quantity'] ?? 1));
    $mCod = \App\Support\CodPolicy::isCod($delivery['payment_method'] ?? '');
@endphp
<div class="dd-m dd-head">
    <a href="{{ route('rider.deliveries') }}" class="dd-back" aria-label="Back to My Deliveries"><i class="bi bi-chevron-left"></i></a>
    <span class="dd-head-text">
        <strong>Order #{{ $delivery['id'] ?? 'N/A' }}</strong>
        <span>{{ $mItems }} {{ $mItems === 1 ? 'item' : 'items' }} · {{ $mCod ? 'COD' : ($delivery['payment'] ?? 'Paid') }}</span>
    </span>
    <x-status-pill :status="$mStatus" />
</div>

<section class="dd-m dd-to" aria-label="Deliver to">
    <span class="dd-to-label">Deliver to</span>
    <strong class="dd-to-name">{{ $delivery['buyer_name'] ?? 'Customer' }}</strong>
    <span class="dd-to-address">{{ $mAddress !== '' ? $mAddress : 'No address provided' }}</span>
    <span class="dd-to-actions">
        @if($mPhone !== '')
            <a href="tel:{{ $mPhone }}" class="is-main"><i class="bi bi-telephone-fill"></i> Call</a>
        @endif
        @if(!empty($delivery['buyer_id']))
            <a href="{{ route('messages.thread', $delivery['buyer_id']) }}"><i class="bi bi-chat-dots-fill"></i> Chat</a>
        @endif
        @if($mAddress !== '')
            <a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($mAddress) }}" target="_blank" rel="noopener"><i class="bi bi-geo-alt-fill"></i> Map</a>
        @endif
    </span>
</section>

@if($mCod && in_array($mStatus, ['Assigned for Delivery', 'Out for Delivery'], true))
    <div class="dd-m dd-cash">
        <span class="dd-cash-icon" aria-hidden="true"><i class="bi bi-cash"></i></span>
        <span>
            <small>Collect in cash</small>
            <strong>₱{{ number_format((float) ($delivery['total_amount'] ?? 0), 2) }}</strong>
        </span>
    </div>
@endif


<div class="topbar dd-desk">

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


<!-- ORDER INFORMATION (phones: the Deliver to card above) -->

<div class="card dd-desk">

    <div class="header">

        <div class="order-id">

            <i class="bi bi-box-seam-fill"></i> Order #{{ $delivery['id'] ?? 'N/A' }} · {{ \App\Support\Waybill::number((int) ($delivery['id'] ?? 0)) }}

        </div>

        @php
            $currentStatus = $delivery['status'] ?? 'Pending';
            // Which delivery attempt this is (A13): a failed one counts until it is rescheduled.
            $attemptsSoFar = (int) ($delivery['delivery_attempts'] ?? 0);
            $attemptNow = $currentStatus === 'Delivery Failed' ? $attemptsSoFar : $attemptsSoFar + 1;
        @endphp

        <span class="attempt-badge" data-attempt>Attempt {{ max(1, $attemptNow) }} of {{ \App\Services\SortingCenterService::MAX_DELIVERY_ATTEMPTS }}</span>

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

    @if($currentStatus === 'Assigned for Delivery' || $currentStatus === 'Out for Delivery')

        <div class="card update-box">

            <h3>
                <i class="bi bi-arrow-repeat"></i> Update Delivery Status
            </h3>

            <form
                method="POST"
                action="{{ route('rider.delivery.status', $delivery['id']) }}"
                enctype="multipart/form-data"
            >

                @csrf

                {{-- Phones: big buttons that pick the status in the menu below (hidden there). --}}
                <div class="dd-m dd-choose" role="group" aria-label="New status">
                    @if($currentStatus === 'Assigned for Delivery')
                        <button type="button" class="is-go" data-pick-status="Out for Delivery"><i class="bi bi-truck"></i> Out for Delivery</button>
                    @else
                        <button type="button" class="is-fail" data-pick-status="Delivery Failed">Can’t deliver</button>
                        <button type="button" class="is-done" data-pick-status="Delivered"><i class="bi bi-check-lg"></i> Delivered</button>
                    @endif
                </div>

                <select name="status" id="deliveryStatusSelect" required onchange="document.getElementById('failureReasonBox').style.display = this.value === 'Delivery Failed' ? 'block' : 'none'; var proof = document.getElementById('proofBox'); proof.style.display = this.value === 'Delivered' ? 'block' : 'none'; document.getElementById('deliveryProof').required = this.value === 'Delivered';">

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

                {{-- HAND-OVER: photo, the buyer's code and the cash — a checklist; "Confirm delivered" turns on when all are done. --}}
                @php
                    $hoCod = \App\Support\CodPolicy::isCod($delivery['payment_method'] ?? '');
                    $hoAmount = (float) ($delivery['total_amount'] ?? 0);
                    $hoAskCash = $hoCod && config('app.delivery_checks');
                    $hoBuyer = trim(explode(' ', (string) ($delivery['buyer_name'] ?? 'the buyer'))[0]) ?: 'the buyer';
                @endphp
                <div id="proofBox" class="proof-box handover" style="display:none;" data-ho>
                    <div class="ho-head">
                        <strong>Hand the parcel to {{ $delivery['buyer_name'] ?? 'the buyer' }}</strong>
                        <small data-ho-left>Do the steps below, then confirm.</small>
                    </div>

                    <div class="ho-step" data-ho-step="photo">
                        <span class="ho-n" aria-hidden="true"><i class="bi bi-check-lg"></i><b>1</b></span>
                        <div class="ho-body">
                            <label class="ho-title" for="deliveryProof">Proof of delivery photo</label>
                            <small>The parcel with the buyer, or at their door. JPG, PNG or WEBP, up to 5 MB.</small>
                            <label class="ho-photo" for="deliveryProof">
                                <img data-ho-preview alt="Your delivery photo" hidden>
                                <span data-ho-photo-text><i class="bi bi-camera-fill"></i> Take photo</span>
                            </label>
                            <input type="file" name="delivery_proof" id="deliveryProof" accept="image/*" capture="environment" class="ho-file">
                        </div>
                    </div>

                    @if(!empty($delivery['delivery_otp']))
                        <div class="ho-step" data-ho-step="code">
                            <span class="ho-n" aria-hidden="true"><i class="bi bi-check-lg"></i><b>2</b></span>
                            <div class="ho-body">
                                <label class="ho-title" for="deliveryOtp">Buyer's delivery OTP</label>
                                <small>Ask {{ $hoBuyer }} for the 6-digit code — it's in My Orders on their phone.</small>
                                <div class="ho-code">
                                    <span></span><span></span><span></span><span></span><span></span><span></span>
                                    <input type="text" name="otp" id="deliveryOtp" inputmode="numeric" maxlength="6" pattern="\d{6}" autocomplete="one-time-code" aria-label="6-digit code from the buyer">
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="ho-step" data-ho-step="cash" @if(!$hoAskCash) data-ho-done-already @endif>
                        <span class="ho-n" aria-hidden="true"><i class="bi bi-check-lg"></i><b>{{ !empty($delivery['delivery_otp']) ? 3 : 2 }}</b></span>
                        <div class="ho-body">
                            @if($hoCod)
                                <span class="ho-title">Collect the cash <em>₱{{ number_format($hoAmount, 2) }}</em></span>
                                <small>Cash on Delivery — hand it in at the Sorting Center after.</small>
                                @if($hoAskCash)
                                    <button type="button" class="ho-exact" data-ho-exact="{{ number_format($hoAmount, 2, '.', '') }}">
                                        <span class="ho-box" aria-hidden="true"></span> I collected exactly ₱{{ number_format($hoAmount, 2) }}
                                    </button>
                                    <details class="ho-other">
                                        <summary>Got a different amount? Type it</summary>
                                        <label class="failure-label" for="codAmount">COD amount collected</label>
                                        <input type="number" name="cod_amount" id="codAmount" step="0.01" min="0" placeholder="{{ number_format($hoAmount, 2, '.', '') }}">
                                    </details>
                                @endif
                            @else
                                <span class="ho-title">Nothing to collect</span>
                                <small>Paid online — COD ₱0.00, collect nothing.</small>
                            @endif
                        </div>
                    </div>
                </div>

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

                    // Phones: a big button picks the status, then the submit button says what it will do.
                    (function () {
                        var select = document.getElementById('deliveryStatusSelect');
                        var form = select.form;
                        var submitBtn = function () { return form.querySelector('.update-btn'); };
                        var labels = {
                            'Out for Delivery': 'Confirm: Out for Delivery',
                            'Delivered': 'Confirm delivered',
                            'Delivery Failed': 'Confirm: can’t deliver'
                        };

                        form.querySelectorAll('[data-pick-status]').forEach(function (button) {
                            button.addEventListener('click', function () {
                                select.value = button.dataset.pickStatus;
                                select.dispatchEvent(new Event('change'));
                                form.querySelectorAll('[data-pick-status]').forEach(function (other) {
                                    other.classList.toggle('is-picked', other === button);
                                });
                                form.classList.add('dd-picked');
                                submitBtn().innerHTML = '<i class="bi bi-check2-circle"></i> ' + labels[select.value];
                                if (window.bbHandoverCheck) window.bbHandoverCheck();
                            });
                        });
                    })();

                    // Hand-over checklist: photo preview, the 6 code boxes, one tap for the exact cash,
                    // and "Confirm delivered" only once every step is done.
                    (function () {
                        var box = document.querySelector('[data-ho]');
                        if (!box) return;
                        var select = document.getElementById('deliveryStatusSelect');
                        var form = select.form;
                        var file = document.getElementById('deliveryProof');
                        var otp = document.getElementById('deliveryOtp');
                        var cod = document.getElementById('codAmount');
                        var exact = box.querySelector('[data-ho-exact]');
                        var left = box.querySelector('[data-ho-left]');

                        function step(name) { return box.querySelector('[data-ho-step="' + name + '"]'); }

                        function check() {
                            var steps = {
                                photo: file && file.files && file.files.length > 0,
                                code: !otp || /^\d{6}$/.test(otp.value),
                                cash: !cod || cod.value !== ''
                            };
                            var todo = 0;
                            Object.keys(steps).forEach(function (k) {
                                var el = step(k);
                                if (!el) return;
                                var done = steps[k] || el.hasAttribute('data-ho-done-already');
                                el.classList.toggle('is-done', !!done);
                                if (!done) todo++;
                            });
                            var delivering = select.value === 'Delivered';
                            left.textContent = todo ? todo + ' of ' + box.querySelectorAll('[data-ho-step]').length + ' left — then confirm.' : 'All done — confirm delivered below.';
                            var submit = form.querySelector('.update-btn');
                            if (!submit) return;
                            submit.disabled = delivering && todo > 0;
                            submit.classList.toggle('is-waiting', delivering && todo > 0);
                        }
                        window.bbHandoverCheck = check;

                        file.addEventListener('change', function () {
                            var img = box.querySelector('[data-ho-preview]');
                            var text = box.querySelector('[data-ho-photo-text]');
                            if (file.files && file.files[0]) {
                                img.src = URL.createObjectURL(file.files[0]);
                                img.hidden = false;
                                text.innerHTML = '<i class="bi bi-arrow-repeat"></i> Retake';
                            }
                            check();
                        });

                        if (otp) {
                            var cells = box.querySelectorAll('.ho-code span');
                            otp.addEventListener('input', function () {
                                otp.value = otp.value.replace(/\D/g, '').slice(0, 6);
                                cells.forEach(function (c, i) {
                                    c.textContent = otp.value[i] || '';
                                    c.classList.toggle('is-next', i === otp.value.length);
                                });
                                check();
                            });
                            otp.addEventListener('focus', function () { box.querySelector('.ho-code').classList.add('is-focus'); });
                            otp.addEventListener('blur', function () { box.querySelector('.ho-code').classList.remove('is-focus'); });
                        }

                        if (exact && cod) {
                            exact.addEventListener('click', function () {
                                var on = !exact.classList.contains('is-on');
                                exact.classList.toggle('is-on', on);
                                cod.value = on ? exact.dataset.hoExact : '';
                                check();
                            });
                            cod.addEventListener('input', function () {
                                exact.classList.toggle('is-on', cod.value === exact.dataset.hoExact);
                                check();
                            });
                        }

                        select.addEventListener('change', check);
                        document.addEventListener('DOMContentLoaded', check);
                    })();
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
    class="back-btn dd-desk"
>
    ← Back to My Deliveries
</a>


</div>
</main>

    @include('partials.pwa-register')

</body>
</html>
