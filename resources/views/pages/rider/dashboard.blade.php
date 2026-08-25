<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rider Dashboard — BoomBuy</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
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
            color: #f97316;
        }

        .menu-title {
            font-size: 12px;
            color: #9ca3af;
            text-transform: uppercase;
            margin: 20px 10px 10px;
            letter-spacing: 1px;
        }

        .menu a {
            display: block;
            text-decoration: none;
            color: #d1d5db;
            padding: 13px 12px;
            border-radius: 8px;
            margin-bottom: 6px;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #f97316;
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
            color: #6b7280;
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
            color: #6b7280;
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

        /* DELIVERY */

        .delivery {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .delivery-id {
            font-weight: bold;
        }

        .status {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: #fff7ed;
            color: #ea580c;
        }

        .delivery-info {
            color: #6b7280;
            line-height: 1.7;
            font-size: 14px;
        }

        .view-btn {
            display: inline-block;
            margin-top: 12px;
            padding: 9px 14px;
            background: #f97316;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-size: 13px;
        }

        .view-btn:hover {
            background: #ea580c;
        }

        .empty {
            text-align: center;
            padding: 35px 10px;
            color: #9ca3af;
        }

        /* QUICK ACTIONS */

        .quick-action {
            display: block;
            text-decoration: none;
            background: #f9fafb;
            padding: 15px;
            border-radius: 9px;
            margin-bottom: 10px;
            color: #374151;
            transition: 0.2s;
        }

        .quick-action:hover {
            background: #fff7ed;
            color: #ea580c;
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

        <a href="{{ route('rider.dashboard') }}"
           class="active">
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

        <form method="POST" action="{{ route('logout') }}">

            @csrf

            <button type="submit">
                🚪 Logout
            </button>

        </form>

    </div>

</aside>


<!-- MAIN -->

<main class="main">

    <div class="topbar">

        <div>

            <h1>Rider Dashboard</h1>

            <p class="welcome">
                Welcome back, {{ $user['name'] ?? 'Rider' }}!
            </p>

        </div>

        <div class="profile">

            🚴
            <strong>
                {{ $user['name'] ?? 'Rider' }}
            </strong>

        </div>

    </div>


    <!-- STAT CARDS -->

    @php

        $totalDeliveries = count($deliveries ?? []);

        $pendingDeliveries = 0;

        $pickedUp = 0;

        $delivered = 0;

        foreach (($deliveries ?? []) as $delivery) {

            $status = $delivery['status'] ?? 'Pending';

            if ($status === 'Pending') {
                $pendingDeliveries++;
            }

            if ($status === 'Picked Up' || $status === 'On the Way') {
                $pickedUp++;
            }

            if ($status === 'Delivered') {
                $delivered++;
            }

        }

    @endphp


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
                ⏳
            </div>

            <div class="card-title">
                Pending
            </div>

            <div class="card-number">
                {{ $pendingDeliveries }}
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
                {{ $pickedUp }}
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

        <!-- RECENT DELIVERIES -->

        <div class="panel">

            <h2>
                Recent Deliveries
            </h2>

            @if(count($deliveries ?? []) > 0)

                @foreach(array_slice($deliveries, 0, 5) as $delivery)

                    <div class="delivery">

                        <div class="delivery-header">

                            <div class="delivery-id">

                                Order #{{ $delivery['id'] ?? 'N/A' }}

                            </div>

                            <div class="status">

                                {{ $delivery['status'] ?? 'Pending' }}

                            </div>

                        </div>

                        <div class="delivery-info">

                            <div>
                                👤
                                Customer:
                                {{ $delivery['customer_name'] ?? 'Customer' }}
                            </div>

                            <div>
                                📍
                                Address:
                                {{ $delivery['address'] ?? 'No address provided' }}
                            </div>

                            <div>
                                💰
                                Amount:
                                ₱{{ number_format($delivery['amount'] ?? 0, 2) }}
                            </div>

                        </div>

                        @if(isset($delivery['id']))

                            <a
                                href="{{ route('rider.delivery.details', $delivery['id']) }}"
                                class="view-btn"
                            >
                                View Delivery
                            </a>

                        @endif

                    </div>

                @endforeach

            @else

                <div class="empty">

                    <div style="font-size:45px;">
                        🚚
                    </div>

                    <p>
                        No deliveries assigned yet.
                    </p>

                    <p style="margin-top:6px;font-size:13px;">
                        Your assigned orders will appear here.
                    </p>

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
                🚚 View My Deliveries
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

        </div>

    </section>

</main>

</body>
</html>