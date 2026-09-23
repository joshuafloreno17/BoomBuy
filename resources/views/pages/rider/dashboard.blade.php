<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy — Rider Dashboard</title>

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
        input {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* =========================================================
           SIDEBAR
        ========================================================= */

        .rider-sidebar {
            position: fixed;
            top: 0;
            left: 0;

            width: 245px;
            height: 100vh;

            background: #ffffff;
            border-right: 1px solid #f7e5e0;

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            padding: 24px 16px;

            z-index: 2000;
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

            padding: 8px 10px 25px;

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

            font-size: 20px;
            flex-shrink: 0;
        }

        .rider-brand-name {
            font-family: 'Baloo 2', sans-serif;
            font-size: 25px;
            font-weight: 800;
            line-height: 1;
        }

        .rider-brand-role {
            color: #977970;
            font-size: 10px;
            font-weight: 700;

            margin-top: 4px;
        }

        /* =========================================================
           SIDEBAR NAVIGATION
        ========================================================= */

        .rider-sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .rider-sidebar-link {
            position: relative;

            display: flex;
            align-items: center;
            gap: 12px;

            width: 100%;

            padding: 12px 13px;

            border-radius: 10px;

            color: #6f5d58;

            font-size: 12px;
            font-weight: 700;

            transition: .2s ease;
        }

        .rider-sidebar-link:hover {
            background: #fff4f0;
            color: #e8420f;
        }

        .rider-sidebar-link.active {
            background: #e8420f;
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(232, 66, 15, .14);
        }

        .sidebar-icon {
            width: 22px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            font-size: 16px;
            flex-shrink: 0;
        }

        .sidebar-notification-badge {
            margin-left: auto;

            min-width: 20px;
            height: 20px;

            padding: 0 5px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #e8420f;
            color: #ffffff;

            border-radius: 999px;

            font-size: 9px;
            font-weight: 800;
        }

        .rider-sidebar-link.active .sidebar-notification-badge {
            background: #ffffff;
            color: #e8420f;
        }

        /* =========================================================
           SIDEBAR BOTTOM
        ========================================================= */

        .rider-sidebar-bottom {
            width: 100%;
        }

        .rider-sidebar-divider {
            height: 1px;

            background: #f7e5e0;

            margin: 10px 0 12px;
        }

        .rider-sidebar-bottom form {
            width: 100%;
        }

        .rider-sidebar-logout {
            width: 100%;

            display: flex;
            align-items: center;
            gap: 12px;

            padding: 12px 13px;

            border: none;
            background: transparent;

            border-radius: 10px;

            color: #e8420f;

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

        .main-content {
            width: calc(100% - 245px);
            min-height: calc(100vh - 74px);

            margin-left: 245px;
        }

        .container {
            width: calc(100% - 40px);
            max-width: 1200px;

            margin: 45px auto 80px;
        }

        /* =========================================================
           WELCOME
        ========================================================= */

        .welcome {
            width: 100%;

            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 18px;

            padding: 30px;
            margin-bottom: 30px;

            overflow: hidden;
        }

        .welcome small {
            color: #db5a33;

            text-transform: uppercase;
            letter-spacing: 2px;

            font-size: 11px;
            font-weight: 800;
        }

        .welcome h1 {
            margin-top: 7px;

            font-family: 'Baloo 2', sans-serif;
            font-size: 35px;
            line-height: 1.15;
        }

        .welcome p {
            margin-top: 9px;

            color: #977970;

            font-size: 14px;
            line-height: 1.6;
        }

        /* =========================================================
           QUICK ACTIONS
        ========================================================= */

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));

            gap: 15px;

            margin-bottom: 35px;
        }

        .quick-card {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 14px;

            padding: 18px;

            transition: .2s ease;
        }

        .quick-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 8px 25px rgba(232, 66, 15, .08);
        }

        .quick-card-icon {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #fff0eb;
            border-radius: 10px;

            margin-bottom: 12px;

            font-size: 18px;
        }

        .quick-card strong {
            display: block;

            color: #172033;

            font-size: 14px;

            margin-bottom: 5px;
        }

        .quick-card span {
            color: #977970;

            font-size: 12px;
            line-height: 1.5;
        }

        /* =========================================================
           STATS
        ========================================================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));

            gap: 15px;

            margin-bottom: 38px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 14px;

            padding: 20px;

            transition: .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 8px 25px rgba(232, 66, 15, .08);
        }

        .stat-icon {
            width: 42px;
            height: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #fff0eb;
            border-radius: 11px;

            margin-bottom: 12px;

            font-size: 20px;
        }

        .stat-title {
            color: #977970;

            font-size: 12px;
            font-weight: 600;
        }

        .stat-number {
            margin-top: 2px;

            color: #172033;

            font-family: 'Baloo 2', sans-serif;
            font-size: 29px;
            font-weight: 800;
        }

        /* =========================================================
           SECTION
        ========================================================= */

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            gap: 15px;

            margin-bottom: 18px;
        }

        .section-title h2 {
            font-family: 'Baloo 2', sans-serif;

            font-size: 24px;
            line-height: 1.2;
        }

        .section-title p {
            color: #977970;

            font-size: 13px;
            line-height: 1.5;

            margin-top: 5px;
        }

        .view-all {
            flex-shrink: 0;

            color: #e8420f;

            font-size: 13px;
            font-weight: 800;

            padding-top: 4px;
        }

        .view-all:hover {
            color: #c43408;
        }

        /* =========================================================
           DELIVERY LIST
        ========================================================= */

        .delivery-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));

            gap: 18px;

            margin-bottom: 35px;
        }

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
                0 8px 25px rgba(232, 66, 15, .08);
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 10px;

            margin-bottom: 14px;
        }

        .delivery-id {
            color: #172033;

            font-family: 'Baloo 2', sans-serif;

            font-size: 18px;
            font-weight: 800;
        }

        /* =========================================================
           STATUS
        ========================================================= */

        .status {
            padding: 6px 10px;

            border-radius: 20px;

            font-size: 10px;
            font-weight: 800;

            white-space: nowrap;
        }

        .status-available {
            background: #fff0eb;
            color: #e8420f;
        }

        .status-picked {
            background: #fff4ed;
            color: #db5a33;
        }

        .status-way {
            background: #fff8df;
            color: #b77900;
        }

        .status-delivered {
            background: #ecfdf5;
            color: #059669;
        }

        /* =========================================================
           LABELS
        ========================================================= */

        .available-label {
            background: #fff5f1;
            border: 1px solid #f9d8ce;
            color: #c84a27;

            padding: 11px 13px;

            border-radius: 9px;

            font-size: 12px;
            line-height: 1.5;

            margin-bottom: 14px;
        }

        .active-label {
            background: #fff8ed;
            border: 1px solid #f9dfbd;
            color: #b86b17;

            padding: 11px 13px;

            border-radius: 9px;

            font-size: 12px;
            line-height: 1.5;

            margin-bottom: 14px;
        }

        /* =========================================================
           INFO
        ========================================================= */

        .delivery-info {
            color: #977970;

            font-size: 12px;
            line-height: 1.9;
        }

        .delivery-info strong {
            color: #523d36;
        }

        /* =========================================================
           BUTTONS
        ========================================================= */

        .delivery-actions {
            display: flex;
            align-items: center;

            gap: 8px;
            flex-wrap: wrap;

            margin-top: 16px;
        }

        .view-btn,
        .claim-btn,
        .status-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 9px 13px;

            border-radius: 8px;

            font-size: 11px;
            font-weight: 800;

            cursor: pointer;

            transition: .2s ease;
        }

        .view-btn {
            background: #e8420f;

            color: #ffffff;

            border: none;
        }

        .view-btn:hover {
            background: #c43408;
        }

        .claim-btn {
            background: #16a34a;

            color: #ffffff;

            border: none;
        }

        .claim-btn:hover {
            background: #15803d;
        }

        .status-btn {
            background: #fff0eb;

            color: #e8420f;

            border: 1px solid #f6cfc4;
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

            padding: 45px 20px;

            text-align: center;

            color: #977970;

            margin-bottom: 35px;
        }

        .empty-icon {
            width: 58px;
            height: 58px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #fff0eb;
            border-radius: 16px;

            margin: 0 auto 12px;

            font-size: 25px;
        }

        .empty-title {
            color: #523d36;

            font-family: 'Baloo 2', sans-serif;

            font-size: 19px;
            font-weight: 800;
        }

        .empty-text {
            margin-top: 5px;

            font-size: 12px;
            line-height: 1.6;
        }

        /* =========================================================
           RIDER TIP
        ========================================================= */

        .rider-tip {
            background: #fff8ed;
            border: 1px solid #f9dfbd;
            border-radius: 14px;

            padding: 18px;

            margin-top: 20px;
        }

        .rider-tip-title {
            color: #b86b17;

            font-size: 13px;
            font-weight: 800;

            margin-bottom: 6px;
        }

        .rider-tip-text {
            color: #7c6012;

            font-size: 12px;
            line-height: 1.6;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        footer {
            width: calc(100% - 245px);

            margin-left: 245px;

            background: #ffffff;

            border-top: 1px solid #f7e5e0;

            padding: 30px 7%;

            display: flex;
            justify-content: space-between;

            gap: 15px;

            color: #977970;

            font-size: 12px;
        }

        footer strong {
            color: #e8420f;
        }

        /* =========================================================
           RESPONSIVE - TABLET
        ========================================================= */

        @media (max-width: 1050px) {

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .delivery-list {
                grid-template-columns: 1fr;
            }

        }

        /* =========================================================
           RESPONSIVE - SMALL TABLET
        ========================================================= */

        @media (max-width: 800px) {

            .rider-sidebar {
                width: 75px;

                padding: 18px 10px;
            }

            .rider-brand {
                justify-content: center;

                padding: 8px 0 25px;
            }

            .rider-brand-name,
            .rider-brand-role,
            .rider-sidebar-link span:not(.sidebar-icon),
            .rider-sidebar-logout span:not(.sidebar-icon) {
                display: none;
            }

            .rider-sidebar-link,
            .rider-sidebar-logout {
                justify-content: center;

                padding: 13px 8px;
            }

            .sidebar-icon {
                width: auto;

                font-size: 18px;
            }

            .sidebar-notification-badge {
                position: absolute;

                top: 3px;
                right: 3px;

                min-width: 17px;
                height: 17px;

                font-size: 8px;
            }

            .main-content {
                width: calc(100% - 75px);

                margin-left: 75px;
            }

            footer {
                width: calc(100% - 75px);

                margin-left: 75px;
            }

        }

        /* =========================================================
           RESPONSIVE - MOBILE
        ========================================================= */

        @media (max-width: 600px) {

            .container {
                width: calc(100% - 24px);

                margin-top: 25px;
                margin-bottom: 50px;
            }

            .welcome {
                padding: 22px;
            }

            .welcome h1 {
                font-size: 28px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .section-title {
                flex-wrap: wrap;
            }

            footer {
                flex-direction: column;

                text-align: center;

                padding: 25px 15px;
            }

        }

        /* =========================================================
           RESPONSIVE - SMALL PHONE
        ========================================================= */

        @media (max-width: 450px) {

            .rider-sidebar {
                width: 64px;
            }

            .main-content {
                width: calc(100% - 64px);

                margin-left: 64px;
            }

            footer {
                width: calc(100% - 64px);

                margin-left: 64px;
            }

            .container {
                width: calc(100% - 16px);
            }

            .welcome {
                padding: 18px;
            }

            .welcome h1 {
                font-size: 24px;
            }

            .delivery-card {
                padding: 16px;
            }

            .delivery-header {
                align-items: flex-start;

                flex-direction: column;
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
                    class="rider-sidebar-link {{ request()->routeIs('rider.dashboard') ? 'active' : '' }}"
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
                    class="rider-sidebar-link {{ request()->routeIs('rider.deliveries') ? 'active' : '' }}"
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
                    class="rider-sidebar-link {{ request()->routeIs('rider.profile') ? 'active' : '' }}"
                >

                    <span class="sidebar-icon">
                        👤
                    </span>

                    <span>
                        My Profile
                    </span>

                </a>


                <a
                    href="{{ route('rider.notifications') }}"
                    class="rider-sidebar-link {{ request()->routeIs('rider.notifications') ? 'active' : '' }}"
                >

                    <span class="sidebar-icon">
                        🔔
                    </span>

                    <span>
                        Notifications
                    </span>

                    @if(($riderUnreadNotifications ?? 0) > 0)

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


        <!-- SIDEBAR BOTTOM -->

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
         DASHBOARD DATA
    ========================================================= -->

    @php

        $availableDeliveries = $availableOrders ?? [];

        $myDeliveriesList = $myDeliveries ?? [];

        $availableCount = count($availableDeliveries);

        $totalDeliveries = count($myDeliveriesList);

        $inTransitCount = count($inTransit ?? []);

        $deliveredCount = count($delivered ?? []);

        $myActiveDeliveries = array_values(
            array_filter(
                $myDeliveriesList,
                function ($delivery) {

                    $status = $delivery['status'] ?? '';

                    return $status !== 'Delivered';

                }
            )
        );

        $riderUnreadNotifications =
            \App\Models\Notification::where('user_id', $user['id'])
            ->whereNull('read_at')
            ->count();

    @endphp


    <!-- =========================================================
         MAIN
    ========================================================= -->

    <main class="main-content">

        <div class="container">


            <!-- =====================================================
                 WELCOME
            ===================================================== -->

            <section class="welcome">

                <small>
                    Rider Center
                </small>

                <h1>
                    Welcome,
                    {{ $user['name'] ?? 'Rider' }}! 🛵
                </h1>

                <p>
                    Manage your deliveries, claim available orders,
                    and keep track of your delivery progress.
                </p>

            </section>


            <!-- =====================================================
                 QUICK ACTIONS
            ===================================================== -->

            <div class="quick-actions">

                <a
                    href="{{ route('rider.deliveries') }}"
                    class="quick-card"
                >

                    <div class="quick-card-icon">
                        🚚
                    </div>

                    <strong>
                        My Deliveries
                    </strong>

                    <span>
                        View available and assigned deliveries.
                    </span>

                </a>


                <a
                    href="{{ route('rider.profile') }}"
                    class="quick-card"
                >

                    <div class="quick-card-icon">
                        👤
                    </div>

                    <strong>
                        My Profile
                    </strong>

                    <span>
                        View and manage your rider information.
                    </span>

                </a>


                <a
                    href="{{ route('rider.notifications') }}"
                    class="quick-card"
                >

                    <div class="quick-card-icon">
                        🔔
                    </div>

                    <strong>
                        Notifications
                    </strong>

                    <span>
                        Check your latest rider updates.
                    </span>

                </a>

            </div>


            <!-- =====================================================
                 STATISTICS
            ===================================================== -->

            <section class="stats">

                <div class="stat-card">

                    <div class="stat-icon">
                        📦
                    </div>

                    <div class="stat-title">
                        Total Deliveries
                    </div>

                    <div class="stat-number">
                        {{ $totalDeliveries }}
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        🟢
                    </div>

                    <div class="stat-title">
                        Available Orders
                    </div>

                    <div class="stat-number">
                        {{ $availableCount }}
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        🚚
                    </div>

                    <div class="stat-title">
                        In Transit
                    </div>

                    <div class="stat-number">
                        {{ $inTransitCount }}
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ✅
                    </div>

                    <div class="stat-title">
                        Delivered
                    </div>

                    <div class="stat-number">
                        {{ $deliveredCount }}
                    </div>

                </div>

            </section>


            <!-- =====================================================
                 AVAILABLE ORDERS
            ===================================================== -->

            <div class="section-title">

                <div>

                    <h2>
                        Available Orders
                    </h2>

                    <p>
                        Orders that are ready for pickup.
                    </p>

                </div>

                <a
                    href="{{ route('rider.deliveries') }}"
                    class="view-all"
                >
                    View All →
                </a>

            </div>


            @if(count($availableDeliveries) > 0)

                <div class="delivery-list">

                    @foreach(
                        array_slice($availableDeliveries, 0, 6)
                        as $delivery
                    )

                        <div class="delivery-card">

                            <div class="delivery-header">

                                <div class="delivery-id">

                                    📦

                                    Order #{{ $delivery['id'] ?? 'N/A' }}

                                </div>

                                <div class="status status-available">
                                    Ready for Pickup
                                </div>

                            </div>


                            <div class="available-label">

                                🚚 This order is available for pickup.

                            </div>


                            <div class="delivery-info">

                                <div>

                                    👤

                                    <strong>
                                        Customer:
                                    </strong>

                                    {{ $delivery['buyer_name'] ?? 'Customer' }}

                                </div>


                                <div>

                                    📍

                                    <strong>
                                        Address:
                                    </strong>

                                    {{ $delivery['address'] ?? 'No address provided' }}

                                </div>


                                <div>

                                    📞

                                    <strong>
                                        Phone:
                                    </strong>

                                    {{ $delivery['phone'] ?? 'No phone provided' }}

                                </div>


                                <div>

                                    💰

                                    <strong>
                                        Total:
                                    </strong>

                                    ₱{{ number_format($delivery['total'] ?? 0, 2) }}

                                </div>


                                <div>

                                    💳

                                    <strong>
                                        Payment:
                                    </strong>

                                    {{ $delivery['payment'] ?? 'N/A' }}

                                </div>


                                <div>

                                    🛒

                                    <strong>
                                        Items:
                                    </strong>

                                    {{ count($delivery['items'] ?? []) }}

                                </div>

                            </div>


                            <div class="delivery-actions">

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

                </div>

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

                        <strong>
                            "Ready for Pickup"
                        </strong>

                        will appear here.

                    </div>

                </div>

            @endif


            <!-- =====================================================
                 ACTIVE DELIVERIES
            ===================================================== -->

            <div class="section-title">

                <div>

                    <h2>
                        My Active Deliveries
                    </h2>

                    <p>
                        Track the orders currently assigned to you.
                    </p>

                </div>

                <a
                    href="{{ route('rider.deliveries') }}"
                    class="view-all"
                >
                    View All →
                </a>

            </div>


            @if(count($myActiveDeliveries) > 0)

                <div class="delivery-list">

                    @foreach(
                        array_slice($myActiveDeliveries, 0, 6)
                        as $delivery
                    )

                        @php

                            $status =
                                $delivery['status']
                                ?? 'Picked Up';

                            $statusClass =
                                $status === 'Out for Delivery'
                                ? 'status-way'
                                : 'status-picked';

                        @endphp


                        <div class="delivery-card">

                            <div class="delivery-header">

                                <div class="delivery-id">

                                    📦

                                    Order #{{ $delivery['id'] ?? 'N/A' }}

                                </div>

                                <div class="status {{ $statusClass }}">
                                    {{ $status }}
                                </div>

                            </div>


                            <div class="active-label">

                                🚚 This order is assigned to you.

                                @if($status === 'Picked Up')

                                    Continue the delivery process.

                                @elseif($status === 'Out for Delivery')

                                    This order is currently out for delivery.

                                @endif

                            </div>


                            <div class="delivery-info">

                                <div>

                                    👤

                                    <strong>
                                        Customer:
                                    </strong>

                                    {{ $delivery['buyer_name'] ?? 'Customer' }}

                                </div>


                                <div>

                                    📍

                                    <strong>
                                        Address:
                                    </strong>

                                    {{ $delivery['address'] ?? 'No address provided' }}

                                </div>


                                <div>

                                    📞

                                    <strong>
                                        Phone:
                                    </strong>

                                    {{ $delivery['phone'] ?? 'No phone provided' }}

                                </div>


                                <div>

                                    💰

                                    <strong>
                                        Total:
                                    </strong>

                                    ₱{{ number_format($delivery['total'] ?? 0, 2) }}

                                </div>


                                <div>

                                    💳

                                    <strong>
                                        Payment:
                                    </strong>

                                    {{ $delivery['payment'] ?? 'N/A' }}

                                </div>


                                <div>

                                    🛒

                                    <strong>
                                        Items:
                                    </strong>

                                    {{ count($delivery['items'] ?? []) }}

                                </div>

                            </div>


                            <div class="delivery-actions">

                                <a
                                    href="{{ route(
                                        'rider.delivery.details',
                                        $delivery['id']
                                    ) }}"
                                    class="view-btn"
                                >
                                    👁 View Details
                                </a>


                                <a
                                    href="{{ route(
                                        'rider.delivery.details',
                                        $delivery['id']
                                    ) }}"
                                    class="status-btn"
                                >
                                    🔄 Update Status
                                </a>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="empty">

                    <div class="empty-icon">
                        🚚
                    </div>

                    <div class="empty-title">
                        No Active Deliveries
                    </div>

                    <div class="empty-text">
                        Orders you claim will appear here.
                    </div>

                </div>

            @endif


            <!-- =====================================================
                 RIDER TIP
            ===================================================== -->

            <div class="rider-tip">

                <div class="rider-tip-title">
                    💡 Rider Tip
                </div>

                <div class="rider-tip-text">

                    Orders become available after the seller
                    changes the order status to
                    <strong>
                        Ready for Pickup
                    </strong>.

                    Claim the order to start your delivery.

                </div>

            </div>


        </div>

    </main>


    <!-- =========================================================
         FOOTER
    ========================================================= -->

    <footer>

        <div>

            © 2026

            <strong>
                BoomBuy
            </strong>

        </div>

        <div>
            Your Marketplace for Everything
        </div>

    </footer>


    @include('partials.pwa-register')

</body>

</html>