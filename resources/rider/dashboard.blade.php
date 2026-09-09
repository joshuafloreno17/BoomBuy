<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rider Dashboard — BoomBuy</title>

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
            color: #222;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #111827;
            color: white;
            padding: 25px 18px;
        }

        .logo {
            font-size: 27px;
            font-weight: bold;
            margin-bottom: 35px;
            padding-left: 10px;
        }

        .logo span {
            color: #f9bc16;
        }

        .menu-title {
            font-size: 12px;
            color: #b0a09b;
            text-transform: uppercase;
            margin: 20px 10px 10px;
            letter-spacing: 1px;
        }

        .menu a {
            display: block;
            text-decoration: none;
            color: #dbd3d1;
            padding: 13px 12px;
            border-radius: 8px;
            margin-bottom: 6px;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #f9bc16;
            color: white;
        }

        .logout {
            position: absolute;
            bottom: 25px;
            left: 18px;
            right: 18px;
        }

        .logout button {
            width: 100%;
            border: none;
            background: #dc2626;
            color: white;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .logout button:hover {
            background: #b91c1c;
        }


        /* MAIN */

        .main {
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .topbar h1 {
            font-size: 28px;
        }

        .welcome {
            color: #816f6a;
            margin-top: 6px;
        }

        .profile {
            background: white;
            padding: 10px 16px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .profile strong {
            color: #111827;
        }


        /* CARDS */

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .card-icon {
            font-size: 28px;
            margin-bottom: 12px;
        }

        .card-title {
            color: #816f6a;
            font-size: 14px;
        }

        .card-number {
            font-size: 28px;
            font-weight: bold;
            margin-top: 5px;
        }


        /* CONTENT */

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .panel {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .panel h2 {
            margin-bottom: 20px;
            font-size: 20px;
        }


        /* PANEL HEADER */

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .panel-header h2 {
            margin-bottom: 0;
        }

        .view-all {
            color: #f9bc16;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
        }

        .view-all:hover {
            color: #eaaf0c;
        }


        /* DELIVERY */

        .delivery {
            border: 1px solid #ebe6e5;
            border-radius: 12px;
            padding: 17px;
            margin-bottom: 12px;
            transition: 0.2s;
        }

        .delivery:hover {
            border-color: #fdd874;
            box-shadow: 0 4px 12px rgba(249,115,22,0.08);
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .delivery-id {
            font-weight: bold;
            font-size: 15px;
        }


        /* STATUS */

        .status {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-available {
            background: #ecfdf5;
            color: #059669;
        }

        .status-picked {
            background: #fff3ef;
            color: #f34f1d;
        }

        .status-way {
            background: #fffaed;
            color: #eaaf0c;
        }

        .status-delivered {
            background: #f0fdf4;
            color: #16a34a;
        }


        /* DELIVERY INFO */

        .delivery-info {
            color: #816f6a;
            line-height: 1.8;
            font-size: 14px;
        }

        .delivery-info strong {
            color: #523d36;
        }


        /* BUTTONS */

        .delivery-actions {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .view-btn {
            display: inline-block;
            padding: 9px 14px;
            background: #f9bc16;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-size: 13px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .view-btn:hover {
            background: #eaaf0c;
        }

        .claim-btn {
            padding: 9px 15px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }

        .claim-btn:hover {
            background: #15803d;
        }


        /* AVAILABLE BOX */

        .available-label {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 12px 14px;
            border-radius: 9px;
            font-size: 13px;
            margin-bottom: 15px;
        }


        /* EMPTY */

        .empty {
            text-align: center;
            padding: 45px 10px;
            color: #b0a09b;
        }

        .empty-icon {
            font-size: 50px;
            margin-bottom: 12px;
        }

        .empty-title {
            font-size: 16px;
            font-weight: bold;
            color: #816f6a;
        }

        .empty-text {
            margin-top: 6px;
            font-size: 13px;
        }


        /* QUICK ACTIONS */

        .quick-action {
            display: block;
            text-decoration: none;
            background: #fbf9f9;
            padding: 15px;
            border-radius: 9px;
            margin-bottom: 10px;
            color: #523d36;
            transition: 0.2s;
        }

        .quick-action:hover {
            background: #fffaed;
            color: #eaaf0c;
        }


        /* RIDER TIP */

        .rider-tip {
            margin-top: 20px;
            padding: 15px;
            background: #fffaed;
            border: 1px solid #fee8aa;
            border-radius: 10px;
        }

        .rider-tip-title {
            font-size: 13px;
            font-weight: bold;
            color: #c2910c;
            margin-bottom: 6px;
        }

        .rider-tip-text {
            font-size: 12px;
            color: #7c6012;
            line-height: 1.6;
        }


        /* RESPONSIVE */

        @media (max-width: 1000px) {

            .cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 700px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                padding: 20px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .topbar {
                align-items: flex-start;
                gap: 15px;
                flex-direction: column;
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


<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">
        Boom<span>Buy</span>
    </div>


    <div class="menu-title">
        Rider Menu
    </div>


    <div class="menu">

        <a
            href="{{ route('rider.dashboard') }}"
            class="active"
        >
            🏠 Dashboard
        </a>


        <a href="{{ route('rider.deliveries') }}">
            🚚 My Deliveries
        </a>


        <a href="{{ route('rider.profile') }}">
            👤 My Profile
        </a>

    </div>


    <div class="menu-title">
        Account
    </div>


    <div class="menu">

        <a href="{{ url('/') }}">
            🛍️ BoomBuy Store
        </a>

    </div>


    <div class="logout">

        <form
            method="POST"
            action="{{ route('logout') }}"
        >

            @csrf

            <button type="submit">
                🚪 Logout
            </button>

        </form>

    </div>

</aside>



<!-- MAIN -->

<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h1>
                Rider Dashboard
            </h1>

            <p class="welcome">
                Welcome back,
                {{ $user['name'] ?? 'Rider' }}!
            </p>

        </div>


        <div class="profile">

            🚴

            <strong>
                {{ $user['name'] ?? 'Rider' }}
            </strong>

        </div>

    </div>



   @php

    $totalDeliveries = 0;
    $availableOrders = 0;
    $inTransit = 0;
    $delivered = 0;

    foreach (($deliveries ?? []) as $delivery) {

        $status = $delivery['status'] ?? 'Pending';
        $riderId = $delivery['rider_id'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | AVAILABLE ORDERS
        |--------------------------------------------------------------------------
        */

        if (
            $status === 'Ready for Pickup' &&
            empty($riderId)
        ) {
            $availableOrders++;
        }


        /*
        |--------------------------------------------------------------------------
        | IN TRANSIT
        |--------------------------------------------------------------------------
        */

        if (
            !empty($riderId) &&
            (
                $status === 'Picked Up' ||
                $status === 'On the Way'
            )
        ) {
            $inTransit++;
        }


        /*
        |--------------------------------------------------------------------------
        | DELIVERED
        |--------------------------------------------------------------------------
        */

        if ($status === 'Delivered') {
            $delivered++;
        }


        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        if (
            !empty($riderId) ||
            $status === 'Ready for Pickup'
        ) {
            $totalDeliveries++;
        }

    }

@endphp

    <!-- STAT CARDS -->

    <section class="cards">


        <div class="card">

            <div class="card-icon">
                📦
            </div>

            <div class="card-title">
                Total Deliveries
            </div>

            <div class="card-number">
                {{ $totalDeliveries }}
            </div>

        </div>



        <div class="card">

            <div class="card-icon">
                🟢
            </div>

            <div class="card-title">
                Available Orders
            </div>

            <div class="card-number">
                {{ $availableOrders }}
            </div>

        </div>



        <div class="card">

            <div class="card-icon">
                🚚
            </div>

            <div class="card-title">
                In Transit
            </div>

            <div class="card-number">
                {{ $inTransit }}
            </div>

        </div>



        <div class="card">

            <div class="card-icon">
                ✅
            </div>

            <div class="card-title">
                Delivered
            </div>

            <div class="card-number">
                {{ $delivered }}
            </div>

        </div>

    </section>



    <!-- CONTENT -->

    <section class="content-grid">


        <!-- AVAILABLE ORDERS -->

        <div class="panel">


            <div class="panel-header">

                <h2>
                    Available Orders
                </h2>

                <a
                    href="{{ route('rider.deliveries') }}"
                    class="view-all"
                >
                    View All →
                </a>

            </div>



            @php

                $availableDeliveries = [];


                foreach (($deliveries ?? []) as $delivery) {

                    if (
                        ($delivery['status'] ?? '') ===
                        'Ready for Pickup'
                        &&
                        empty($delivery['rider_id'])
                    ) {

                        $availableDeliveries[] =
                            $delivery;

                    }

                }

            @endphp



            @if(count($availableDeliveries) > 0)


                @foreach(
                    array_slice($availableDeliveries, 0, 5)
                    as $delivery
                )


                    <div class="delivery">


                        <div class="delivery-header">


                            <div class="delivery-id">

                                📦
                                Order #{{ $delivery['id'] ?? 'N/A' }}

                            </div>


                            <div class="status status-available">

                                🟢 Ready for Pickup

                            </div>

                        </div>



                        <div class="available-label">

                            🚚 This order is available for pickup.
                            Claim it if you want to deliver this order.

                        </div>



                        <div class="delivery-info">


                            <div>

                                👤
                                <strong>Customer:</strong>

                                {{ $delivery['buyer_name'] ?? 'Customer' }}

                            </div>


                            <div>

                                📍
                                <strong>Address:</strong>

                                {{ $delivery['address'] ?? 'No address provided' }}

                            </div>


                            <div>

                                📞
                                <strong>Phone:</strong>

                                {{ $delivery['phone'] ?? 'No phone provided' }}

                            </div>


                            <div>

                                💰
                                <strong>Total:</strong>

                                ₱{{ number_format($delivery['total'] ?? 0, 2) }}

                            </div>


                            <div>

                                💳
                                <strong>Payment:</strong>

                                {{ $delivery['payment'] ?? 'N/A' }}

                            </div>


                            <div>

                                🛒
                                <strong>Items:</strong>

                                {{ count($delivery['items'] ?? []) }}

                            </div>

                        </div>



                        <div class="delivery-actions">


                            <!-- CLAIM -->

                            <form
                                method="POST"
                                action="{{ route(
                                    'rider.delivery.claim',
                                    $delivery['id']
                                ) }}"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="claim-btn"
                                >
                                    🚚 Claim Delivery
                                </button>

                            </form>



                            <!-- DETAILS -->

                            <a
                                href="{{ route(
                                    'rider.delivery.details',
                                    $delivery['id']
                                ) }}"
                                class="view-btn"
                            >
                                👁 View Details
                            </a>

                        </div>

                    </div>


                @endforeach


            @else


                <div class="empty">

                    <div class="empty-icon">
                        📦
                    </div>


                    <div class="empty-title">
                        No Available Orders
                    </div>


                    <div class="empty-text">
                        Orders marked
                        <strong>"Ready for Pickup"</strong>
                        will appear here.
                    </div>

                </div>


            @endif

        </div>



        <!-- QUICK ACTIONS -->

        <div class="panel">


            <h2>
                Quick Actions
            </h2>


            <a
                href="{{ route('rider.deliveries') }}"
                class="quick-action"
            >
                🚚 View Available Deliveries
            </a>


            <a
                href="{{ route('rider.profile') }}"
                class="quick-action"
            >
                👤 View Profile
            </a>


            <a
                href="{{ url('/') }}"
                class="quick-action"
            >
                🛍️ Visit BoomBuy
            </a>



            <div class="rider-tip">

                <div class="rider-tip-title">
                    💡 Rider Tip
                </div>


                <div class="rider-tip-text">

                    Orders become available after the seller
                    changes the order status to
                    <strong>Ready for Pickup</strong>.

                    Claim the order to start your delivery.

                </div>

            </div>


        </div>


    </section>


</main>


</body>
</html>