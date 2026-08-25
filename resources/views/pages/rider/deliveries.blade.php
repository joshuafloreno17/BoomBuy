<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Deliveries — BoomBuy</title>

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

        .subtitle {
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

        /* ALERT */

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

        /* FILTER */

        .filter-box {
            background: white;
            padding: 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .filter-box label {
            font-size: 13px;
            color: #6b7280;
            margin-right: 10px;
        }

        .filter-box select {
            padding: 10px 13px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            background: white;
        }

        /* DELIVERY CARDS */

        .deliveries {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .delivery-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .order-id {
            font-size: 18px;
            font-weight: bold;
        }

        .status {
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: #fff7ed;
            color: #ea580c;
        }

        .status.delivered {
            background: #dcfce7;
            color: #166534;
        }

        .status.transit {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .info {
            border-top: 1px solid #e5e7eb;
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
            color: #6b7280;
        }

        .info-value {
            font-weight: 500;
            text-align: right;
        }

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
        }

        .view-btn {
            background: #f97316;
            color: white;
        }

        .view-btn:hover {
            background: #ea580c;
        }

        .status-btn {
            background: #111827;
            color: white;
        }

        .status-btn:hover {
            background: #374151;
        }

        /* EMPTY */

        .empty {
            background: white;
            border-radius: 14px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .empty h2 {
            margin-bottom: 8px;
        }

        .empty p {
            color: #9ca3af;
        }

        /* RESPONSIVE */

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

        <a href="{{ route('rider.dashboard') }}">
            🏠 Dashboard
        </a>

        <a href="{{ route('rider.deliveries') }}"
           class="active">
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

            <h1>My Deliveries</h1>

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


    <!-- ALERTS -->

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


    <!-- FILTER -->

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


    <!-- DELIVERIES -->

    @if(count($deliveries ?? []) > 0)

        <div class="deliveries">

            @foreach($deliveries as $delivery)

                @php

                    $status =
                        $delivery['status']
                        ?? 'Pending';

                    $statusClass = 'pending';

                    if ($status === 'Picked Up' ||
                        $status === 'On the Way') {

                        $statusClass = 'transit';

                    }

                    if ($status === 'Delivered') {

                        $statusClass = 'delivered';

                    }

                @endphp


                <div
                    class="delivery-card"
                    data-status="{{ $status }}"
                >

                    <div class="delivery-header">

                        <div class="order-id">

                            📦 Order #{{ $delivery['id'] ?? 'N/A' }}

                        </div>

                        <div class="status {{ $statusClass }}">

                            {{ $status }}

                        </div>

                    </div>


                    <div class="info">

                        <div class="info-row">

                            <span class="info-label">
                                Customer
                            </span>

                            <span class="info-value">

                                {{ $delivery['customer_name'] ?? 'Customer' }}

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

                                ₱{{ number_format($delivery['amount'] ?? 0, 2) }}

                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Payment
                            </span>

                            <span class="info-value">

                                {{ $delivery['payment_method'] ?? 'Cash on Delivery' }}

                            </span>

                        </div>


                        @if(isset($delivery['items']))

                            <div class="info-row">

                                <span class="info-label">
                                    Items
                                </span>

                                <span class="info-value">

                                    {{ $delivery['items'] }}

                                </span>

                            </div>

                        @endif

                    </div>


                    <div class="buttons">

                        <a
                            href="{{ route('rider.delivery.details', $delivery['id']) }}"
                            class="btn view-btn"
                        >
                            👁 View Details
                        </a>

                        @if($status !== 'Delivered')

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


<script>

    const statusFilter =
        document.getElementById('statusFilter');

    const deliveryCards =
        document.querySelectorAll('.delivery-card');


    statusFilter.addEventListener('change', function () {

        const selected =
            this.value;

        deliveryCards.forEach(function (card) {

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

        });

    });

</script>

</body>
</html>