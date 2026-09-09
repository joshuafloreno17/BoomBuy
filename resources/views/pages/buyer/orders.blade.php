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
           ALERT
        ========================= */

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
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

        /* =========================
           TRACK BUTTON
        ========================= */

        .track-btn {
            border: none;
            background: #f34f1d;
            color: white;
            padding: 11px 18px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
        }

        .track-btn:hover {
            background: #df4516;
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

        .timeline-dot.pending {
            background: #f5b70b;
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
            border-radius: 8px;
            font-weight: 700;
        }

        .btn:hover {
            background: #df4516;
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

            .tracking-grid {
                grid-template-columns: 1fr;
            }

            .map-container {
                height: 320px;
            }
        }

    
/* ===== BoomBuy Vibrant Design System Overrides ===== */
h1, h2, h3, .logo, .hero-title, .hero h1, .section-title, .page-title,
.product-title, .price, .cta, .cta-title, .brand, .checkout-title,
.card-title, .modal-title, .auth-title, .form-title, .empty-title,
.step-title, .order-title, .stat-title, .stat-value, .banner-title {
    font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
    letter-spacing: -0.01em;
}
button, .btn, [class*="btn-"], .add-to-cart, .buy-now, .checkout-btn,
.register-btn, .login-btn, .submit-btn, .primary-btn {
    border-radius: 12px !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
button:hover, .btn:hover, [class*="btn-"]:hover, .add-to-cart:hover,
.buy-now:hover, .primary-btn:hover {
    transform: translateY(-1px);
}
.card, [class*="-card"], .product-card {
    border-radius: 16px !important;
}
::selection {
    background: #ffd7c2;
    color: #7c1a00;
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


    @if(session('success'))

        <div class="alert">
            {{ session('success') }}
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

                @endphp


                <div class="order-card">


                    <!-- ORDER HEADER -->

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


                    <!-- ITEMS -->

                    <div class="items">

                        @foreach($order['items'] ?? [] as $item)

                           <div class="item">
    <div>
        <div class="item-name">
            {{ $item['product_name'] ?? 'Product' }}
        </div>

        <div class="item-info">
            Quantity: {{ $item['quantity'] ?? 1 }}
        </div>
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


                    <!-- ORDER INFO -->

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


                    <!-- ORDER FOOTER -->

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


                        <!-- TRACK ORDER -->

                        <button
                            type="button"
                            class="track-btn"
                            onclick="toggleTracking('{{ $trackingId }}')"
                        >
                            📍 Track Order
                        </button>


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


                        <!-- DELIVERY TIMELINE -->

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
                                        ['preparing','ready for pickup','picked up','out for delivery','delivered']
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
                                        ['ready for pickup','picked up','out for delivery','delivered']
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
                                        ['picked up','out for delivery','delivered']
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
                                        ['out for delivery','delivered']
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

                </div>

            @endforeach


        </div>


    @endif


</div>


<!-- Leaflet JS -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


<script>

    const maps = {};

    /*
    |--------------------------------------------------------------------------
    | TOGGLE TRACKING
    |--------------------------------------------------------------------------
    */

    function toggleTracking(id) {

        const panel =
            document.getElementById(id);

        if (!panel) {
            return;
        }

        const isOpen =
            panel.classList.contains('active');

        // Close if already open
        if (isOpen) {

            panel.classList.remove('active');

            return;
        }

        panel.classList.add('active');


        /*
        |--------------------------------------------------------------------------
        | FIND MAP
        |--------------------------------------------------------------------------
        */

        const mapElement =
            panel.querySelector('.map');

        if (!mapElement) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | DON'T CREATE MAP TWICE
        |--------------------------------------------------------------------------
        */

        if (maps[id]) {

            setTimeout(function () {

                maps[id].invalidateSize();

            }, 100);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | DEFAULT PHILIPPINES LOCATION
        |--------------------------------------------------------------------------
        |
        | This is a temporary demo location.
        | Later we will replace this with:
        |
        | Seller GPS
        | Rider GPS
        | Buyer GPS
        |
        */

        const buyerLocation =
            [14.5995, 120.9842];

        const riderLocation =
            [14.6095, 120.9942];


        /*
        |--------------------------------------------------------------------------
        | CREATE MAP
        |--------------------------------------------------------------------------
        */

        const map =
            L.map(mapElement).setView(
                buyerLocation,
                13
            );


        maps[id] = map;


        /*
        |--------------------------------------------------------------------------
        | MAP TILES
        |--------------------------------------------------------------------------
        */

        L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                maxZoom: 19,
                attribution:
                    '&copy; OpenStreetMap contributors'
            }
        ).addTo(map);


        /*
        |--------------------------------------------------------------------------
        | BUYER MARKER
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | RIDER MARKER
        |--------------------------------------------------------------------------
        */

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


        const riderMarker =
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


        /*
        |--------------------------------------------------------------------------
        | DELIVERY ROUTE
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | FIT MAP
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | FIX MAP SIZE
        |--------------------------------------------------------------------------
        */

        setTimeout(function () {

            map.invalidateSize();

        }, 300);

    }

</script>


</body>

</html>