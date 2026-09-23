<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Deliveries — BoomBuy</title>

    @include('partials.pwa-head')

    <style>

        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button,
        input,
        select {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }


        /* =========================================================
           RIDER SIDEBAR
        ========================================================= */

        .rider-sidebar {

            position: fixed;

            left: 0;
            top: 0;

            width: 250px;
            height: 100vh;

            background: #ffffff;

            border-right: 1px solid #f7e5e0;

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            padding: 26px 16px;

            z-index: 1000;
        }


        .rider-sidebar-top {
            width: 100%;
        }


        /* =========================================================
           BRAND
        ========================================================= */

        .rider-brand {

            display: flex;
            align-items: center;

            gap: 11px;

            padding: 0 10px;

            margin-bottom: 30px;

            color: #172033;
        }


        .rider-brand-icon {

            width: 42px;
            height: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #fff0eb;

            border-radius: 12px;

            font-size: 21px;
        }


        .rider-brand-name {

            font-family: 'Baloo 2', sans-serif;

            font-size: 25px;
            font-weight: 800;

            line-height: 1;
        }


        .rider-brand-role {

            margin-top: 4px;

            color: #c47a66;

            font-size: 10px;
            font-weight: 600;
        }


        /* =========================================================
           SIDEBAR NAVIGATION
        ========================================================= */

        .rider-sidebar-nav {

            display: flex;
            flex-direction: column;

            gap: 6px;
        }


        .rider-sidebar-link {

            position: relative;

            display: flex;
            align-items: center;

            gap: 12px;

            width: 100%;

            padding: 12px 14px;

            border-radius: 11px;

            color: #6f5d58;

            font-size: 12px;
            font-weight: 700;

            transition: .2s ease;
        }


        .rider-sidebar-link:hover {

            background: #fff4f0;

            color: #e8420f;

            transform: translateX(2px);
        }


        .rider-sidebar-link.active {

            background: #ef4715;

            color: #ffffff;

            box-shadow: 0 7px 18px rgba(232, 66, 15, .18);
        }


        .sidebar-icon {

            width: 22px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            font-size: 16px;
        }


        .sidebar-notification-badge {

            margin-left: auto;

            min-width: 19px;
            height: 19px;

            padding: 0 5px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #ef4715;

            color: #ffffff;

            border-radius: 999px;

            font-size: 9px;
            font-weight: 800;
        }


        .rider-sidebar-link.active .sidebar-notification-badge {

            background: #ffffff;

            color: #ef4715;
        }


        /* =========================================================
           SIDEBAR BOTTOM
        ========================================================= */

        .rider-sidebar-bottom {

            width: 100%;
        }


        .rider-sidebar-divider {

            width: 100%;

            height: 1px;

            background: #f7e5e0;

            margin-bottom: 14px;
        }


        .rider-sidebar-bottom form {
            width: 100%;
        }


        .rider-sidebar-logout {

            width: 100%;

            display: flex;
            align-items: center;

            gap: 12px;

            padding: 12px 14px;

            border: none;

            background: transparent;

            color: #e8420f;

            border-radius: 11px;

            font-size: 12px;
            font-weight: 700;

            cursor: pointer;

            text-align: left;

            transition: .2s ease;
        }


        .rider-sidebar-logout:hover {

            background: #fff0eb;
        }


        /* =========================================================
           MAIN
        ========================================================= */

        .main {

            width: calc(100% - 250px);

            min-height: 100vh;

            margin-left: 250px;

            padding: 38px 40px 70px;
        }


        .content-wrapper {

            width: 100%;

            max-width: 1200px;

            margin: 0 auto;
        }


        /* =========================================================
           TOPBAR
        ========================================================= */

        .topbar {

            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 20px;

            margin-bottom: 28px;
        }


        .topbar h1 {

            font-family: 'Baloo 2', sans-serif;

            color: #172033;

            font-size: 32px;
            font-weight: 800;

            line-height: 1.15;
        }


        .subtitle {

            margin-top: 5px;

            color: #977970;

            font-size: 13px;

            line-height: 1.5;
        }


        .profile {

            display: flex;
            align-items: center;

            gap: 9px;

            background: #ffffff;

            border: 1px solid #f7e5e0;

            padding: 10px 15px;

            border-radius: 11px;

            color: #977970;

            font-size: 12px;

            flex-shrink: 0;
        }


        .profile strong {

            color: #172033;

            font-weight: 800;
        }


        /* =========================================================
           ALERTS
        ========================================================= */

        .alert {

            padding: 13px 17px;

            border-radius: 11px;

            margin-bottom: 20px;

            font-size: 12px;
            font-weight: 600;

            border: 1px solid transparent;
        }


        .success {

            background: #ecfdf5;

            color: #047857;

            border-color: #bbf7d0;
        }


        .error {

            background: #fff1f2;

            color: #be123c;

            border-color: #fecdd3;
        }


        /* =========================================================
           FILTER
        ========================================================= */

        .filter-box {

            display: flex;
            align-items: center;

            gap: 12px;

            background: #ffffff;

            border: 1px solid #f7e5e0;

            padding: 16px 18px;

            border-radius: 14px;

            margin-bottom: 25px;
        }


        .filter-box label {

            color: #977970;

            font-size: 12px;
            font-weight: 700;
        }


        .filter-box select {

            min-width: 190px;

            padding: 9px 12px;

            border: 1px solid #f3d8d0;

            border-radius: 9px;

            outline: none;

            background: #ffffff;

            color: #523d36;

            font-size: 12px;

            cursor: pointer;
        }


        .filter-box select:focus {

            border-color: #ef4715;

            box-shadow: 0 0 0 3px rgba(232, 66, 15, .08);
        }


        /* =========================================================
           DELIVERY GRID
        ========================================================= */

        .deliveries {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px;
        }


        /* =========================================================
           DELIVERY CARD
        ========================================================= */

        .delivery-card {

            background: #ffffff;

            border: 1px solid #f7e5e0;

            border-radius: 15px;

            padding: 20px;

            transition: .2s ease;

            overflow: hidden;
        }


        .delivery-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 10px 28px rgba(232, 66, 15, .08);
        }


        .delivery-header {

            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 12px;

            margin-bottom: 17px;
        }


        .order-id {

            color: #172033;

            font-family: 'Baloo 2', sans-serif;

            font-size: 19px;
            font-weight: 800;
        }


        /* =========================================================
           STATUS
        ========================================================= */

        .status {

            padding: 6px 10px;

            border-radius: 999px;

            font-size: 10px;
            font-weight: 800;

            white-space: nowrap;
        }


        .status.delivered {

            background: #ecfdf5;

            color: #059669;
        }


        .status.transit {

            background: #fff0eb;

            color: #e8420f;
        }


        .status.pending {

            background: #fff8ed;

            color: #b77900;
        }


        .status.ready {

            background: #fff0eb;

            color: #e8420f;
        }


        /* =========================================================
           INFO
        ========================================================= */

        .info {

            border-top: 1px solid #f7e5e0;

            padding-top: 13px;
        }


        .info-row {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            padding: 7px 0;

            font-size: 12px;
        }


        .info-label {

            color: #977970;

            flex-shrink: 0;
        }


        .info-value {

            color: #523d36;

            font-weight: 600;

            text-align: right;

            word-break: break-word;
        }


        /* =========================================================
           BUTTONS
        ========================================================= */

        .buttons {

            display: flex;

            gap: 8px;

            margin-top: 17px;

            flex-wrap: wrap;
        }


        .btn {

            min-height: 36px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 8px 12px;

            border: none;

            border-radius: 9px;

            text-decoration: none;

            text-align: center;

            cursor: pointer;

            font-size: 11px;
            font-weight: 800;

            transition: .2s ease;
        }


        .btn:hover {

            transform: translateY(-1px);
        }


        /* VIEW */

        .view-btn {

            background: #ef4715;

            color: #ffffff;

            flex: 1;
        }


        .view-btn:hover {

            background: #cf370b;
        }


        /* CLAIM */

        .claim-form {

            flex: 1;
        }


        .claim-btn {

            width: 100%;

            background: #16a34a;

            color: #ffffff;

            font-weight: 800;
        }


        .claim-btn:hover {

            background: #15803d;
        }


        /* UPDATE */

        .status-btn {

            background: #fff0eb;

            color: #e8420f;

            border: 1px solid #f6cfc4;

            flex: 1;
        }


        .status-btn:hover {

            background: #ffe3da;
        }


        /* =========================================================
           EMPTY
        ========================================================= */

        .empty {

            background: #ffffff;

            border: 1px solid #f7e5e0;

            border-radius: 15px;

            padding: 55px 20px;

            text-align: center;

            box-shadow: 0 6px 20px rgba(232, 66, 15, .04);
        }


        .empty-icon {

            width: 60px;
            height: 60px;

            display: flex;

            align-items: center;
            justify-content: center;

            margin: 0 auto 13px;

            background: #fff0eb;

            border-radius: 16px;

            font-size: 27px;
        }


        .empty h2 {

            color: #172033;

            font-family: 'Baloo 2', sans-serif;

            font-size: 21px;
            font-weight: 800;

            margin-bottom: 5px;
        }


        .empty p {

            color: #977970;

            font-size: 12px;
        }


        /* =========================================================
           FOOTER
        ========================================================= */

        .page-footer {

            margin-top: 35px;

            padding-top: 22px;

            border-top: 1px solid #f7e5e0;

            display: flex;

            justify-content: space-between;

            gap: 15px;

            color: #977970;

            font-size: 11px;
        }


        .page-footer strong {

            color: #e8420f;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .deliveries {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 760px) {

            .rider-sidebar {

                width: 78px;

                padding: 22px 10px;
            }


            .rider-brand {

                justify-content: center;

                padding: 0;

                margin-bottom: 25px;
            }


            .rider-brand-icon {

                width: 42px;
                height: 42px;
            }


            .rider-brand > div:last-child {

                display: none;
            }


            .rider-sidebar-link {

                justify-content: center;

                padding: 12px 8px;
            }


            .rider-sidebar-link > span:not(.sidebar-icon):not(.sidebar-notification-badge) {

                display: none;
            }


            .sidebar-icon {

                font-size: 17px;
            }


            .sidebar-notification-badge {

                position: absolute;

                top: 4px;
                right: 5px;
            }


            .rider-sidebar-bottom .rider-sidebar-logout {

                justify-content: center;

                padding: 12px 8px;
            }


            .rider-sidebar-logout > span:not(.sidebar-icon) {

                display: none;
            }


            .main {

                width: calc(100% - 78px);

                margin-left: 78px;

                padding: 28px 22px 55px;
            }

        }


        @media (max-width: 560px) {

            .rider-sidebar {

                position: relative;

                width: 100%;

                height: auto;

                min-height: auto;

                padding: 12px;

                border-right: none;

                border-bottom: 1px solid #f7e5e0;
            }


            .rider-brand {

                justify-content: flex-start;

                margin-bottom: 12px;

                padding: 0 5px;
            }


            .rider-brand > div:last-child {

                display: block;
            }


            .rider-sidebar-nav {

                flex-direction: row;

                overflow-x: auto;

                padding-bottom: 3px;

                gap: 5px;
            }


            .rider-sidebar-link {

                width: auto;

                flex-shrink: 0;

                justify-content: flex-start;

                padding: 9px 11px;

                gap: 7px;

                font-size: 11px;
            }


            .rider-sidebar-link > span:not(.sidebar-icon):not(.sidebar-notification-badge) {

                display: inline;
            }


            .rider-sidebar-bottom {

                margin-top: 8px;
            }


            .rider-sidebar-divider {

                display: none;
            }


            .rider-sidebar-bottom form {

                display: flex;
            }


            .rider-sidebar-logout {

                width: auto;

                padding: 8px 11px;

                font-size: 11px;
            }


            .rider-sidebar-logout > span:not(.sidebar-icon) {

                display: inline;
            }


            .main {

                width: 100%;

                margin-left: 0;

                padding: 22px 14px 45px;
            }


            .topbar {

                flex-direction: column;

                align-items: flex-start;

                margin-bottom: 22px;
            }


            .topbar h1 {

                font-size: 28px;
            }


            .profile {

                width: 100%;

                justify-content: center;
            }


            .filter-box {

                flex-direction: column;

                align-items: flex-start;
            }


            .filter-box select {

                width: 100%;
            }


            .delivery-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .buttons {

                flex-direction: column;
            }


            .buttons .btn,
            .claim-form {

                width: 100%;

                flex: none;
            }


            .page-footer {

                flex-direction: column;

                text-align: center;
            }

        }

    </style>

