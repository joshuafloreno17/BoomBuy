<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Orders — BoomBuy</title>

    <!-- Leaflet Map -->
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fbf6f5;
            color: #1f2937;
        }

        /* =========================
           NAVBAR
        ========================= */

        .navbar {
            background: #ffffff;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ebe6e5;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
            color: #f34f1d;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .nav-links a {
            text-decoration: none;
            color: #523d36;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: #f34f1d;
        }

        .user {
            background: #fff3ef;
            color: #f34f1d;
            padding: 9px 15px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* =========================
           CONTAINER
        ========================= */

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 40px auto;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #816f6a;
        }

        /* =========================
           ALERTS
        ========================= */

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* =========================
           ORDERS
        ========================= */

        .orders {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 24px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #ebe6e5;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding-bottom: 18px;
            border-bottom: 1px solid #ebe6e5;
        }

        .order-id {
            font-size: 18px;
            font-weight: 700;
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
            background: #fffaed;
            color: #c2910c;
        }

        .items {
            margin-top: 20px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 700;
        }

        .item-info {
            color: #816f6a;
            font-size: 14px;
            margin-top: 5px;
        }

        .item-price {
            font-weight: 700;
            white-space: nowrap;
        }

        /* =========================
           RETURN STATUS
        ========================= */

        .return-status-box {
            margin-top: 10px;
        }

        .return-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
        }

        .return-pending {
            background: #fff7d6;
            color: #9a7100;
            border: 1px solid #f5df88;
        }

        .return-approved {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .return-rejected {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .return-returned {
            background: #e0f2fe;
            color: #075985;
            border: 1px solid #bae6fd;
        }

        .return-processing {
            background: #ede9fe;
            color: #5b21b6;
            border: 1px solid #ddd6fe;
        }

        .return-completed {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .seller-note {
            margin-top: 8px;
            color: #816f6a;
            font-size: 12px;
        }

        /* =========================
           ORDER INFO
        ========================= */

        .order-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .info-box {
            background: #fbf9f9;
            padding: 15px;
            border-radius: 10px;
        }

        .info-box span {
            display: block;
            color: #816f6a;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .info-box strong {
            font-size: 14px;
        }

        /* =========================
           ORDER FOOTER
        ========================= */

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #ebe6e5;
            gap: 15px;
        }

        .total {
            font-size: 21px;
            font-weight: 800;
            color: #f34f1d;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        /* =========================
           BUTTONS
        ========================= */

        .track-btn,
        .return-btn {
            border: none;
            padding: 11px 18px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            transition: 0.15s ease;
        }

        .track-btn {
            background: #f34f1d;
            color: white;
        }

        .track-btn:hover {
            background: #df4516;
            transform: translateY(-1px);
        }

        .return-btn {
            background: white;
            color: #f34f1d;
            border: 1px solid #f34f1d;
        }

        .return-btn:hover {
            background: #fff3ef;
            transform: translateY(-1px);
        }

        /* =========================
           TRACKING PANEL
        ========================= */

        .tracking-panel {
            display: none;
            margin-top: 25px;
            border-top: 1px solid #ebe6e5;
            padding-top: 25px;
        }

        .tracking-panel.active {
            display: block;
        }

        .tracking-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 18px;
        }

        /* =========================
           MAP
        ========================= */

        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #f0dfda;
            margin-bottom: 25px;
        }

        .map {
            width: 100%;
            height: 100%;
        }

        /* =========================
           RIDER INFO
        ========================= */

        .tracking-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 25px;
        }

        .tracking-box {
            background: #fcf9f8;
            border: 1px solid #f1e5e1;
            border-radius: 12px;
            padding: 18px;
        }

        .tracking-box-title {
            color: #8d6c62;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .tracking-box-value {
            font-size: 16px;
            font-weight: 700;
        }

        .rider-icon {
            font-size: 25px;
            margin-right: 8px;
        }

        /* =========================
           TIMELINE
        ========================= */

        .timeline {
            background: white;
            border: 1px solid #ebe6e5;
            border-radius: 14px;
            padding: 22px;
        }

        .timeline-title {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 22px;
        }

        .timeline-item {
            position: relative;
            display: flex;
            gap: 15px;
            padding-bottom: 22px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-line {
            position: absolute;
            left: 11px;
            top: 25px;
            width: 2px;
            height: calc(100% - 10px);
            background: #ffe3da;
        }

        .timeline-item:last-child .timeline-line {
            display: none;
        }

        .timeline-dot {
            width: 24px;
            height: 24px;
            min-width: 24px;
            border-radius: 50%;
            background: #f34f1d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            z-index: 2;
        }

        .timeline-dot.gray {
            background: #e2d0ca;
        }

        .timeline-content strong {
            display: block;
            margin-bottom: 4px;
        }

        .timeline-content span {
            color: #8d6c62;
            font-size: 13px;
        }

        /* =========================
           RETURN / REFUND PANEL
        ========================= */

        .return-refund-panel {
            display: none;
            margin-top: 25px;
            border-top: 1px solid #ebe6e5;
            padding-top: 25px;
        }

        .return-refund-panel.active {
            display: block;
        }

        .return-card {
            background: #fffaf8;
            border: 1px solid #f2ddd5;
            border-radius: 15px;
            padding: 24px;
        }

        .return-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .return-subtitle {
            color: #816f6a;
            font-size: 14px;
            margin-bottom: 22px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 8px;
            color: #523d36;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            border: 1px solid #dfd1cc;
            background: white;
            border-radius: 10px;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #f34f1d;
            box-shadow: 0 0 0 3px rgba(243, 79, 29, 0.08);
        }

        .form-group textarea {
            min-height: 110px;
            resize: vertical;
        }

        .return-submit {
            border: none;
            background: #f34f1d;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
        }

        .return-submit:hover {
            background: #df4516;
            transform: translateY(-1px);
        }

        .return-note {
            background: #fff3ef;
            border: 1px solid #ffd8ca;
            border-radius: 10px;
            padding: 12px 14px;
            color: #7c4030;
            font-size: 13px;
            margin-bottom: 18px;
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            background: #ffffff;
            padding: 60px 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .empty-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        .empty h2 {
            margin-bottom: 8px;
        }

        .empty p {
            color: #816f6a;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 11px 18px;
            background: #f34f1d;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
        }

        .btn:hover {
            background: #df4516;
        }

        /* =========================
           VIBRANT DESIGN
        ========================= */

        h1,
        h2,
        h3,
        .logo,
        .page-title,
        .order-title,
        .total,
        .return-title {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }

        button {
            transition:
                transform 0.15s ease,
                box-shadow 0.15s ease,
                background 0.15s ease;
        }

        ::selection {
            background: #ffd7c2;
            color: #7c1a00;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 768px) {

            .navbar {
                padding: 15px 5%;
            }

            .nav-links {
                gap: 12px;
                font-size: 13px;
            }

            .container {
                width: 92%;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-info {
                grid-template-columns: 1fr;
            }

            .item {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }

            .order-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .tracking-grid {
                grid-template-columns: 1fr;
            }

            .map-container {
                height: 320px;
            }

            .return-card {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

    <!-- =========================
         NAVBAR
    ========================= -->

    <nav class="navbar">

        <a
            href="{{ route('buyer.dashboard') }}"
            class="logo"
        >
            BoomBuy
        </a>

        <div class="nav-links">

            <a href="{{ route('buyer.dashboard') }}">
                Home
            </a>

            <a href="{{ route('cart') }}">
                🛒 Cart
            </a>

            <a href="{{ route('buyer.orders') }}">
                📦 My Orders
            </a>

            <span class="user">
                {{ $user['name'] ?? 'Buyer' }}
            </span>

        </div>

    </nav>


    <!-- =========================
         MAIN
    ========================= -->

    <div class="container">

        <div class="page-header">

            <h1>
                My Orders
            </h1>

            <p>
                View and track all your BoomBuy orders.
            </p>

        </div>


        <!-- SUCCESS -->

        @if(session('success'))

            <div class="alert alert-success">
                {{ session('success') }}
            </div>

        @endif


        <!-- ERROR -->

        @if(session('error'))

            <div class="alert alert-error">
                {{ session('error') }}
            </div>

        @endif


        @if(empty($orders))

            <div class="empty">

                <div class="empty-icon">
                    📦
                </div>

                <h2>
                    No Orders Yet
                </h2>

                <p>
                    You haven't placed any orders yet.
                </p>

                <a
                    href="{{ route('buyer.dashboard') }}"
                    class="btn"
                >
                    Start Shopping
                </a>

            </div>

        @else

            <div class="orders">

                @foreach($orders as $order)

                    @php

                        $orderStatus =
                            strtolower(
                                $order['status'] ?? 'pending'
                            );

                        $trackingId =
                            'tracking-' .
                            preg_replace(
                                '/[^a-zA-Z0-9]/',
                                '',
                                $order['id'] ?? uniqid()
                            );

                        $returnId =
                            'return-refund-' .
                            preg_replace(
                                '/[^a-zA-Z0-9]/',
                                '',
                                $order['id'] ?? uniqid()
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | GET RETURN / REFUND REQUESTS FOR THIS ORDER
                        |--------------------------------------------------------------------------
                        */

                        $returnRequests = DB::table('return_refund_requests')
                            ->where('order_id', $order['id'] ?? 0)
                            ->orderByDesc('created_at')
                            ->get()
                            ->keyBy('order_item_id');

                    @endphp


                    <div class="order-card">


                        <!-- =========================
                             ORDER HEADER
                        ========================= -->

                        <div class="order-header">

                            <div>

                                <div class="order-id">
                                    Order #{{ $order['id'] ?? 'N/A' }}
                                </div>

                                <div class="date">
                                    {{ $order['date'] ?? 'N/A' }}
                                </div>

                            </div>

                            <div class="status">
                                {{ $order['status'] ?? 'Pending' }}
                            </div>

                        </div>


                        <!-- =========================
                             ITEMS
                        ========================= -->

                        <div class="items">

                            @foreach($order['items'] ?? [] as $item)

                                @php

                                    $itemRequest =
                                        $returnRequests->get($item['id'] ?? null);

                                    $requestStatus =
                                        strtolower(
                                            $itemRequest->status ?? ''
                                        );

                                @endphp


                                <div class="item">

                                    <div style="width:100%;">

                                        <div class="item-name">
                                            {{ $item['product_name'] ?? 'Product' }}
                                        </div>

                                        <div class="item-info">
                                            Quantity:
                                            {{ $item['quantity'] ?? 1 }}
                                        </div>


                                        <!-- =========================
                                             RETURN / REFUND STATUS
                                        ========================= -->

                                        @if($itemRequest)

                                            <div class="return-status-box">

                                                @if($requestStatus === 'pending')

                                                    <span class="return-status return-pending">
                                                        🕐 Return/Refund Pending Review
                                                    </span>

                                                @elseif($requestStatus === 'approved')

                                                    <span class="return-status return-approved">
                                                        ✅ Return/Refund Approved
                                                    </span>

                                                @elseif($requestStatus === 'rejected')

                                                    <span class="return-status return-rejected">
                                                        ❌ Return/Refund Rejected
                                                    </span>

                                                @elseif($requestStatus === 'returned')

                                                    <span class="return-status return-returned">
                                                        📦 Item Returned
                                                    </span>

                                                @elseif($requestStatus === 'refund_processing')

                                                    <span class="return-status return-processing">
                                                        💸 Refund Processing
                                                    </span>

                                                @elseif($requestStatus === 'completed')

                                                    <span class="return-status return-completed">
                                                        ✅ Return/Refund Completed
                                                    </span>

                                                @else

                                                    <span class="return-status return-pending">
                                                        ↩️ Return/Refund Request Submitted
                                                    </span>

                                                @endif


                                                @if(!empty($itemRequest->seller_note))

                                                    <div class="seller-note">
                                                        Seller note:
                                                        {{ $itemRequest->seller_note }}
                                                    </div>

                                                @endif

                                            </div>

                                        @endif

                                    </div>


                                    <div class="item-price">

                                        ₱{{ number_format(
                                            (float)($item['price'] ?? 0) *
                                            (int)($item['quantity'] ?? 1),
                                            2
                                        ) }}

                                    </div>

                                </div>

                            @endforeach

                        </div>


                        <!-- =========================
                             ORDER INFO
                        ========================= -->

                        <div class="order-info">

                            <div class="info-box">

                                <span>
                                    Payment Method
                                </span>

                                <strong>
                                    {{ $order['payment'] ?? 'N/A' }}
                                </strong>

                            </div>


                            <div class="info-box">

                                <span>
                                    Phone
                                </span>

                                <strong>
                                    {{ $order['phone'] ?? 'N/A' }}
                                </strong>

                            </div>


                            <div class="info-box">

                                <span>
                                    Delivery Address
                                </span>

                                <strong>
                                    {{ $order['address'] ?? 'N/A' }}
                                </strong>

                            </div>

                        </div>


                        <!-- =========================
                             ORDER FOOTER
                        ========================= -->

                        <div class="order-footer">

                            <div>

                                <span>
                                    Order Total:
                                </span>

                                <div class="total">

                                    ₱{{ number_format(
                                        (float)($order['total'] ?? 0),
                                        2
                                    ) }}

                                </div>

                            </div>


                            <div class="order-actions">

                                <!-- TRACK ORDER -->

                                <button
                                    type="button"
                                    class="track-btn"
                                    onclick="toggleTracking('{{ $trackingId }}')"
                                >
                                    📍 Track Order
                                </button>


                                <!-- RETURN / REFUND -->

                                @if(($order['status'] ?? '') === 'Delivered')

                                    <button
                                        type="button"
                                        class="return-btn"
                                        onclick="toggleReturnRefund('{{ $returnId }}')"
                                    >
                                        ↩️ Return / Refund
                                    </button>

                                @endif

                            </div>

                        </div>


                        <!-- =========================
                             TRACKING PANEL
                        ========================= -->

                        <div
                            id="{{ $trackingId }}"
                            class="tracking-panel"
                        >

                            <div class="tracking-title">
                                📍 Order Tracking
                            </div>


                            <!-- MAP -->

                            <div class="map-container">

                                <div
                                    id="map-{{ $trackingId }}"
                                    class="map"
                                ></div>

                            </div>


                            <!-- RIDER / DELIVERY INFO -->

                            <div class="tracking-grid">


                                <div class="tracking-box">

                                    <div class="tracking-box-title">
                                        🚴 Delivery Rider
                                    </div>

                                    <div class="tracking-box-value">

                                        @if(!empty($order['rider_name']))

                                            <span class="rider-icon">
                                                🏍️
                                            </span>

                                            {{ $order['rider_name'] }}

                                        @else

                                            <span style="color:#8d6c62;">
                                                Rider not assigned yet
                                            </span>

                                        @endif

                                    </div>

                                </div>


                                <div class="tracking-box">

                                    <div class="tracking-box-title">
                                        📦 Current Status
                                    </div>

                                    <div class="tracking-box-value">
                                        {{ $order['status'] ?? 'Pending' }}
                                    </div>

                                </div>


                                <div class="tracking-box">

                                    <div class="tracking-box-title">
                                        🏠 Delivery Address
                                    </div>

                                    <div class="tracking-box-value">
                                        {{ $order['address'] ?? 'No address' }}
                                    </div>

                                </div>


                                <div class="tracking-box">

                                    <div class="tracking-box-title">
                                        📞 Contact Number
                                    </div>

                                    <div class="tracking-box-value">
                                        {{ $order['phone'] ?? 'N/A' }}
                                    </div>

                                </div>

                            </div>


                            <!-- =========================
                                 DELIVERY TIMELINE
                            ========================= -->

                            <div class="timeline">

                                <div class="timeline-title">
                                    Delivery Progress
                                </div>


                                <!-- ORDER PLACED -->

                                <div class="timeline-item">

                                    <div class="timeline-line"></div>

                                    <div class="timeline-dot">
                                        ✓
                                    </div>

                                    <div class="timeline-content">

                                        <strong>
                                            Order Placed
                                        </strong>

                                        <span>
                                            Your order has been successfully placed.
                                        </span>

                                    </div>

                                </div>


                                <!-- PREPARING -->

                                <div class="timeline-item">

                                    <div class="timeline-line"></div>

                                    <div
                                        class="timeline-dot
                                        {{ in_array(
                                            $orderStatus,
                                            [
                                                'processing',
                                                'ready for pickup',
                                                'picked up',
                                                'out for delivery',
                                                'delivered'
                                            ]
                                        ) ? '' : 'gray' }}"
                                    >
                                        ✓
                                    </div>

                                    <div class="timeline-content">

                                        <strong>
                                            Preparing Order
                                        </strong>

                                        <span>
                                            Seller is preparing your order.
                                        </span>

                                    </div>

                                </div>


                                <!-- READY -->

                                <div class="timeline-item">

                                    <div class="timeline-line"></div>

                                    <div
                                        class="timeline-dot
                                        {{ in_array(
                                            $orderStatus,
                                            [
                                                'ready for pickup',
                                                'picked up',
                                                'out for delivery',
                                                'delivered'
                                            ]
                                        ) ? '' : 'gray' }}"
                                    >
                                        ✓
                                    </div>

                                    <div class="timeline-content">

                                        <strong>
                                            Ready for Pickup
                                        </strong>

                                        <span>
                                            Your order is ready for the rider.
                                        </span>

                                    </div>

                                </div>


                                <!-- PICKED UP -->

                                <div class="timeline-item">

                                    <div class="timeline-line"></div>

                                    <div
                                        class="timeline-dot
                                        {{ in_array(
                                            $orderStatus,
                                            [
                                                'picked up',
                                                'out for delivery',
                                                'delivered'
                                            ]
                                        ) ? '' : 'gray' }}"
                                    >
                                        🚚
                                    </div>

                                    <div class="timeline-content">

                                        <strong>
                                            Picked Up
                                        </strong>

                                        <span>
                                            Rider has picked up your order.
                                        </span>

                                    </div>

                                </div>


                                <!-- OUT FOR DELIVERY -->

                                <div class="timeline-item">

                                    <div class="timeline-line"></div>

                                    <div
                                        class="timeline-dot
                                        {{ in_array(
                                            $orderStatus,
                                            [
                                                'out for delivery',
                                                'delivered'
                                            ]
                                        ) ? '' : 'gray' }}"
                                    >
                                        🏍️
                                    </div>

                                    <div class="timeline-content">

                                        <strong>
                                            Out for Delivery
                                        </strong>

                                        <span>
                                            Your order is on the way.
                                        </span>

                                    </div>

                                </div>


                                <!-- DELIVERED -->

                                <div class="timeline-item">

                                    <div
                                        class="timeline-dot
                                        {{ $orderStatus === 'delivered'
                                            ? ''
                                            : 'gray' }}"
                                    >
                                        ✓
                                    </div>

                                    <div class="timeline-content">

                                        <strong>
                                            Delivered
                                        </strong>

                                        <span>
                                            Your order has been delivered.
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- =========================
                             RETURN / REFUND PANEL
                        ========================= -->

                        @if(($order['status'] ?? '') === 'Delivered')

                            <div
                                id="{{ $returnId }}"
                                class="return-refund-panel"
                            >

                                <div class="return-card">

                                    <div class="return-title">
                                        ↩️ Return / Refund Request
                                    </div>

                                    <div class="return-subtitle">
                                        Request a return or refund for an item from this delivered order.
                                    </div>


                                    <div class="return-note">
                                        💡 Please select the exact product you want to return or refund.
                                        The seller will review your request.
                                    </div>


                                    <!-- =========================
                                         EXISTING REQUESTS
                                    ========================= -->

                                    @if($returnRequests->count() > 0)

                                        <div style="
                                            background:#ffffff;
                                            border:1px solid #eadbd5;
                                            border-radius:12px;
                                            padding:16px;
                                            margin-bottom:20px;
                                        ">

                                            <div style="
                                                font-weight:800;
                                                margin-bottom:12px;
                                                color:#523d36;
                                            ">
                                                📋 Return / Refund History
                                            </div>


                                            @foreach($returnRequests as $requestData)

                                                <div style="
                                                    padding:12px 0;
                                                    border-bottom:1px solid #f0e7e3;
                                                ">

                                                    <div style="
                                                        font-weight:700;
                                                        margin-bottom:5px;
                                                    ">
                                                        {{ $requestData->request_type }}
                                                    </div>

                                                    <div style="
                                                        font-size:13px;
                                                        color:#816f6a;
                                                        margin-bottom:7px;
                                                    ">
                                                        Reason:
                                                        {{ $requestData->reason }}
                                                    </div>


                                                    @if($requestData->status === 'pending')

                                                        <span class="return-status return-pending">
                                                            🕐 Pending Review
                                                        </span>

                                                    @elseif($requestData->status === 'approved')

                                                        <span class="return-status return-approved">
                                                            ✅ Approved by Seller
                                                        </span>

                                                    @elseif($requestData->status === 'rejected')

                                                        <span class="return-status return-rejected">
                                                            ❌ Rejected by Seller
                                                        </span>

                                                    @elseif($requestData->status === 'returned')

                                                        <span class="return-status return-returned">
                                                            📦 Item Returned
                                                        </span>

                                                    @elseif($requestData->status === 'refund_processing')

                                                        <span class="return-status return-processing">
                                                            💸 Refund Processing
                                                        </span>

                                                    @elseif($requestData->status === 'completed')

                                                        <span class="return-status return-completed">
                                                            ✅ Completed
                                                        </span>

                                                    @else

                                                        <span class="return-status return-pending">
                                                            ↩️ {{ ucfirst(str_replace('_', ' ', $requestData->status)) }}
                                                        </span>

                                                    @endif


                                                    @if(!empty($requestData->seller_note))

                                                        <div class="seller-note">
                                                            Seller note:
                                                            {{ $requestData->seller_note }}
                                                        </div>

                                                    @endif

                                                </div>

                                            @endforeach

                                        </div>

                                    @endif


                                    <!-- =========================
                                         RETURN FORM
                                    ========================= -->

                                    <form
                                        method="POST"
                                        action="{{ route('buyer.return-refund.store', $order['id']) }}"
                                    >

                                        @csrf


                                        <!-- PRODUCT -->

                                        <div class="form-group">

                                            <label>
                                                Product
                                            </label>

                                            <select
                                                name="order_item_id"
                                                required
                                            >

                                                <option value="">
                                                    Select product
                                                </option>

                                                @foreach($order['items'] ?? [] as $item)

                                                    @php

                                                        $itemRequest =
                                                            $returnRequests->get($item['id'] ?? null);

                                                    @endphp


                                                    <option
                                                        value="{{ $item['id'] ?? '' }}"
                                                        @if(
                                                            $itemRequest &&
                                                            in_array(
                                                                $itemRequest->status,
                                                                [
                                                                    'pending',
                                                                    'approved',
                                                                    'returned',
                                                                    'refund_processing'
                                                                ]
                                                            )
                                                        )
                                                            disabled
                                                        @endif
                                                    >

                                                        {{ $item['product_name'] ?? 'Product' }}

                                                        —
                                                        ₱{{ number_format(
                                                            (float)($item['price'] ?? 0) *
                                                            (int)($item['quantity'] ?? 1),
                                                            2
                                                        ) }}

                                                        @if(
                                                            $itemRequest &&
                                                            in_array(
                                                                $itemRequest->status,
                                                                [
                                                                    'pending',
                                                                    'approved',
                                                                    'returned',
                                                                    'refund_processing'
                                                                ]
                                                            )
                                                        )
                                                            — Request Already Submitted
                                                        @endif

                                                    </option>

                                                @endforeach

                                            </select>

                                        </div>


                                        <!-- REQUEST TYPE -->

                                        <div class="form-group">

                                            <label>
                                                Request Type
                                            </label>

                                            <select
                                                name="request_type"
                                                required
                                            >

                                                <option value="">
                                                    Select request type
                                                </option>

                                                <option value="Return">
                                                    ↩️ Return Item
                                                </option>

                                                <option value="Refund">
                                                    💸 Refund Only
                                                </option>

                                            </select>

                                        </div>


                                        <!-- REASON -->

                                        <div class="form-group">

                                            <label>
                                                Reason
                                            </label>

                                            <select
                                                name="reason"
                                                required
                                            >

                                                <option value="">
                                                    Select reason
                                                </option>

                                                <option value="Wrong item">
                                                    Wrong item received
                                                </option>

                                                <option value="Damaged item">
                                                    Item arrived damaged
                                                </option>

                                                <option value="Defective item">
                                                    Product is defective
                                                </option>

                                                <option value="Missing item">
                                                    Missing item
                                                </option>

                                                <option value="Not as described">
                                                    Item not as described
                                                </option>

                                                <option value="Other">
                                                    Other
                                                </option>

                                            </select>

                                        </div>


                                        <!-- MESSAGE -->

                                        <div class="form-group">

                                            <label>
                                                Additional Details
                                            </label>

                                            <textarea
                                                name="message"
                                                placeholder="Tell the seller what happened..."
                                            ></textarea>

                                        </div>


                                        <!-- SUBMIT -->

                                        <button
                                            type="submit"
                                            class="return-submit"
                                        >
                                            Submit Return / Refund Request
                                        </button>

                                    </form>

                                </div>

                            </div>

                        @endif


                    </div>

                @endforeach

            </div>

        @endif

    </div>


    <!-- Leaflet JS -->

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


    <script>

        const maps = {};


        /* =========================
           TOGGLE TRACKING
        ========================= */

        function toggleTracking(id) {

            const panel = document.getElementById(id);

            if (!panel) {
                return;
            }

            const isOpen =
                panel.classList.contains('active');


            if (isOpen) {

                panel.classList.remove('active');

                return;

            }


            panel.classList.add('active');


            const mapElement =
                panel.querySelector('.map');


            if (!mapElement) {
                return;
            }


            if (maps[id]) {

                setTimeout(function () {

                    maps[id].invalidateSize();

                }, 100);

                return;

            }


            /* =========================
               TEMPORARY LOCATIONS
            ========================= */

            const buyerLocation =
                [14.5995, 120.9842];

            const riderLocation =
                [14.6095, 120.9942];


            /* =========================
               CREATE MAP
            ========================= */

            const map =
                L.map(mapElement).setView(
                    buyerLocation,
                    13
                );


            maps[id] = map;


            /* =========================
               MAP TILES
            ========================= */

            L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
                    maxZoom: 19,
                    attribution:
                        '&copy; OpenStreetMap contributors'
                }
            ).addTo(map);


            /* =========================
               BUYER MARKER
            ========================= */

            const buyerIcon =
                L.divIcon({

                    className: '',

                    html:
                        '<div style="' +
                        'font-size:32px;' +
                        'text-align:center;' +
                        '">🏠</div>',

                    iconSize: [35, 35],

                    iconAnchor: [17, 30]

                });


            L.marker(
                buyerLocation,
                {
                    icon: buyerIcon
                }
            )
            .addTo(map)
            .bindPopup(
                '<strong>🏠 Delivery Address</strong><br>' +
                'Your delivery location'
            );


            /* =========================
               RIDER MARKER
            ========================= */

            const riderIcon =
                L.divIcon({

                    className: '',

                    html:
                        '<div style="' +
                        'font-size:32px;' +
                        'text-align:center;' +
                        '">🏍️</div>',

                    iconSize: [35, 35],

                    iconAnchor: [17, 30]

                });


            L.marker(
                riderLocation,
                {
                    icon: riderIcon
                }
            )
            .addTo(map)
            .bindPopup(
                '<strong>🏍️ Rider</strong><br>' +
                'Delivery rider location'
            );


            /* =========================
               DELIVERY ROUTE
            ========================= */

            L.polyline(
                [
                    riderLocation,
                    buyerLocation
                ],
                {
                    weight: 5,
                    opacity: 0.7
                }
            )
            .addTo(map);


            /* =========================
               FIT MAP
            ========================= */

            const bounds =
                L.latLngBounds([
                    riderLocation,
                    buyerLocation
                ]);


            map.fitBounds(
                bounds,
                {
                    padding: [40, 40]
                }
            );


            setTimeout(function () {

                map.invalidateSize();

            }, 300);

        }


        /* =========================
           TOGGLE RETURN / REFUND
        ========================= */

        function toggleReturnRefund(id) {

            const panel =
                document.getElementById(id);


            if (!panel) {
                return;
            }


            const isOpen =
                panel.classList.contains('active');


            if (isOpen) {

                panel.classList.remove('active');

                return;

            }


            panel.classList.add('active');


            panel.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });

        }

    </script>

</body>

</html>