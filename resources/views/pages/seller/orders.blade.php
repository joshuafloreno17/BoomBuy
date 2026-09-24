<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Orders — BoomBuy</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/pages/seller-orders.css') }}">

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

            <div class="page-top">

                <div class="page-heading">

                    <small>Seller Panel</small>

                    <h1>Orders</h1>

                    <p class="subtitle">
                        View and manage orders containing your products.
                    </p>

                </div>

                <a
                    href="{{ route('seller.dashboard') }}"
                    class="back-btn"
                >
                    ← Back to Dashboard
                </a>

            </div>


            {{-- ALERTS --}}

            @if(session('success'))

                <div class="alert success">
                    ✓ {{ session('success') }}
                </div>

            @endif


            @if(session('error'))

                <div class="alert error">
                    {{ session('error') }}
                </div>

            @endif


            {{-- SUMMARY --}}

            @if(!$orders->isEmpty())

                @php
                    $totalOrders = $orders->count();

                    $pendingOrders = $orders->filter(function ($order) {
                        return in_array(
                            strtolower($order->status),
                            ['pending', 'processing']
                        );
                    })->count();

                    $totalSales = $orders->sum(function ($order) {
                        return (float) $order->seller_total;
                    });
                @endphp

                <div class="summary-grid">

                    <div class="summary-card">
                        <div class="summary-label">
                            Total Orders
                        </div>

                        <div class="summary-value">
                            {{ $totalOrders }}
                        </div>
                    </div>

                    <div class="summary-card orange">
                        <div class="summary-label">
                            Pending Orders
                        </div>

                        <div class="summary-value">
                            {{ $pendingOrders }}
                        </div>
                    </div>

                    <div class="summary-card green">
                        <div class="summary-label">
                            Your Sales
                        </div>

                        <div class="summary-value">
                            ₱{{ number_format($totalSales, 2) }}
                        </div>
                    </div>

                </div>

            @endif


            {{-- =========================
                 NO ORDERS
            ========================== --}}

            @if($orders->isEmpty())

                <div class="empty">

                    <div class="empty-icon">
                        🛒
                    </div>

                    <h2>No Orders Yet</h2>

                    <p>
                        Orders containing your products will appear here.
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

    <div class="status">
        {{ ucwords(str_replace('_', ' ', $order->status)) }}
    </div>

    @if(!empty($order->buyer_received_at))

        <div class="received-badge">
            ✓ Received by Buyer
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
                                        📞 {{ $order->shipping_phone }}
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
                                    Your Sales
                                </div>

                                <div class="total">
                                    ₱{{ number_format((float) $order->seller_total, 2) }}
                                </div>

                            </div>

                           @if(empty($order->buyer_received_at))

    <a
        href="{{ route('seller.order.details', ['id' => $order->id]) }}"
        class="view-btn"
    >
        View Order →
    </a>

@endif

                        </div>

                    </div>

                @endforeach

            @endif


            {{-- =========================
                 RETURN / REFUND REQUESTS
            ========================== --}}

            @if(isset($returnRequests) && !$returnRequests->isEmpty())

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
                            'refund_processing' => 'status-refund-processing',
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
                                {{ ucwords(str_replace('_', ' ', $request->status)) }}
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
                                        📞 {{ $request->shipping_phone }}
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
                                        ✓ Approve
                                    </button>
                                </form>

                                {{-- REJECT (opens modal) --}}
                                <button
                                    type="button"
                                    class="btn btn-reject"
                                    onclick="openRejectModal({{ $request->id }})"
                                >
                                    ✕ Reject
                                </button>

                            </div>

                        @elseif($statusLower === 'approved' && $request->request_type === 'Return')

                            <div class="return-footer">

                                <form
                                    action="{{ route('seller.return-refund.returned', ['id' => $request->id]) }}"
                                    method="POST"
                                >
                                    @csrf

                                    <button type="submit" class="btn btn-approve">
                                        📦 Mark as Returned
                                    </button>
                                </form>

                            </div>

                        @elseif($statusLower === 'approved' && $request->request_type === 'Refund')

                            <div class="return-footer">

                                <form
                                    action="{{ route('seller.return-refund.processing', ['id' => $request->id]) }}"
                                    method="POST"
                                >
                                    @csrf

                                    <button type="submit" class="btn btn-approve">
                                        💳 Start Refund Processing
                                    </button>
                                </form>

                            </div>

                        @elseif($statusLower === 'refund_processing')

                            <div class="return-footer">

                                <form
                                    action="{{ route('seller.return-refund.complete', ['id' => $request->id]) }}"
                                    method="POST"
                                >
                                    @csrf

                                    <button type="submit" class="btn btn-approve">
                                        ✓ Mark Refund Completed
                                    </button>
                                </form>

                            </div>

                        @endif

                    </div>

                @endforeach

            @endif

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

    @include('partials.pwa-register')

</body>
</html>