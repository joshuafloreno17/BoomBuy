<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Seller Order Details — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-order-details.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/seller-order-details.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-order-flow.css') }}">

</head>

<body>

@php
    $currentStatus = $order['status'] ?? '';
    $waybill = \App\Support\Waybill::number((int) $order['id']);
    $isCod = \App\Support\CodPolicy::isCod($order['payment_method'] ?? '');
    $pickupDay = !empty($order['pickup_date']) ? \Illuminate\Support\Carbon::parse($order['pickup_date'])->format('l, M j') : null;

    // The seller's 5 steps (0-based): what the seller does first, then the trip.
    $stepOf = [
        'Pending' => 0,
        'Confirmed' => 1, 'Preparing' => 1, 'Processing' => 1,
        'Ready for Pickup' => 2, 'Pickup Assigned' => 2,
        'Picked Up' => 3, 'Dropped Off' => 3, 'Received at Branch' => 3, 'To Sorting Center' => 3,
        'At Sorting Center' => 3, 'Sorted' => 3, 'To Branch' => 3, 'At Branch' => 3, 'In Transit' => 3,
        'Assigned for Delivery' => 3, 'Out for Delivery' => 3, 'Delivery Failed' => 3, 'Ready to Collect' => 3,
        'Delivered' => 4, 'Completed' => 4,
    ];
    $stoppedStatus = !isset($stepOf[$currentStatus]);
    $currentStep = $stepOf[$currentStatus] ?? -1;
    $allDone = $currentStatus === 'Completed';

    $unclaimedPickup = $currentStatus === 'Ready for Pickup' && empty($order['rider_id']);
    $canCancel = in_array($currentStatus, \App\Support\OrderStatus::WITH_SELLER, true) || $unclaimedPickup;
    $canConfirmPickup = !empty($order['rider_id']) && in_array($currentStatus, ['Pickup Assigned', 'Picked Up'], true) && empty($order['seller_confirmed_pickup_at']);
    $pickupDays = \App\Services\PickupService::dayChoices();

    // The rider brings the parcel to the branch in the seller's town,
    // else to the Sorting Center of the seller's province.
    $sellerTown = \App\Support\ParcelRoute::sellerLocation((int) $user['id']);
    $dropOffCenter = !empty($order['origin_center_id'])
        ? \App\Models\SortingCenter::find($order['origin_center_id'])
        : \App\Support\ParcelRoute::centerFor($sellerTown['province'], $sellerTown['city']);
    $dropOffCenter = (!empty($order['origin_branch_id']) ? \App\Models\SortingCenter::find($order['origin_branch_id']) : null)
        ?? \App\Support\ParcelRoute::branchFor($dropOffCenter?->id, $sellerTown['province'], $sellerTown['city'])
        ?? $dropOffCenter;
    $centerName = $dropOffCenter->name ?? 'your BoomBuy Sorting Center';

    // Buyer history, shown where the seller decides.
    $riskCount = ($buyerHistory['cancelled'] ?? 0) + ($buyerHistory['refused'] ?? 0);
    $isRisky = ($buyerHistory['recent'] ?? 0) >= 2;

    // What happened so far, in the seller's words, newest first.
    $updates = collect(\App\Support\OrderTimeline::forOrders([(int) $order['id']])->get((int) $order['id']) ?? [])
        ->map(fn ($step) => \App\Support\OrderTimeline::forSeller($step))
        ->reverse()
        ->values();
    $doneAt = function (array $statuses) use ($updates) {
        $step = $updates->first(fn ($s) => in_array($s->status, $statuses, true));
        return $step ? \Illuminate\Support\Carbon::parse($step->at)->format('M j · g:i A') : null;
    };

    $steps = [
        ['title' => 'New order', 'done' => 'New order — accepted', 'doneNote' => ($at = $doneAt(['Confirmed'])) ? 'You accepted it ' . $at : 'Accepted'],
        ['title' => 'Pack the order', 'done' => 'Packed', 'doneNote' => ($at = $doneAt(['Ready for Pickup'])) ? 'Rider pickup booked ' . $at : 'Packed and labelled'],
        ['title' => 'Rider pickup', 'done' => 'Picked up', 'doneNote' => ($assignedRider ? $assignedRider->name . ' picked it up' : 'Picked up') . (($at = $doneAt(['Picked Up'])) ? ' · ' . $at : '')],
        ['title' => 'In transit', 'done' => 'In transit', 'doneNote' => 'Reached the buyer through the Sorting Centers'],
        ['title' => 'Delivered', 'done' => $currentStatus === 'Completed' ? 'Delivered — completed' : 'Delivered', 'doneNote' => $currentStatus === 'Completed' ? 'The buyer confirmed they received it' : 'Waiting for the buyer to confirm'],
    ];

    $declineReasons = [
        'The buyer often cancels orders' => 'Sorry, we can\'t accept this order because of several recent cancellations on your account.',
        'The buyer refused deliveries before' => 'Sorry, we can\'t accept this Cash on Delivery order because earlier deliveries were refused.',
        'Couldn\'t confirm the order with the buyer' => 'Sorry, we couldn\'t confirm this order with you, so we can\'t send it.',
        'The item is damaged or defective' => 'Sorry, we found a defect on the item while checking it, so we can\'t send it.',
    ];
    $cancelReasons = array_slice($declineReasons, 2, null, true);