</head>


<body>


    <!-- =========================================================
         RIDER SIDEBAR
    ========================================================= -->

    <aside class="rider-sidebar">


        <div class="rider-sidebar-top">


            <!-- BRAND -->

            <a
                href="{{ route('rider.dashboard') }}"
                class="rider-brand"
            >

                <div class="rider-brand-icon">
                    🛍️
                </div>

                <div>

                    <div class="rider-brand-name">
                        BoomBuy
                    </div>

                    <div class="rider-brand-role">
                        Rider Center
                    </div>

                </div>

            </a>


            <!-- NAVIGATION -->

            <nav class="rider-sidebar-nav">


                <a
                    href="{{ route('rider.dashboard') }}"
                    class="rider-sidebar-link
                        {{ request()->routeIs('rider.dashboard') ? 'active' : '' }}"
                >

                    <span class="sidebar-icon">
                        🏠
                    </span>

                    <span>
                        Dashboard
                    </span>

                </a>


                <a
                    href="{{ route('rider.deliveries') }}"
                    class="rider-sidebar-link
                        {{ request()->routeIs('rider.deliveries') ? 'active' : '' }}"
                >

                    <span class="sidebar-icon">
                        🚚
                    </span>

                    <span>
                        My Deliveries
                    </span>

                </a>


                <a
                    href="{{ route('rider.profile') }}"
                    class="rider-sidebar-link
                        {{ request()->routeIs('rider.profile') ? 'active' : '' }}"
                >

                    <span class="sidebar-icon">
                        👤
                    </span>

                    <span>
                        My Profile
                    </span>

                </a>


                @php

                    $riderUnreadNotifications =
                        \App\Models\Notification::where(
                            'user_id',
                            $user['id']
                        )
                        ->whereNull('read_at')
                        ->count();

                @endphp


                <a
                    href="{{ route('rider.notifications') }}"
                    class="rider-sidebar-link
                        {{ request()->routeIs('rider.notifications') ? 'active' : '' }}"
                >

                    <span class="sidebar-icon">
                        🔔
                    </span>

                    <span>
                        Notifications
                    </span>

                    @if($riderUnreadNotifications > 0)

                        <span class="sidebar-notification-badge">
                            {{ $riderUnreadNotifications }}
                        </span>

                    @endif

                </a>


                <a
                    href="{{ url('/') }}"
                    class="rider-sidebar-link"
                >

                    <span class="sidebar-icon">
                        🛒
                    </span>

                    <span>
                        Store
                    </span>

                </a>


            </nav>

        </div>


        <!-- =====================================================
             SIDEBAR BOTTOM
        ===================================================== -->

        <div class="rider-sidebar-bottom">


            <div class="rider-sidebar-divider"></div>


            <form
                action="{{ route('logout') }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to log out?');"
            >

                @csrf

                <button
                    type="submit"
                    class="rider-sidebar-logout"
                >

                    <span class="sidebar-icon">
                        🚪
                    </span>

                    <span>
                        Logout
                    </span>

                </button>

            </form>


        </div>

    </aside>



    <!-- =========================================================
         MAIN
    ========================================================= -->

    <main class="main">


        <div class="content-wrapper">


            <!-- =====================================================
                 TOPBAR
            ===================================================== -->

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



            <!-- =====================================================
                 ALERTS
            ===================================================== -->

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



            <!-- =====================================================
                 FILTER
            ===================================================== -->

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

                    <option value="Out for Delivery">
                        Out for Delivery
                    </option>

                    <option value="Delivered">
                        Delivered
                    </option>

                </select>

            </div>



            <!-- =====================================================
                 DELIVERIES
            ===================================================== -->

            @if(count($deliveries ?? []) > 0)


                <div class="deliveries">


                    @foreach($deliveries as $delivery)


                        @php

                            $status =
                                $delivery['status']
                                ?? 'Pending';


                            $statusClass = 'pending';


                            if ($status === 'Ready for Pickup') {

                                $statusClass = 'ready';

                            }


                            if (
                                $status === 'Picked Up' ||
                                $status === 'Out for Delivery'
                            ) {

                                $statusClass = 'transit';

                            }


                            if ($status === 'Delivered') {

                                $statusClass = 'delivered';

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

                                    📦

                                    Order #{{ $delivery['id'] ?? 'N/A' }}

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



                            <!-- BUTTONS -->

                            <div class="buttons">


                                {{-- READY FOR PICKUP --}}

                                @if(
                                    $status === 'Ready for Pickup' &&
                                    empty($delivery['rider_id'] ?? null)
                                )


                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'rider.delivery.claim',
                                            $delivery['id']
                                        ) }}"
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



                                {{-- VIEW DETAILS --}}

                                <a
                                    href="{{ route(
                                        'rider.delivery.details',
                                        $delivery['id']
                                    ) }}"
                                    class="btn view-btn"
                                >

                                    👁 View Details

                                </a>



                                {{-- UPDATE STATUS --}}

                                @if(
                                    !empty($delivery['rider_id'] ?? null) &&
                                    $status !== 'Delivered'
                                )


                                    <a
                                        href="{{ route(
                                            'rider.delivery.details',
                                            $delivery['id']
                                        ) }}"
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



            <!-- =====================================================
                 FOOTER
            ===================================================== -->

            <div class="page-footer">

                <div>

                    © 2026

                    <strong>
                        BoomBuy
                    </strong>

                </div>


                <div>
                    Rider Center
                </div>

            </div>


        </div>


    </main>



    <!-- =========================================================
         FILTER SCRIPT
    ========================================================= -->

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

                            card.style.display = '';

                        } else {

                            card.style.display = 'none';

                        }

                    }
                );

            }
        );

    </script>


    @include('partials.pwa-register')

</body>

</html>