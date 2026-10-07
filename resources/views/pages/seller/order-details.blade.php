<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Seller Order Details — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-order-details.css') }}">

</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="orders" :user="$user" />


    <!-- =========================================
         MAIN CONTENT
    ========================================== -->

    <main class="main-content">

        <div class="container">

            <x-seller-page-head
                :title="'Order #' . ($order['id'] ?? '')"
                :subtitle="'Placed ' . (!empty($order['date']) ? \Illuminate\Support\Carbon::parse($order['date'])->format('M j, Y · g:i A') : '—')"
                :crumbs="['Orders' => route('seller.orders')]"
            >
                <a href="{{ route('seller.order.waybill', $order['id']) }}" target="_blank" class="waybill-btn">
                    <i class="bi bi-printer-fill"></i> Print Waybill
                </a>
            </x-seller-page-head>


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
                            {{ !empty($order['date']) ? \Illuminate\Support\Carbon::parse($order['date'])->format('M j, Y · g:i A') : 'N/A' }}
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

                <link rel="stylesheet" href="{{ vasset('css/views/seller-order-details.css') }}">

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

                @include('partials.order-timeline', [
                    'steps' => \App\Support\OrderTimeline::forOrders([(int) $order['id']])->get((int) $order['id']),
                    'proof' => !empty($order['delivery_proof']) ? route('orders.delivery-proof', $order['id']) : null,
                ])

                @if(\App\Support\CodPolicy::isCod($order['payment_method'] ?? ''))
                    @php
                        $codState = !empty($order['cod_remitted_at']) ? ['is-done', 'bi-check-circle-fill', 'COD cash received at the Sorting Center — it joins your payout once the return window closes.']
                            : (!empty($order['cod_collected_at']) ? ['is-wait', 'bi-hourglass-split', 'The rider collected the cash; it\'s released to you once handed in at the Sorting Center.']
                            : ['is-wait', 'bi-cash-coin', 'Cash on Delivery — paid to the rider when delivered.']);
                    @endphp
                    <div class="cod-state {{ $codState[0] }}"><i class="bi {{ $codState[1] }}"></i> {{ $codState[2] }}</div>
                @endif

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
                            @elseif(in_array($order['status'] ?? '', ['Pending', 'Processing'], true))
                                None — you bring this parcel to the Sorting Center
                            @elseif(in_array($order['status'] ?? '', ['Dropped Off', 'At Sorting Center'], true))
                                The Sorting Center will assign a delivery rider
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

            </div>


            <!-- =========================================
                 UPDATE STATUS
            ========================================== -->

            <div class="card">

                <h2>
                    Update Order Status
                </h2>

                @php
                    $currentStatus = $order['status'] ?? '';

                    // Where each status sits on the seller's 5-step tracker (0-based).
                    $stepOf = [
                        'Pending' => 0,
                        'Processing' => 1,
                        'Dropped Off' => 2,
                        'At Sorting Center' => 3,
                        'In Transit' => 3,
                        'Assigned for Delivery' => 3,
                        'Out for Delivery' => 3,
                        'Delivery Failed' => 3,
                        'Ready to Collect' => 3,
                        'Delivered' => 4,
                        'Completed' => 4,
                    ];
                    $trackSteps = ['Order placed', 'Processing', 'Dropped off', 'In transit', 'Delivered'];

                    // Where this seller brings the parcel: the Sorting Center of their town.
                    $sellerTown = \App\Support\ParcelRoute::sellerLocation((int) $user['id']);
                    $dropOffCenter = !empty($order['origin_center_id'])
                        ? \App\Models\SortingCenter::find($order['origin_center_id'])
                        : \App\Support\ParcelRoute::centerFor($sellerTown['province'], $sellerTown['city']);

                    $stoppedStatus = !isset($stepOf[$currentStatus]);
                    $currentStep = $stepOf[$currentStatus] ?? -1;
                    $allDone = $currentStep === 4;

                    // The one thing the seller can do next, and what the buyer is told.
                    $nextAction = match ($currentStatus) {
                        'Pending' => ['status' => 'Processing', 'label' => 'Start Processing', 'icon' => 'bi-box-seam', 'note' => 'The buyer will be told you are now preparing their order.'],
                        'Processing' => ['status' => 'Dropped Off', 'label' => 'Dropped Off at ' . ($dropOffCenter->name ?? 'Sorting Center'), 'icon' => 'bi-box-arrow-in-right', 'note' => 'Bring the packed parcel to the Sorting Center, then tap this. The buyer will be told it has shipped, and the Sorting Center will confirm they received it.', 'confirm' => 'Confirm you handed order #' . $order['id'] . ' to ' . ($dropOffCenter->name ?? 'the Sorting Center') . '?'],
                        default => null,
                    };
                @endphp

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

                    <ol class="status-track" aria-label="Order progress">
                        @foreach($trackSteps as $i => $label)
                            @php
                                $state = ($i < $currentStep || ($allDone && $i === 4)) ? 'is-done' : ($i === $currentStep ? 'is-current' : '');
                            @endphp
                            <li class="{{ $state }}" @if($i === $currentStep) aria-current="step" @endif>
                                <span class="status-track-dot">
                                    @if($state === 'is-done')
                                        <i class="bi bi-check-lg"></i>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </span>
                                <span class="status-track-label">{{ $label }}</span>
                            </li>
                        @endforeach
                    </ol>

                    @if(in_array($currentStatus, ['Pending', 'Processing'], true))
                        <div class="dropoff-box">
                            <i class="bi bi-geo-alt-fill"></i>
                            <div>
                                <strong>Drop off at: {{ $dropOffCenter->name ?? 'No Sorting Center in your area yet' }}</strong>
                                <span>
                                    @if($dropOffCenter)
                                        {{ $dropOffCenter->address ?: $dropOffCenter->town }}
                                    @else
                                        BoomBuy hasn't opened a Sorting Center near your shop's town yet — contact support before dropping this off.
                                    @endif
                                </span>
                                <a href="{{ route('seller.order.label', $order['id']) }}" target="_blank" rel="noopener" class="dropoff-label">
                                    <i class="bi bi-printer-fill"></i> Print shipping label ({{ \App\Support\Waybill::number((int) $order['id']) }})
                                </a>
                            </div>
                        </div>
                    @endif

                    @if($nextAction)

                        <form
                            method="POST"
                            action="{{ route('seller.order.status', $order['id']) }}"
                            @if(!empty($nextAction['confirm']))
                                data-confirm="{{ $nextAction['confirm'] }}"
                                data-confirm-ok="Yes, Dropped Off"
                            @endif
                        >
                            @csrf
                            <input type="hidden" name="status" value="{{ $nextAction['status'] }}">
                            <button type="submit">
                                <i class="bi {{ $nextAction['icon'] }}"></i> {{ $nextAction['label'] }}
                            </button>
                            <p class="status-next-note">
                                <i class="bi bi-bell"></i> {{ $nextAction['note'] }}
                            </p>
                        </form>

                        <details class="status-cancel">
                            <summary>Can't fulfil this order? Cancel it</summary>

                            <form
                                method="POST"
                                action="{{ route('seller.order.status', $order['id']) }}"
                                data-confirm="Cancel order #{{ $order['id'] }}? The buyer will be notified with your reason and the stock will be returned."
                                data-confirm-ok="Cancel Order"
                                data-confirm-danger
                            >
                                @csrf
                                <input type="hidden" name="status" value="Cancelled">
                                <label for="cancellationReason">Reason for cancellation</label>
                                <textarea id="cancellationReason" name="cancellation_reason" placeholder="e.g. Item is out of stock" required></textarea>
                                <button type="submit" class="btn-danger">
                                    <i class="bi bi-x-circle"></i> Cancel Order
                                </button>
                            </form>
                        </details>

                    @else

                        <p class="status-next-note">
                            <i class="bi bi-info-circle"></i>
                            @if($allDone)
                                This order has been delivered. Nothing left for you to do.
                            @elseif($currentStatus === 'Dropped Off')
                                You dropped this off. Waiting for the Sorting Center to confirm they received it — from there they send it to the buyer.
                            @else
                                Currently <strong>{{ $currentStatus }}</strong>. The Sorting Center and delivery rider move it along from here.
                            @endif
                        </p>

                    @endif

                @endif

            </div>

        </div>




    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>