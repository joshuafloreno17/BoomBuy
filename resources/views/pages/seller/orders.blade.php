<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Orders — BoomBuy</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            background: #fbf7f6;
            color: #1f2937;
        }

        .navbar {
            background: white;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ebe6e5;
        }

        .brand {
            font-family: 'Baloo 2', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #111827;
            text-decoration: none;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .seller-name {
            font-weight: 600;
        }

        .logout button {
            border: none;
            background: #ef4444;
            color: white;
            padding: 9px 15px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #f34f1d;
            text-decoration: none;
            font-weight: 700;
        }

        h1,
        h2,
        h3 {
            font-family: 'Baloo 2', sans-serif;
        }

        h1 {
            font-size: 34px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #816f6a;
            margin-bottom: 30px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .empty {
            background: white;
            padding: 50px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .06);
        }

        .order-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .06);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ebe6e5;
        }

        .order-id {
            font-size: 20px;
            font-weight: 800;
        }

        .date {
            color: #816f6a;
            font-size: 14px;
            margin-top: 5px;
        }

        .status {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            background: #fef3c7;
            color: #926f0e;
        }

        .item {
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .item-info {
            color: #816f6a;
            font-size: 14px;
        }

        .subtotal {
            margin-top: 6px;
            font-weight: 700;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #ebe6e5;
        }

        .total-label {
            font-size: 13px;
            color: #816f6a;
        }

        .total {
            font-size: 20px;
            font-weight: 800;
            margin-top: 3px;
        }

        .view-btn {
            background: #111827;
            color: white;
            text-decoration: none;
            padding: 11px 18px;
            border-radius: 10px;
            font-weight: 700;
            display: inline-block;
        }

        .customer {
            margin-top: 18px;
            padding: 14px;
            background: #fbf9f9;
            border-radius: 10px;
        }

        .customer-title {
            font-size: 13px;
            color: #816f6a;
            margin-bottom: 5px;
        }

        .customer-name {
            font-weight: 700;
        }

        /* =========================================
           RETURN / REFUND
           ========================================= */

        .return-section {
            margin-top: 22px;
            border-top: 1px solid #ebe6e5;
            padding-top: 20px;
        }

        .return-title {
            font-size: 20px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .return-card {
            background: #fff7f2;
            border: 1px solid #ffd9c7;
            border-radius: 14px;
            padding: 18px;
            margin-top: 12px;
        }

        .return-card.pending {
            border-left: 5px solid #f59e0b;
        }

        .return-card.approved {
            border-left: 5px solid #22c55e;
            background: #f0fdf4;
        }

        .return-card.rejected {
            border-left: 5px solid #ef4444;
            background: #fef2f2;
        }

        .return-card.returned {
            border-left: 5px solid #3b82f6;
            background: #eff6ff;
        }

        .return-card.refund_processing {
            border-left: 5px solid #8b5cf6;
            background: #f5f3ff;
        }

        .return-card.completed {
            border-left: 5px solid #16a34a;
            background: #f0fdf4;
        }

        .return-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
        }

        .request-type {
            font-size: 16px;
            font-weight: 800;
        }

        .request-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .request-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .request-status.approved {
            background: #dcfce7;
            color: #166534;
        }

        .request-status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .request-status.returned {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .request-status.refund_processing {
            background: #ede9fe;
            color: #6d28d9;
        }

        .request-status.completed {
            background: #dcfce7;
            color: #166534;
        }

        .return-info {
            font-size: 14px;
            line-height: 1.7;
            color: #4b5563;
        }

        .return-info strong {
            color: #1f2937;
        }

        .seller-note {
            margin-top: 10px;
            padding: 10px 12px;
            background: white;
            border-radius: 9px;
            font-size: 13px;
        }

        .return-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .approve-btn,
        .reject-btn,
        .returned-btn,
        .refund-btn,
        .complete-btn {
            border: none;
            padding: 10px 16px;
            border-radius: 10px;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .approve-btn {
            background: #16a34a;
        }

        .reject-btn {
            background: #ef4444;
        }

        .returned-btn {
            background: #2563eb;
        }

        .refund-btn {
            background: #7c3aed;
        }

        .complete-btn {
            background: #059669;
        }

        .status-description {
            margin-top: 10px;
            font-size: 13px;
            color: #6b7280;
        }

        @media (max-width: 700px) {

            .navbar {
                padding: 16px 4%;
            }

            .nav-right {
                gap: 10px;
            }

            .seller-name {
                font-size: 13px;
            }

            .container {
                width: 94%;
                margin-top: 25px;
            }

            .order-header,
            .order-footer,
            .return-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .return-actions {
                width: 100%;
                flex-direction: column;
            }

            .approve-btn,
            .reject-btn,
            .returned-btn,
            .refund-btn,
            .complete-btn {
                width: 100%;
            }
        }

        button {
            transition: transform .15s ease, box-shadow .15s ease;
        }

        button:hover,
        .view-btn:hover {
            transform: translateY(-1px);
        }

        ::selection {
            background: #ffd7c2;
            color: #7c1a00;
        }
    </style>
</head>

<body>

<nav class="navbar">

    <a href="{{ route('home') }}" class="brand">
        BoomBuy
    </a>

    <div class="nav-right">

        <span class="seller-name">
            Seller: {{ $user['name'] ?? 'Seller' }}
        </span>

        <form action="{{ route('logout') }}" method="POST" class="logout">
            @csrf

            <button type="submit">
                Logout
            </button>
        </form>

    </div>

</nav>


<div class="container">

    <a href="{{ route('seller.dashboard') }}" class="back">
        ← Back to Seller Dashboard
    </a>

    <h1>Seller Orders</h1>

    <p class="subtitle">
        View and manage orders containing your products.
    </p>


    {{-- SUCCESS MESSAGE --}}

    @if(session('success'))
        <div class="success">
            ✓ {{ session('success') }}
        </div>
    @endif


    {{-- ERROR MESSAGE --}}

    @if(session('error'))
        <div class="error">
            {{ session('error') }}
        </div>
    @endif


    {{-- =====================================================
         GET SELLER RETURN / REFUND REQUESTS
         ===================================================== --}}

    @php

        $sellerReturnRequests = DB::table('return_refund_requests')
            ->where('seller_id', $user['id'] ?? 0)
            ->orderByDesc('created_at')
            ->get();

    @endphp


    {{-- =====================================================
         RETURN / REFUND REQUESTS
         ===================================================== --}}

    @if($sellerReturnRequests->count() > 0)

        <div class="return-section">

            <h2 class="return-title">
                ↩️ Return / Refund Requests
            </h2>

            @foreach($sellerReturnRequests as $request)

                @php
                    $requestedItem = DB::table('order_items')
                        ->where('id', $request->order_item_id)
                        ->first();
                @endphp


                <div class="return-card {{ $request->status }}">

                    <div class="return-top">

                        <div class="request-type">

                            @if($request->request_type === 'Return')
                                📦 Return Request
                            @else
                                💰 Refund Request
                            @endif

                        </div>

                        <div class="request-status {{ $request->status }}">
                            {{ $request->status }}
                        </div>

                    </div>


                    <div class="return-info">

                        <div>
                            <strong>Order:</strong>
                            #{{ $request->order_id }}
                        </div>

                        <div>
                            <strong>Product:</strong>
                            {{ $requestedItem->product_name ?? 'Product' }}
                        </div>

                        <div>
                            <strong>Quantity:</strong>
                            {{ $requestedItem->quantity ?? 1 }}
                        </div>

                        <div>
                            <strong>Reason:</strong>
                            {{ $request->reason }}
                        </div>

                        @if(!empty($request->message))

                            <div>
                                <strong>Buyer Message:</strong>
                                {{ $request->message }}
                            </div>

                        @endif

                        <div>
                            <strong>Amount:</strong>
                            ₱{{ number_format((float) $request->refund_amount, 2) }}
                        </div>

                        <div>
                            <strong>Requested:</strong>
                            {{ \Carbon\Carbon::parse($request->created_at)->format('M d, Y • h:i A') }}
                        </div>

                    </div>


                    {{-- =================================================
                         SELLER ACTIONS
                         ================================================= --}}

                    @if($request->status === 'pending')

                        <div class="return-actions">

                            <form
                                method="POST"
                                action="{{ route('seller.return-refund.approve', $request->id) }}"
                                onsubmit="return confirm('Approve this return/refund request?');"
                            >

                                @csrf

                                <button type="submit" class="approve-btn">
                                    ✓ Approve Request
                                </button>

                            </form>


                            <form
                                method="POST"
                                action="{{ route('seller.return-refund.reject', $request->id) }}"
                                onsubmit="return confirm('Reject this return/refund request?');"
                            >

                                @csrf

                                <button type="submit" class="reject-btn">
                                    ✕ Reject Request
                                </button>

                            </form>

                        </div>


                    @elseif($request->status === 'approved')

                        @if($request->request_type === 'Return')

                            <div class="status-description">
                                The return request has been approved. Wait for the item to be returned.
                            </div>

                            <div class="return-actions">

                                <form
                                    method="POST"
                                    action="{{ route('seller.return-refund.returned', $request->id) }}"
                                    onsubmit="return confirm('Mark this item as returned?');"
                                >

                                    @csrf

                                    <button type="submit" class="returned-btn">
                                        📦 Mark as Returned
                                    </button>

                                </form>

                            </div>

                        @elseif($request->request_type === 'Refund')

                            <div class="status-description">
                                The refund request has been approved and is ready for processing.
                            </div>

                            <div class="return-actions">

                                <form
                                    method="POST"
                                    action="{{ route('seller.return-refund.processing', $request->id) }}"
                                    onsubmit="return confirm('Start processing this refund?');"
                                >

                                    @csrf

                                    <button type="submit" class="refund-btn">
                                        💸 Start Refund
                                    </button>

                                </form>

                            </div>

                        @endif


                    @elseif($request->status === 'refund_processing')

                        <div class="status-description">
                            The refund is currently being processed.
                        </div>

                        <div class="return-actions">

                            <form
                                method="POST"
                                action="{{ route('seller.return-refund.complete', $request->id) }}"
                                onsubmit="return confirm('Mark this refund as completed?');"
                            >

                                @csrf

                                <button type="submit" class="complete-btn">
                                    ✅ Complete Refund
                                </button>

                            </form>

                        </div>


                    @elseif($request->status === 'returned')

                        <div class="seller-note">
                            📦 This item has been marked as returned.
                        </div>


                    @elseif($request->status === 'completed')

                        <div class="seller-note">
                            ✅ This refund has been completed.
                        </div>


                    @elseif($request->status === 'rejected')

                        <div class="seller-note">
                            ✕ This request has been rejected.
                        </div>

                    @endif


                    {{-- SELLER NOTE --}}

                    @if(!empty($request->seller_note))

                        <div class="seller-note">
                            <strong>Seller Note:</strong>
                            {{ $request->seller_note }}
                        </div>

                    @endif

                </div>

            @endforeach

        </div>

    @endif


    {{-- =====================================================
         ORDERS
         ===================================================== --}}

    @if($orders->isEmpty())

        <div class="empty">

            <h2>No Orders Yet</h2>

            <p style="margin-top: 10px; color: #816f6a;">
                Orders containing your products will appear here.
            </p>

        </div>

    @else

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


                    <div class="status">
                        {{ $order->status }}
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

                            <div style="margin-top: 4px; color: #816f6a;">
                                📞 {{ $order->shipping_phone }}
                            </div>

                        @endif

                    </div>

                @endif


                {{-- SELLER PRODUCTS --}}

                @foreach($order->items as $item)

                    @php
                        $subtotal = (float) $item->price * (int) $item->quantity;
                    @endphp

                    <div class="item">

                        <div class="item-name">
                            {{ $item->product_name }}
                        </div>

                        <div class="item-info">

                            Quantity:
                            {{ $item->quantity }}

                            • ₱{{ number_format((float) $item->price, 2) }}

                            each

                        </div>

                        <div class="subtotal">

                            Subtotal:
                            ₱{{ number_format($subtotal, 2) }}

                        </div>

                    </div>

                @endforeach


                {{-- FOOTER --}}

                <div class="order-footer">

                    <div>

                        <div class="total-label">
                            Your Sales
                        </div>

                        <div class="total">
                            ₱{{ number_format((float) $order->seller_total, 2) }}
                        </div>

                    </div>


                    <a
                        href="{{ route('seller.order.details', ['id' => $order->id]) }}"
                        class="view-btn"
                    >
                        View Order
                    </a>

                </div>


                {{-- =================================================
                     RETURN / REFUND FOR THIS ORDER
                     ================================================= --}}

                @php

                    $orderReturnRequests = $sellerReturnRequests
                        ->where('order_id', $order->id);

                @endphp


                @if($orderReturnRequests->count() > 0)

                    <div class="return-section">

                        <h3 class="return-title">
                            ↩️ Return / Refund for this Order
                        </h3>


                        @foreach($orderReturnRequests as $orderReturnRequest)

                            <div class="return-card {{ $orderReturnRequest->status }}">

                                <div class="return-top">

                                    <div class="request-type">

                                        @if($orderReturnRequest->request_type === 'Return')
                                            📦 Return
                                        @else
                                            💰 Refund
                                        @endif

                                    </div>

                                    <div class="request-status {{ $orderReturnRequest->status }}">
                                        {{ $orderReturnRequest->status }}
                                    </div>

                                </div>


                                <div class="return-info">

                                    <div>
                                        <strong>Reason:</strong>
                                        {{ $orderReturnRequest->reason }}
                                    </div>

                                    @if(!empty($orderReturnRequest->message))

                                        <div>
                                            <strong>Buyer Message:</strong>
                                            {{ $orderReturnRequest->message }}
                                        </div>

                                    @endif

                                    <div>
                                        <strong>Refund Amount:</strong>
                                        ₱{{ number_format((float) $orderReturnRequest->refund_amount, 2) }}
                                    </div>

                                </div>


                                {{-- PENDING --}}

                                @if($orderReturnRequest->status === 'pending')

                                    <div class="return-actions">

                                        <form
                                            method="POST"
                                            action="{{ route('seller.return-refund.approve', $orderReturnRequest->id) }}"
                                            onsubmit="return confirm('Approve this request?');"
                                        >

                                            @csrf

                                            <button
                                                type="submit"
                                                class="approve-btn"
                                            >
                                                ✓ Approve
                                            </button>

                                        </form>


                                        <form
                                            method="POST"
                                            action="{{ route('seller.return-refund.reject', $orderReturnRequest->id) }}"
                                            onsubmit="return confirm('Reject this request?');"
                                        >

                                            @csrf

                                            <button
                                                type="submit"
                                                class="reject-btn"
                                            >
                                                ✕ Reject
                                            </button>

                                        </form>

                                    </div>


                                {{-- APPROVED --}}

                                @elseif($orderReturnRequest->status === 'approved')

                                    @if($orderReturnRequest->request_type === 'Return')

                                        <div class="return-actions">

                                            <form
                                                method="POST"
                                                action="{{ route('seller.return-refund.returned', $orderReturnRequest->id) }}"
                                                onsubmit="return confirm('Mark this item as returned?');"
                                            >

                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="returned-btn"
                                                >
                                                    📦 Mark as Returned
                                                </button>

                                            </form>

                                        </div>

                                    @elseif($orderReturnRequest->request_type === 'Refund')

                                        <div class="return-actions">

                                            <form
                                                method="POST"
                                                action="{{ route('seller.return-refund.processing', $orderReturnRequest->id) }}"
                                                onsubmit="return confirm('Start processing this refund?');"
                                            >

                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="refund-btn"
                                                >
                                                    💸 Start Refund
                                                </button>

                                            </form>

                                        </div>

                                    @endif


                                {{-- REFUND PROCESSING --}}

                                @elseif($orderReturnRequest->status === 'refund_processing')

                                    <div class="return-actions">

                                        <form
                                            method="POST"
                                            action="{{ route('seller.return-refund.complete', $orderReturnRequest->id) }}"
                                            onsubmit="return confirm('Mark this refund as completed?');"
                                        >

                                            @csrf

                                            <button
                                                type="submit"
                                                class="complete-btn"
                                            >
                                                ✅ Complete Refund
                                            </button>

                                        </form>

                                    </div>


                                {{-- RETURNED --}}

                                @elseif($orderReturnRequest->status === 'returned')

                                    <div class="seller-note">
                                        📦 This item has been marked as returned.
                                    </div>


                                {{-- COMPLETED --}}

                                @elseif($orderReturnRequest->status === 'completed')

                                    <div class="seller-note">
                                        ✅ This refund has been completed.
                                    </div>


                                {{-- REJECTED --}}

                                @elseif($orderReturnRequest->status === 'rejected')

                                    <div class="seller-note">
                                        ✕ This request has been rejected.
                                    </div>

                                @endif


                                @if(!empty($orderReturnRequest->seller_note))

                                    <div class="seller-note">
                                        <strong>Seller Note:</strong>
                                        {{ $orderReturnRequest->seller_note }}
                                    </div>

                                @endif

                            </div>

                        @endforeach

                    </div>

                @endif

            </div>

        @endforeach

    @endif

</div>

</body>
</html>