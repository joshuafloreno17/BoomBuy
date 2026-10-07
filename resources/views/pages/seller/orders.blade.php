<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Seller Orders — BoomBuy'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-orders.css') }}">

</head>

<body>

<div class="layout">

    {{-- =========================
         SIDEBAR
    ========================== --}}

    <x-layout.seller-sidebar active="orders" :user="$user" />


    {{-- =========================
         MAIN CONTENT
    ========================== --}}

    <main class="main-content">

        <div class="container">

            {{-- PAGE HEADER --}}

            <x-seller-page-head title="Orders" subtitle="View and manage orders containing your products." />


            {{-- ALERTS --}}

            @if(session('success'))

                <div class="alert success">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                </div>

            @endif


            @if(session('error'))

                <div class="alert error">
                    {{ session('error') }}
                </div>

            @endif


            {{-- SUMMARY (whole shop, not just the current search/tab) --}}

            @if($summary['total'] > 0)

                <div class="summary-grid">

                    <div class="summary-card">
                        <div class="summary-label">
                            Total Orders
                        </div>

                        <div class="summary-value">
                            {{ $summary['total'] }}
                        </div>
                    </div>

                    <div class="summary-card orange">
                        <div class="summary-label">
                            To Process
                        </div>

                        <div class="summary-value">
                            {{ $summary['to_process'] }}
                        </div>
                    </div>

                    <div class="summary-card green">
                        <div class="summary-label">
                            Your Sales (delivered)
                        </div>

                        <div class="summary-value">
                            ₱{{ number_format($summary['sales'], 2) }}
                        </div>
                    </div>

                </div>

            @endif


            {{-- SEARCH --}}

            <form method="GET" action="{{ route('seller.orders') }}" class="orders-toolbar" data-live-search data-live-target="#liveClear, #liveTabs, #liveResults">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div class="orders-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search order #, buyer name, phone or product" aria-label="Search orders">
                </div>

                <button type="submit" class="orders-search-btn"><i class="bi bi-search"></i> Search</button>

                <span id="liveClear" style="display:contents;">
                @if($search !== '')
                    <a href="{{ route('seller.orders', ['tab' => $tab]) }}" class="orders-search-btn light"><i class="bi bi-x-lg"></i> Clear</a>
                @endif
                </span>
            </form>


            {{-- TABS --}}

            <div class="order-tabs" id="liveTabs">
                @foreach($tabs as $key => $definition)
                    <a
                        href="{{ route('seller.orders', array_filter(['tab' => $key, 'q' => $search])) }}"
                        class="order-tab {{ $tab === $key ? 'active' : '' }} {{ in_array($key, ['to-process', 'returns'], true) && $tabCounts[$key] > 0 ? 'has-work' : '' }}"
                    >
                        {{ $definition['label'] }}<span class="count">{{ $tabCounts[$key] }}</span>
                    </a>
                @endforeach
            </div>


            <div id="liveResults">

            @if($tab !== 'returns')

            {{-- =========================
                 NO ORDERS
            ========================== --}}

            @if($orders->isEmpty())

                <div class="empty">

                    <div class="empty-icon">
                        <i class="bi bi-cart-fill"></i>
                    </div>

                    <h2>{{ $search !== '' ? 'No matching orders' : ($summary['total'] > 0 ? 'Nothing here' : 'No Orders Yet') }}</h2>

                    <p>
                        @if($search !== '')
                            Nothing matches "{{ $search }}" in this tab.
                        @elseif($summary['total'] > 0)
                            There are no orders in this tab right now.
                        @else
                            Orders containing your products will appear here.
                        @endif
                    </p>

                </div>

            @else


                {{-- =========================
                     ORDER LIST
                ========================== --}}

                @foreach($orders as $order)

                    <div class="order-card">

                        {{-- ORDER HEADER --}}

                        <div class="order-header">

                            <div>

                                <div class="order-id">
                                    Order #{{ $order->id }}
                                </div>

                                <div class="date">
                                    {{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y • h:i A') }}
                                </div>

                            </div>

              <div>

    <x-status-pill :status="$order->status" />

    @if(!empty($order->buyer_received_at))

        <div class="received-badge">
            <i class="bi bi-check-circle-fill"></i> Received by Buyer
        </div>

    @endif

    @if($order->status === 'Returned to Seller' && !empty($order->restocked_at))

        <div class="received-badge">
            <i class="bi bi-check-circle-fill"></i> Restocked
        </div>

    @endif

</div>

                        </div>


                        {{-- CUSTOMER --}}

                        @if($order->shipping_name)

                            <div class="customer">

                                <div class="customer-title">
                                    Customer
                                </div>

                                <div class="customer-name">
                                    {{ $order->shipping_name }}
                                </div>

                                @if($order->shipping_phone)

                                    <div class="customer-phone">
                                        <i class="bi bi-telephone-fill"></i> {{ $order->shipping_phone }}
                                    </div>

                                @endif

                            </div>

                        @endif


                        {{-- PRODUCTS --}}

                        <div class="items">

                            @foreach($order->items as $item)

                                @php
                                    $subtotal =
                                        (float) $item->price *
                                        (int) $item->quantity;
                                @endphp

                                <div class="item">

                                    <div>

                                        <div class="item-name">
                                            {{ $item->product_name }}
                                            @if(!empty($item->variation_label))
                                                <span style="color:#95827b; font-weight:600;">({{ $item->variation_label }})</span>
                                            @endif
                                        </div>

                                        <div class="item-info">

                                            Quantity:
                                            {{ $item->quantity }}

                                            • ₱{{ number_format((float) $item->price, 2) }}
                                            each

                                        </div>

                                    </div>

                                    <div class="subtotal">

                                        ₱{{ number_format($subtotal, 2) }}

                                    </div>

                                </div>

                            @endforeach

                        </div>


                        {{-- ORDER FOOTER --}}

                        <div class="order-footer">

                            <div>

                                <div class="total-label">
                                    {{ in_array($order->status, ['Cancelled', 'Returned to Seller'], true) ? 'Order Value (not a sale)' : ($order->status === 'Delivered' ? 'Your Sales' : 'Order Value') }}
                                </div>

                                <div class="total">
                                    ₱{{ number_format((float) $order->seller_total, 2) }}
                                </div>

                            </div>

                           <div style="display:flex; align-items:center; gap:10px;">

    <a
        href="{{ route('seller.order.details', ['id' => $order->id]) }}"
        class="view-btn"
    >
        View Order →
    </a>

@if($order->status === 'Returned to Seller' && empty($order->restocked_at))

    <form method="POST" action="{{ route('seller.order.restock', ['id' => $order->id]) }}">
        @csrf
        <button type="submit" class="view-btn" data-confirm="Mark this order as restocked? This will add the returned items back to your inventory." data-confirm-ok="Mark Restocked">
            <i class="bi bi-box-seam-fill"></i> Mark as Restocked
        </button>
    </form>

@endif

                           </div>

                        </div>

                    </div>

                @endforeach

                @include('partials.simple-pager', ['paginator' => $orders])

            @endif

            @else

            {{-- =========================
                 RETURN / REFUND REQUESTS (own tab)
            ========================== --}}

            @if($returnRequests->isEmpty())

                <div class="empty">
                    <div class="empty-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
                    <h2>{{ $search !== '' ? 'No matching requests' : 'No Return Requests' }}</h2>
                    <p>Return and refund requests from buyers will appear here.</p>
                </div>

            @else

                @php
                    $pendingReturns = $returnRequests->filter(function ($r) {
                        return strtolower($r->status) === 'pending';
                    })->count();
                @endphp

                <div class="section-heading">

                    <h2>Return / Refund Requests</h2>

                    @if($pendingReturns > 0)
                        <span class="count-pill">
                            {{ $pendingReturns }} pending
                        </span>
                    @endif

                </div>

                @foreach($returnRequests as $request)

                    @php
                        $statusLower = strtolower($request->status);
                        $statusClass = match($statusLower) {
                            'approved' => 'status-approved',
                            'rejected' => 'status-rejected',
                            'returned' => 'status-returned',
                            'refund_processing', 'refund_pending', 'dropped_off', 'in_transit', 'ready_for_seller' => 'status-refund-processing',
                            'disputed' => 'status-pending',
                            'completed' => 'status-completed',
                            default => 'status-pending',
                        };
                    @endphp

                    <div class="return-card">

                        <div class="return-header">

                            <div>

                                <div class="return-id">
                                    {{ $request->request_type }} #{{ $request->id }}
                                    <span class="order-ref">
                                        — Order #{{ $request->order_id }}
                                        @if(!empty($request->product_name))
                                            • {{ $request->product_name }}
                                        @endif
                                    </span>
                                </div>

                                <div class="date">
                                    Requested {{ \Carbon\Carbon::parse($request->created_at)->format('M d, Y • h:i A') }}
                                </div>

                            </div>

                            <div class="status {{ $statusClass }}">
                                {{ \App\Services\ReturnRefundService::LABELS[$request->status] ?? ucwords(str_replace('_', ' ', $request->status)) }}
                            </div>

                        </div>

                        @if(!empty($request->shipping_name))

                            <div class="customer" style="margin-top:18px;">

                                <div class="customer-title">
                                    Customer
                                </div>

                                <div class="customer-name">
                                    {{ $request->shipping_name }}
                                </div>

                                @if($request->shipping_phone)

                                    <div class="customer-phone">
                                        <i class="bi bi-telephone-fill"></i> {{ $request->shipping_phone }}
                                    </div>

                                @endif

                            </div>

                        @endif

                        <div class="return-body">

                            <div class="return-block">

                                <div class="return-block-title">
                                    Reason for {{ $request->request_type }}
                                </div>

                                <div class="return-block-text">
                                    {{ $request->reason ?? 'No reason provided.' }}
                                </div>

                            </div>

                            @if(!empty($request->message))

                                <div class="return-block">

                                    <div class="return-block-title">
                                        Buyer Note
                                    </div>

                                    <div class="return-block-text">
                                        {{ $request->message }}
                                    </div>

                                </div>

                            @endif

                            @if(!empty($request->evidence))

                                <div class="return-block">

                                    <div class="return-block-title">
                                        Photo Evidence
                                    </div>

                                    <a href="{{ route('return-refund.evidence', $request->id) }}" target="_blank">
                                        <img src="{{ route('return-refund.evidence', $request->id) }}" alt="Return evidence" style="max-width:160px; border-radius:8px; margin-top:6px;">
                                    </a>

                                </div>

                            @endif

                            @if(!empty($request->seller_note))

                                <div class="return-block">

                                    <div class="return-block-title">
                                        Your Note (Seller)
                                    </div>

                                    <div class="return-block-text seller-note-box">
                                        {{ $request->seller_note }}
                                    </div>

                                </div>

                            @endif

                        </div>

                        @if($statusLower === 'pending')

                            <div class="return-footer">

                                {{-- APPROVE --}}
                                <form
                                    action="{{ route('seller.return-refund.approve', ['id' => $request->id]) }}"
                                    method="POST"
                                >
                                    @csrf

                                    <button type="submit" class="btn btn-approve">
                                        <i class="bi bi-check-circle-fill"></i> Approve
                                    </button>
                                </form>

                                {{-- REJECT (opens modal) --}}
                                <button
                                    type="button"
                                    class="btn btn-reject"
                                    onclick="openRejectModal({{ $request->id }})"
                                >
                                    <i class="bi bi-x-circle-fill"></i> Reject
                                </button>

                            </div>

                        @elseif(in_array($statusLower, ['approved', 'dropped_off', 'in_transit', 'ready_for_seller', 'disputed', 'refund_pending', 'refund_processing'], true))

                            <div class="return-footer" style="display:block;">
                                <div class="return-block-text" style="font-size:12.5px;">
                                    @switch($statusLower)
                                        @case('approved')
                                            <i class="bi bi-hourglass-split"></i> Waiting for the buyer to drop the item at their Sorting Center.
                                            @break
                                        @case('dropped_off')
                                        @case('in_transit')
                                            <i class="bi bi-truck"></i> The item is on its way to your Sorting Center.
                                            @break
                                        @case('ready_for_seller')
                                            <i class="bi bi-shop"></i> <strong>Collect it at your Sorting Center</strong> — give them return number #{{ $request->id }}.
                                            @break
                                        @case('disputed')
                                            <i class="bi bi-shield-check"></i> The buyer asked BoomBuy to review this. BoomBuy will decide and tell you.
                                            @break
                                        @default
                                            <i class="bi bi-cash-coin"></i> BoomBuy is sending the buyer their refund of ₱{{ number_format((float) $request->refund_amount, 2) }}. It is not taken from your payout — it was never part of it.
                                    @endswitch
                                </div>
                            </div>

                        @endif

                        {{-- The item came back: put it back in stock (once), unless it is damaged. --}}
                        @if($request->request_type === 'Return' && ($request->returned_at || $statusLower === 'returned') && empty($request->restocked_at))

                            <div class="return-footer">
                                <form action="{{ route('seller.return-refund.restock', ['id' => $request->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-approve">
                                        <i class="bi bi-box-seam-fill"></i> Add back to stock
                                    </button>
                                </form>
                                <span style="font-size:12px; color:#6b6570;">Skip this if it came back damaged.</span>
                            </div>

                        @elseif(!empty($request->restocked_at))

                            <div class="return-footer" style="font-size:12px; color:#0a6f66; font-weight:700;">
                                <i class="bi bi-check-circle-fill"></i> Added back to stock
                            </div>
                        @endif

                    </div>

                @endforeach

            @endif

            @endif

            </div>{{-- #liveResults --}}

        </div>

    </main>

</div>


{{-- =========================
     REJECT MODAL (shared)
========================== --}}

<div class="modal-overlay" id="rejectModalOverlay" data-base-url="{{ url('seller/return-refund') }}">

    <div class="modal-box">

        <h3>Reject Return Request</h3>

        <p>
            Please add a note explaining why this return/refund request
            is being rejected. The buyer will see this note.
        </p>

        <form
            id="rejectForm"
            method="POST"
            action=""
        >
            @csrf

            <label for="seller_note">Seller Note</label>

            <textarea
                name="seller_note"
                id="seller_note"
                placeholder="e.g. Item does not meet return policy conditions..."
                required
            ></textarea>

            <div class="modal-actions">

                <button
                    type="button"
                    class="btn btn-cancel"
                    onclick="closeRejectModal()"
                >
                    Cancel
                </button>

                <button type="submit" class="btn btn-reject" style="background:#d6362b; color:#fff; border:none;">
                    Confirm Reject
                </button>

            </div>

        </form>

    </div>

</div>


<script src="{{ asset('js/pages/seller-orders.js') }}"></script>

    @include('partials.live-search')
    @include('partials.pwa-register')

</body>
</html>