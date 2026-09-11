<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Deliveries — BoomBuy</title>

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

        /* =========================
           SIDEBAR
        ========================= */

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

        /* LOGOUT */

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


        /* =========================
           MAIN
        ========================= */

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

        .subtitle {
            color: #816f6a;

            margin-top: 6px;
        }

        .profile {
            background: white;

            padding: 10px 16px;

            border-radius: 10px;

            box-shadow:
                0 2px 10px rgba(0,0,0,0.05);
        }

        .profile strong {
            color: #111827;
        }


        /* =========================
           ALERTS
        ========================= */

        .alert {
            padding: 14px 18px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;
        }

        .success {
            background: #dcfce7;

            color: #166534;
        }

        .error {
            background: #fee2e2;

            color: #991b1b;
        }


        /* =========================
           FILTER
        ========================= */

        .filter-box {
            background: white;

            padding: 18px;

            border-radius: 14px;

            margin-bottom: 20px;

            box-shadow:
                0 2px 12px rgba(0,0,0,0.05);
        }

        .filter-box label {
            font-size: 13px;

            color: #816f6a;

            margin-right: 10px;
        }

        .filter-box select {
            padding: 10px 13px;

            border: 1px solid #dbd3d1;

            border-radius: 8px;

            outline: none;

            background: white;

            cursor: pointer;
        }


        /* =========================
           DELIVERY GRID
        ========================= */

        .deliveries {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 20px;
        }


        /* =========================
           DELIVERY CARD
        ========================= */

        .delivery-card {
            background: white;

            border-radius: 14px;

            padding: 22px;

            box-shadow:
                0 2px 12px rgba(0,0,0,0.05);

            transition: 0.2s;
        }

        .delivery-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 5px 18px rgba(0,0,0,0.08);
        }

        .delivery-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 18px;
        }

        .order-id {
            font-size: 18px;

            font-weight: bold;
        }


        /* =========================
           STATUS
        ========================= */

        .status {
            padding: 7px 11px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;

            background: #fffaed;

            color: #eaaf0c;

            white-space: nowrap;
        }

        .status.delivered {
            background: #dcfce7;

            color: #166534;
        }

        .status.transit {
            background: #ffe3da;

            color: #df4516;
        }

        .status.pending {
            background: #fef3c7;

            color: #926f0e;
        }


        /* =========================
           INFO
        ========================= */

        .info {
            border-top:
                1px solid #ebe6e5;

            padding-top: 15px;
        }

        .info-row {
            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 8px 0;

            font-size: 14px;
        }

        .info-label {
            color: #816f6a;
        }

        .info-value {
            font-weight: 500;

            text-align: right;

            word-break: break-word;
        }


        /* =========================
           BUTTONS
        ========================= */

        .buttons {
            display: flex;

            gap: 10px;

            margin-top: 18px;
        }

        .btn {
            flex: 1;

            padding: 10px;

            border: none;

            border-radius: 8px;

            text-decoration: none;

            text-align: center;

            cursor: pointer;

            font-size: 13px;

            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
        }


        /* VIEW */

        .view-btn {
            background: #f9bc16;

            color: white;
        }

        .view-btn:hover {
            background: #eaaf0c;
        }


        /* CLAIM */

        .claim-form {
            flex: 1;
        }

        .claim-btn {
            width: 100%;

            border: none;

            background: #16a34a;

            color: white;

            font-weight: bold;
        }

        .claim-btn:hover {
            background: #15803d;
        }


        /* UPDATE */

        .status-btn {
            background: #111827;

            color: white;
        }

        .status-btn:hover {
            background: #523d36;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty {
            background: white;

            border-radius: 14px;

            padding: 60px 20px;

            text-align: center;

            box-shadow:
                0 2px 12px rgba(0,0,0,0.05);
        }

        .empty-icon {
            font-size: 60px;

            margin-bottom: 15px;
        }

        .empty h2 {
            margin-bottom: 8px;
        }

        .empty p {
            color: #b0a09b;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 950px) {

            .deliveries {
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

            .topbar {
                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }

        }


        @media (max-width: 500px) {

            .sidebar {
                position: relative;

                width: 100%;

                height: auto;

                display: flex;
                flex-direction: column;
                padding: 14px 16px;
            }

            .logo {
                font-size: 20px;
                margin-bottom: 10px;
            }

            .menu-title {
                display: none;
            }

            .menu {
                display: flex;
                flex-direction: row;
                overflow-x: auto;
                gap: 8px;
                margin-bottom: 4px;
                -webkit-overflow-scrolling: touch;
            }

            .menu a {
                white-space: nowrap;
                margin-bottom: 0;
                flex-shrink: 0;
                font-size: 13px;
                padding: 10px 14px;
            }

            .main {
                margin-left: 0;
            }

            .logout {
                position: static;
                margin-top: 10px;
            }

            .deliveries {
                grid-template-columns: 1fr;
            }

            .delivery-header {
                flex-direction: column;

                align-items: flex-start;
            }

            .buttons {
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


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">

        Boom<span>Buy</span>

    </div>


    <div class="menu-title">

        Rider Menu

    </div>


    <div class="menu">

        <a href="{{ route('rider.dashboard') }}">

            🏠 Dashboard

        </a>


        <a
            href="{{ route('rider.deliveries') }}"
            class="active"
        >

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



<!-- =========================
     MAIN
========================= -->

<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h1>
                My Deliveries
            </h1>

            <p class="subtitle">
                Manage and track your assigned orders.
            </p>

        </div>


        <div class="profile">

            🚴

            <strong>
                {{ $user['name'] ?? 'Rider' }}
            </strong>

        </div>

    </div>



    <!-- =========================
         ALERTS
    ========================= -->

    @if(session('success'))

        <div class="alert success">

            ✅ {{ session('success') }}

        </div>

    @endif


    @if(session('error'))

        <div class="alert error">

            ❌ {{ session('error') }}

        </div>

    @endif



    <!-- =========================
         FILTER
    ========================= -->

    <div class="filter-box">

        <label for="statusFilter">

            Filter by Status:

        </label>


        <select id="statusFilter">

            <option value="all">
                All Deliveries
            </option>

            <option value="Pending">
                Pending
            </option>

            <option value="Ready for Pickup">
                Ready for Pickup
            </option>

            <option value="Picked Up">
                Picked Up
            </option>

            <option value="On the Way">
                On the Way
            </option>

            <option value="Delivered">
                Delivered
            </option>

        </select>

    </div>



    <!-- =========================
         DELIVERIES
    ========================= -->

    @if(count($deliveries ?? []) > 0)


        <div class="deliveries">


            @foreach($deliveries as $delivery)


                @php

                    $status =
                        $delivery['status']
                        ?? 'Pending';


                    $statusClass =
                        'pending';


                    if (
                        $status === 'Picked Up' ||
                        $status === 'On the Way'
                    ) {

                        $statusClass =
                            'transit';

                    }


                    if (
                        $status === 'Delivered'
                    ) {

                        $statusClass =
                            'delivered';

                    }

                @endphp



                <!-- DELIVERY CARD -->

                <div
                    class="delivery-card"
                    data-status="{{ $status }}"
                >


                    <!-- HEADER -->

                    <div class="delivery-header">


                        <div class="order-id">

                            📦 Order #{{ $delivery['id'] ?? 'N/A' }}

                        </div>


                        <div
                            class="status {{ $statusClass }}"
                        >

                            {{ $status }}

                        </div>


                    </div>



                    <!-- INFO -->

                    <div class="info">


                        <div class="info-row">

                            <span class="info-label">
                                Customer
                            </span>


                            <span class="info-value">

                                {{ $delivery['buyer_name'] ?? 'Customer' }}

                            </span>

                        </div>



                        <div class="info-row">

                            <span class="info-label">
                                Address
                            </span>


                            <span class="info-value">

                                {{ $delivery['address'] ?? 'No address provided' }}

                            </span>

                        </div>



                        <div class="info-row">

                            <span class="info-label">
                                Amount
                            </span>


                            <span class="info-value">

                                ₱{{ number_format($delivery['total'] ?? 0, 2) }}

                            </span>

                        </div>



                        <div class="info-row">

                            <span class="info-label">
                                Payment
                            </span>


                            <span class="info-value">

                                {{ $delivery['payment'] ?? 'Cash on Delivery' }}

                            </span>

                        </div>



                        @if(
                            isset($delivery['items']) &&
                            is_array($delivery['items'])
                        )

                            <div class="info-row">

                                <span class="info-label">
                                    Items
                                </span>


                                <span class="info-value">

                                    {{ count($delivery['items']) }}
                                    item(s)

                                </span>

                            </div>

                        @endif


                    </div>



                    <!-- =========================
                         BUTTONS
                    ========================= -->

                    <div class="buttons">


                        {{-- =========================
                             READY FOR PICKUP
                        ========================= --}}

                        @if(
                            $status === 'Ready for Pickup' &&
                            empty($delivery['rider_id'] ?? null)
                        )


                            <form
                                method="POST"
                                action="{{ route('rider.delivery.claim', $delivery['id']) }}"
                                class="claim-form"
                            >

                                @csrf


                                <button
                                    type="submit"
                                    class="btn claim-btn"
                                >

                                    🚚 Pick Up Order

                                </button>


                            </form>


                        @endif



                        {{-- =========================
                             VIEW DETAILS
                        ========================= --}}

                        <a
                            href="{{ route('rider.delivery.details', $delivery['id']) }}"
                            class="btn view-btn"
                        >

                            👁 View Details

                        </a>



                        {{-- =========================
                             UPDATE STATUS
                        ========================= --}}

                        @if(
                            !empty($delivery['rider_id'] ?? null) &&
                            $status !== 'Delivered'
                        )


                            <a
                                href="{{ route('rider.delivery.details', $delivery['id']) }}"
                                class="btn status-btn"
                            >

                                🔄 Update Status

                            </a>


                        @endif


                    </div>


                </div>


            @endforeach


        </div>


    @else


        <!-- EMPTY STATE -->

        <div class="empty">


            <div class="empty-icon">

                🚚

            </div>


            <h2>

                No Deliveries Yet

            </h2>


            <p>

                Orders assigned to you will appear here.

            </p>


        </div>


    @endif


</main>



<!-- =========================
     FILTER SCRIPT
========================= -->

<script>

    const statusFilter =
        document.getElementById('statusFilter');


    const deliveryCards =
        document.querySelectorAll('.delivery-card');


    statusFilter.addEventListener(
        'change',
        function () {

            const selected =
                this.value;


            deliveryCards.forEach(
                function (card) {

                    const status =
                        card.dataset.status;


                    if (
                        selected === 'all' ||
                        status === selected
                    ) {

                        card.style.display =
                            '';

                    } else {

                        card.style.display =
                            'none';

                    }

                }
            );

        }
    );

</script>


</body>

</html>