@endphp

<div class="layout">

    <x-layout.seller-sidebar active="orders" :user="$user" />

    <main class="main-content">

        <div class="container so-wide">

            <x-seller-page-head
                :title="'Order #' . ($order['id'] ?? '')"
                :subtitle="'Placed ' . (!empty($order['date']) ? \Illuminate\Support\Carbon::parse($order['date'])->format('M j, Y · g:i A') : '—') . ' · ' . ($order['buyer_name'] ?? 'Buyer') . ' · ₱' . number_format((float) ($order['seller_total'] ?? 0), 2) . ($isCod ? ' COD' : '')"
                :crumbs="['Orders' => route('seller.orders')]"
            >
                {{-- The waybill (label with QR) stays here at every status, so it can always be reprinted. --}}
                <a href="{{ route('seller.order.label', $order['id']) }}" class="waybill-btn" data-label-panel data-waybill="{{ $waybill }}" data-center="{{ $centerName }}">
                    <i class="bi bi-printer-fill"></i> Print Waybill
                    @if(!empty($order['waybill_printed_at']))
                        <span class="label-printed-chip" title="First printed {{ \Illuminate\Support\Carbon::parse($order['waybill_printed_at'])->format('M j, Y g:i A') }}"><i class="bi bi-check-lg"></i> Printed</span>
                    @endif
                </a>
            </x-seller-page-head>

            @if(session('success'))
                <div class="success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="error">{{ session('error') }}</div>
            @endif

            <div class="so-layout">

                {{-- =========================================
                     THE STEPS: done, now, next
                ========================================== --}}

                <section class="so-steps" aria-label="Order progress">

                    @if($stoppedStatus)

                        <div class="status-stopped">
                            <i class="bi bi-x-octagon-fill"></i>
                            <div>
                                This order is <strong>{{ $currentStatus ?: 'unknown' }}</strong>.
                                @if(!empty($order['cancellation_reason']))
                                    <span>Reason: {{ $order['cancellation_reason'] }}</span>
                                @endif
                            </div>
                        </div>

                    @else

                        <ol class="so-step-list">
                            @foreach($steps as $i => $step)
                                @php
                                    $isDone = $i < $currentStep || ($allDone && $i === 4);
                                    $isNow = !$isDone && $i === $currentStep;
                                @endphp

                                <li @class(['so-step', 'is-done' => $isDone, 'is-now' => $isNow, 'is-ahead' => !$isDone && !$isNow]) @if($isNow) aria-current="step" @endif>
                                    <span class="so-dot" aria-hidden="true">
                                        @if($isDone)
                                            <i class="bi bi-check-lg"></i>
                                        @else
                                            {{ $i + 1 }}
                                        @endif
                                    </span>

                                    <div class="so-step-body">

                                        @if($isDone)

                                            <div class="so-step-row">
                                                <strong>{{ $i + 1 }}. {{ $step['done'] }}</strong>
                                                <span class="so-done-tag">Done</span>
                                            </div>
                                            <small>{{ $step['doneNote'] }}</small>

                                        @elseif($isNow)

                                            <strong class="so-now-title">{{ $i + 1 }}. {{ $i === 0 ? 'New order — accept or decline' : $step['title'] }}</strong>

                                            {{-- ---------- 1. New order ---------- --}}
                                            @if($i === 0)

                                                <p class="so-lead">Check the buyer's history, then decide.</p>

                                                <div @class(['so-history', 'is-risky' => $isRisky])>
                                                    <div class="so-history-head">
                                                        <i class="bi {{ $isRisky ? 'bi-exclamation-triangle-fill' : 'bi-person-check-fill' }}" aria-hidden="true"></i>
                                                        <strong>Buyer history: {{ $order['buyer_name'] ?? 'Buyer' }}</strong>
                                                        @if($isRisky)
                                                            <span class="so-risk-tag">Risky buyer</span>
                                                        @endif
                                                    </div>
                                                    <div class="so-history-stats">
                                                        <div><strong>{{ $buyerHistory['orders'] ?? 0 }}</strong><span>{{ Str::plural('order', $buyerHistory['orders'] ?? 0) }}</span></div>
                                                        <div @class(['is-bad' => ($buyerHistory['cancelled'] ?? 0) > 0])><strong>{{ $buyerHistory['cancelled'] ?? 0 }}</strong><span>cancelled</span></div>
                                                        <div @class(['is-bad' => ($buyerHistory['refused'] ?? 0) > 0])><strong>{{ $buyerHistory['refused'] ?? 0 }}</strong><span>refused on delivery</span></div>
                                                    </div>
                                                    @if($isRisky)
                                                        <p>{{ $buyerHistory['recent'] }} cancellations/refusals in the last 30 days — consider messaging the buyer before accepting{{ $isCod ? ' this COD order' : '' }}.</p>
                                                    @elseif($riskCount === 0)
                                                        <p>No cancelled or refused orders.</p>
                                                    @endif
                                                </div>

                                                <div class="so-actions is-split">
                                                    <form method="POST" action="{{ route('seller.order.status', $order['id']) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="Confirmed">
                                                        <button type="submit" class="so-btn is-primary">
                                                            <i class="bi bi-hand-thumbs-up-fill"></i> Accept Order
                                                        </button>
                                                    </form>
                                                    @if($canCancel)
                                                        <button type="button" class="so-btn is-danger-soft" data-so-toggle="soDecline" aria-expanded="false" aria-controls="soDecline">
                                                            <i class="bi bi-x-circle"></i> Decline Order
                                                        </button>
                                                    @endif
                                                </div>
                                                <p class="so-note"><i class="bi bi-bell"></i> The buyer is told right away either way.</p>
                                                @include('partials.seller-cancel-form')

                                            {{-- ---------- 2. Pack ---------- --}}
                                            @elseif($i === 1)

                                                <ul class="so-checklist">
                                                    @foreach(($order['items'] ?? []) as $item)
                                                        <li><span class="so-box" aria-hidden="true"></span>Pack {{ $item['quantity'] ?? 1 }}× {{ $item['name'] ?? 'Product' }}@if(!empty($item['variation_label'])) ({{ $item['variation_label'] }})@endif</li>
                                                    @endforeach
                                                    <li><span class="so-box" aria-hidden="true"></span>Print and stick the waybill {{ $waybill }}</li>
                                                </ul>

                                                @if($currentStatus === 'Confirmed')
                                                    <div class="so-actions">
                                                        <form method="POST" action="{{ route('seller.order.status', $order['id']) }}">
                                                            @csrf
                                                            <input type="hidden" name="status" value="Preparing">
                                                            <button type="submit" class="so-btn is-primary">
                                                                <i class="bi bi-box-seam"></i> Start Preparing
                                                            </button>
                                                        </form>
                                                        <a href="{{ route('seller.order.label', $order['id']) }}" class="so-btn is-ghost" data-label-panel data-waybill="{{ $waybill }}" data-center="{{ $centerName }}">
                                                            <i class="bi bi-printer-fill"></i> Print Waybill ({{ $waybill }})
                                                        </a>
                                                        @if($canCancel)
                                                            <button type="button" class="so-btn is-danger-soft so-push" data-so-toggle="soDecline" aria-expanded="false" aria-controls="soDecline">
                                                                <i class="bi bi-x-circle"></i> Cancel order
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <p class="so-note"><i class="bi bi-bell"></i> The buyer is told you are now preparing their order.</p>
                                                @else
                                                    {{-- Packed: book a rider to pick it up. --}}
                                                    <form method="POST" action="{{ route('seller.order.pickup', $order['id']) }}" class="so-pickup-form">
                                                        @csrf
                                                        <label for="pickupDate">Pickup day</label>
                                                        <select id="pickupDate" name="pickup_date" required>
                                                            @foreach($pickupDays as $date => $label)
                                                                <option value="{{ $date }}">{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="so-actions">
                                                            <button type="submit" class="so-btn is-primary">
                                                                <i class="bi bi-box-arrow-up"></i> Packed — Mark as Ready for Pickup
                                                            </button>
                                                            <a href="{{ route('seller.order.label', $order['id']) }}" class="so-btn is-ghost" data-label-panel data-waybill="{{ $waybill }}" data-center="{{ $centerName }}">
                                                                <i class="bi bi-printer-fill"></i> Print Waybill ({{ $waybill }})
                                                            </a>
                                                            @if($canCancel)
                                                                <button type="button" class="so-btn is-danger-soft so-push" data-so-toggle="soDecline" aria-expanded="false" aria-controls="soDecline">
                                                                    <i class="bi bi-x-circle"></i> Cancel order
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </form>
                                                    <p class="so-note"><i class="bi bi-bell"></i> Riders in your area are notified, and the first to accept collects it. The rider brings it to {{ $centerName }}.</p>
                                                @endif

                                                @if($canCancel)
                                                    <p class="so-note is-warn">Can't ship it? Cancel now, before the rider is booked — the buyer is told right away.</p>
                                                @endif
                                                @include('partials.seller-cancel-form')

                                            {{-- ---------- 3. Rider pickup ---------- --}}
                                            @elseif($i === 2)

                                                @if($unclaimedPickup)
                                                    <p class="so-lead">Ready for pickup{{ $pickupDay ? ' on ' . $pickupDay : '' }}. Waiting for a rider in your area to accept it — you'll be told who is coming.</p>
                                                @else
                                                    <p class="so-lead">{{ $assignedRider->name ?? 'A rider' }} will pick it up{{ $pickupDay ? ' on ' . $pickupDay : '' }}. Hand over the packed parcel, then confirm the pickup.</p>
                                                @endif

                                                <div class="so-actions">
                                                    @if($canConfirmPickup)
                                                        <form method="POST" action="{{ route('seller.order.confirm-pickup', $order['id']) }}"
                                                              data-confirm="Did you hand order #{{ $order['id'] }} to {{ $assignedRider->name ?? 'the rider' }}?"
                                                              data-confirm-ok="Yes, handed over">
                                                            @csrf
                                                            <button type="submit" class="so-btn is-primary">
                                                                <i class="bi bi-check2-circle"></i> Confirm Rider Pickup
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('seller.order.label', $order['id']) }}" class="so-btn is-ghost" data-label-panel data-waybill="{{ $waybill }}" data-center="{{ $centerName }}">
                                                        <i class="bi bi-printer-fill"></i> Print Waybill ({{ $waybill }})
                                                    </a>
                                                    @if($canCancel)
                                                        <button type="button" class="so-btn is-danger-soft so-push" data-so-toggle="soDecline" aria-expanded="false" aria-controls="soDecline">
                                                            <i class="bi bi-x-circle"></i> Cancel order
                                                        </button>
                                                    @endif
                                                </div>
                                                <p class="so-note"><i class="bi bi-geo-alt"></i> The rider brings it to {{ $centerName }}.</p>
                                                @include('partials.seller-cancel-form')

                                            {{-- ---------- 4. In transit ---------- --}}
                                            @elseif($i === 3)

                                                <p class="so-lead">
                                                    @if($currentStatus === 'Picked Up')
                                                        The rider picked it up and is bringing it to {{ $centerName }}. They'll confirm once it arrives.
                                                    @elseif($currentStatus === 'Dropped Off')
                                                        You dropped this off. Waiting for the Sorting Center to confirm they received it.
                                                    @else
                                                        Now <strong>{{ \App\Support\OrderStatus::label($currentStatus) }}</strong>. The Sorting Centers and the delivery rider take it from here — nothing to do on your side.
                                                    @endif
                                                </p>

                                                @if($canConfirmPickup)
                                                    <form method="POST" action="{{ route('seller.order.confirm-pickup', $order['id']) }}"
                                                          data-confirm="Did you hand order #{{ $order['id'] }} to {{ $assignedRider->name ?? 'the rider' }}?"
                                                          data-confirm-ok="Yes, handed over">
                                                        @csrf
                                                        <button type="submit" class="so-btn is-primary">
                                                            <i class="bi bi-check2-circle"></i> Confirm Rider Pickup
                                                        </button>
                                                    </form>
                                                @endif

                                            {{-- ---------- 5. Delivered ---------- --}}
                                            @else

                                                <p class="so-lead">Delivered. Waiting for the buyer to confirm they received it.</p>

                                            @endif

                                        @else

                                            {{-- Steps still ahead: what will happen there. --}}
                                            <strong>{{ $i + 1 }}. {{ $step['title'] }}</strong>
                                            <small>
                                                @switch($i)
                                                    @case(1)
                                                        Pack it and attach the waybill —
                                                        <a href="{{ route('seller.order.label', $order['id']) }}" data-label-panel data-waybill="{{ $waybill }}" data-center="{{ $centerName }}">Print Waybill ({{ $waybill }})</a>
                                                        @break
                                                    @case(2)
                                                        The rider brings it to {{ $centerName }}.
                                                        @break
                                                    @case(3)
                                                        Through the BoomBuy Sorting Centers to {{ $order['address'] ?? 'the buyer' }}.
                                                        @break
                                                    @default
                                                        {{ $isCod ? 'The rider collects ₱' . number_format((float) ($order['total_amount'] ?? 0), 2) . ' — it\'s released to you after it\'s handed in.' : 'Already paid — it joins your payout after the return window.' }}
                                                @endswitch
                                            </small>

                                        @endif

                                    </div>
                                </li>
                            @endforeach
                        </ol>

                        @if(!empty($order['seller_confirmed_pickup_at']))
                            <p class="so-note"><i class="bi bi-check2-circle"></i> You confirmed the rider pickup on {{ \Illuminate\Support\Carbon::parse($order['seller_confirmed_pickup_at'])->format('M j, g:i A') }}.</p>
                        @endif


                    @endif

                </section>

                {{-- =========================================
                     SIDE: the order, the buyer, riders, updates
                ========================================== --}}

                <aside class="so-side">

                    <section class="so-card">
                        <h2>Your products</h2>

                        @forelse(($order['items'] ?? []) as $item)
                            <div class="so-item">
                                @if(!empty($item['image']))
                                    <img src="{{ $item['image'] }}" alt="" width="48" height="48" loading="lazy">
                                @else
                                    <span class="so-item-ph" aria-hidden="true"><i class="bi bi-bag"></i></span>
                                @endif
                                <div>
                                    <strong>{{ $item['name'] ?? 'Product' }}</strong>
                                    <small>@if(!empty($item['variation_label'])){{ $item['variation_label'] }} · @endif Qty {{ $item['quantity'] ?? 0 }} · ₱{{ number_format((float) ($item['price'] ?? 0), 2) }} each</small>
                                </div>
                                <strong>₱{{ number_format((float) ($item['subtotal'] ?? 0), 2) }}</strong>
                            </div>
                        @empty
                            <p class="so-muted">No products found in this order.</p>
                        @endforelse

                        @if(!empty($order['voucher_code']) && (float) ($order['discount_amount'] ?? 0) > 0)
                            <div class="so-line" data-order-voucher="{{ $order['voucher_code'] }}">
                                <span>Your voucher ({{ $order['voucher_code'] }})</span>
                                <span>−₱{{ number_format((float) $order['discount_amount'], 2) }}</span>
                            </div>
                        @endif

                        @if((float) ($order['platform_discount'] ?? 0) > 0)
                            <div class="so-line">
                                <span>BoomBuy voucher ({{ $order['platform_voucher_code'] }}) <small>· paid by BoomBuy</small></span>
                                <span>−₱{{ number_format((float) $order['platform_discount'], 2) }}</span>
                            </div>
                        @endif

                        <div class="so-line" data-order-shipping>
                            <span>Shipping fee <small>· paid to delivery</small></span>
                            <span>₱{{ number_format((float) ($order['delivery_fee'] ?? 0), 2) }}</span>
                        </div>

                        @if(!empty($order['buyer_note']))
                            <div class="so-line is-note" data-order-note>
                                <span>Note from the buyer</span>
                                <span>{{ $order['buyer_note'] }}</span>
                            </div>
                        @endif

                        <div class="so-line is-total">
                            <span>Your Sales</span>
                            <span>₱{{ number_format((float) ($order['seller_total'] ?? 0), 2) }}</span>
                        </div>
                    </section>

                    <section class="so-card">
                        <h2>Buyer &amp; delivery</h2>
                        <div class="so-kv">
                            <strong>{{ $order['buyer_name'] ?? 'Buyer' }}</strong>
                            <span>{{ $order['phone'] ?? 'N/A' }}</span>
                        </div>
                        <div class="so-kv">
                            <small>Deliver to</small>
                            <span>{{ $order['address'] ?? 'N/A' }}</span>
                        </div>
                        <div class="so-kv">
                            <small>Payment</small>
                            <span>{{ $order['payment'] ?? 'N/A' }}</span>
                        </div>

                        @if($isCod)
                            @php
                                $codState = !empty($order['cod_remitted_at']) ? ['is-done', 'bi-check-circle-fill', 'COD cash received at the Sorting Center — it joins your payout once the return window closes.']
                                    : (!empty($order['cod_collected_at']) ? ['is-wait', 'bi-hourglass-split', 'The rider collected the cash; it\'s released to you once handed in at the Sorting Center.']
                                    : ['is-wait', 'bi-cash-coin', 'Cash on Delivery — paid to the rider when delivered.']);
                            @endphp
                            <div class="cod-state {{ $codState[0] }}"><i class="bi {{ $codState[1] }}"></i> {{ $codState[2] }}</div>
                        @endif

                        @if($currentStep !== 0 || $stoppedStatus)
                            <div class="buyer-history {{ $isRisky ? 'is-risky' : '' }}">
                                <i class="bi {{ $isRisky ? 'bi-exclamation-triangle-fill' : 'bi-person-check-fill' }}"></i>
                                <div>
                                    <strong>Buyer history:</strong>
                                    {{ $buyerHistory['orders'] ?? 0 }} {{ Str::plural('order', $buyerHistory['orders'] ?? 0) }} ·
                                    {{ $buyerHistory['cancelled'] ?? 0 }} cancelled ·
                                    {{ $buyerHistory['refused'] ?? 0 }} refused on delivery
                                </div>
                            </div>
                        @endif
                    </section>

                    <section class="so-card">
                        <h2>Riders</h2>
                        <div class="so-rider">
                            <small>Pickup</small>
                            @if($assignedRider)
                                <span><strong>{{ $assignedRider->name }}</strong> · {{ $assignedRider->phone ?? 'N/A' }}</span>
                            @elseif($unclaimedPickup)
                                <span>Waiting for a rider</span>
                            @elseif(in_array($currentStatus, \App\Support\OrderStatus::WITH_SELLER, true))
                                <span>Not booked yet</span>
                            @else
                                <span>—</span>
                            @endif
                        </div>
                        <div class="so-rider">
                            <small>Delivery</small>
                            @if($deliveryRider)
                                <span><strong>{{ $deliveryRider->name }}</strong> · {{ $deliveryRider->phone ?? 'N/A' }}</span>
                            @else
                                <span>Assigned near the buyer</span>
                            @endif
                        </div>
                    </section>

                    <section class="so-card">
                        <h2>Updates</h2>

                        @if(!empty($order['delivery_proof']))
                            @include('partials.viewer-modal')
                            <a href="{{ route('orders.delivery-proof', $order['id']) }}" class="so-proof" data-viewer
                               data-viewer-badge="Delivered" data-viewer-title="Proof of delivery"
                               data-viewer-text="Photo taken by the rider when the parcel was handed over.">
                                <i class="bi bi-camera-fill"></i> View the delivery photo
                            </a>
                        @endif

                        @php $firstUpdates = $updates->take(4); $moreUpdates = $updates->slice(4); @endphp

                        <ol class="so-updates">
                            @foreach($firstUpdates as $u)
                                <li>
                                    <strong>{{ $u->title }}</strong>
                                    @if($u->detail)<span>{{ $u->detail }}</span>@endif
                                    <time>{{ \Illuminate\Support\Carbon::parse($u->at)->format('M j · g:i A') }}</time>
                                </li>
                            @endforeach
                        </ol>

                        @if($moreUpdates->isNotEmpty())
                            <details class="so-more">
                                <summary>Show all {{ $updates->count() }} updates</summary>
                                <ol class="so-updates">
                                    @foreach($moreUpdates as $u)
                                        <li>
                                            <strong>{{ $u->title }}</strong>
                                            @if($u->detail)<span>{{ $u->detail }}</span>@endif
                                            <time>{{ \Illuminate\Support\Carbon::parse($u->at)->format('M j · g:i A') }}</time>
                                        </li>
                                    @endforeach
                                </ol>
                            </details>
                        @endif
                    </section>

                </aside>

            </div>

        </div>

    </main>

</div>

<script>
(function () {
    // Decline / Cancel: open the reason form; a reason chip fills the message.
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-so-toggle]');
        if (toggle) {
            var box = document.getElementById(toggle.getAttribute('data-so-toggle'));
            if (!box) return;
            box.hidden = !box.hidden;
            document.querySelectorAll('[data-so-toggle="' + box.id + '"][aria-expanded]').forEach(function (b) {
                b.setAttribute('aria-expanded', String(!box.hidden));
            });
            if (!box.hidden) box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        var chip = e.target.closest('[data-so-reason]');
        if (chip) {
            var form = chip.closest('form');
            form.querySelectorAll('.so-chip').forEach(function (c) { c.classList.toggle('is-picked', c === chip); });
            var text = form.querySelector('textarea');
            text.value = chip.getAttribute('data-so-reason');
            text.focus();
        }
    });
})();
</script>

    @include('partials.label-panel')

    @include('partials.pwa-register')

</body>
</html>
