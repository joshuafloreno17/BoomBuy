<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>Delivery Details — BoomBuy</title>

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

    .main {
        margin-left: 250px;
        padding: 30px;
        max-width: 1100px;
    }

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    h1 {
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

    .card {
        background: white;
        border-radius: 14px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .order-id {
        font-size: 21px;
        font-weight: bold;
    }

    .status {
        padding: 8px 14px;
        border-radius: 20px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 13px;
        font-weight: bold;
    }

    .status.delivered {
        background: #dcfce7;
        color: #166534;
    }

    .status.pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status.transit {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .info {
        border-top: 1px solid #e5e7eb;
        padding-top: 15px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .label {
        color: #6b7280;
    }

    .value {
        font-weight: 600;
        text-align: right;
        word-break: break-word;
    }

    .items-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 15px;
    }

    .item {
        display: flex;
        justify-content: space-between;
        padding: 14px;
        background: #f9fafb;
        border-radius: 10px;
        margin-bottom: 10px;
        gap: 20px;
    }

    .item-name {
        font-weight: 600;
    }

    .item-details {
        color: #6b7280;
        font-size: 13px;
        margin-top: 4px;
    }

    .total {
        display: flex;
        justify-content: space-between;
        margin-top: 18px;
        padding-top: 15px;
        border-top: 2px solid #e5e7eb;
        font-size: 18px;
        font-weight: bold;
    }

    .update-box {
        margin-top: 20px;
    }

    .update-box h3 {
        margin-bottom: 12px;
    }

    select {
        width: 100%;
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: white;
        margin-bottom: 12px;
        font-size: 14px;
    }

    .btn {
        display: inline-block;
        width: 100%;
        border: none;
        padding: 12px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
    }

    .update-btn {
        background: #f97316;
        color: white;
    }

    .update-btn:hover {
        background: #ea580c;
    }

    .back-btn {
        display: inline-block;
        text-decoration: none;
        background: #111827;
        color: white;
        padding: 11px 16px;
        border-radius: 8px;
        margin-top: 10px;
    }

    .back-btn:hover {
        background: #374151;
    }

    .alert {
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .success {
        background: #dcfce7;
        color: #166534;
    }

    .error {
        background: #fee2e2;
        color: #991b1b;
    }

    .no-items {
        color: #9ca3af;
        padding: 10px 0;
    }

    @media (max-width: 700px) {
        .sidebar {
            width: 210px;
        }

        .main {
            margin-left: 210px;
            padding: 20px;
        }

        .topbar,
        .header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .info-row {
            flex-direction: column;
            gap: 5px;
        }

        .value {
            text-align: left;
        }
    }

    @media (max-width: 500px) {
        .sidebar {
            position: relative;
            width: 100%;
            height: auto;
            min-height: 500px;
        }

        .main {
            margin-left: 0;
        }
    }
</style>


</head>

<body>

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

    <a href="{{ route('rider.deliveries') }}" class="active">
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

<main class="main">


<div class="topbar">

    <div>

        <h1>
            Delivery Details
        </h1>

        <p class="subtitle">
            View and update your assigned delivery.
        </p>

    </div>

    <div class="profile">

        🚴

        <strong>
            {{ $user['name'] ?? 'Rider' }}
        </strong>

    </div>

</div>


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


<!-- ORDER INFORMATION -->

<div class="card">

    <div class="header">

        <div class="order-id">

            📦 Order #{{ $delivery['id'] ?? 'N/A' }}

        </div>

        @php
            $currentStatus = $delivery['status'] ?? 'Pending';

            $statusClass = 'pending';

            if (
                $currentStatus === 'Picked Up' ||
                $currentStatus === 'On the Way'
            ) {
                $statusClass = 'transit';
            }

            if ($currentStatus === 'Delivered') {
                $statusClass = 'delivered';
            }
        @endphp

        <div class="status {{ $statusClass }}">

            {{ $currentStatus }}

        </div>

    </div>


    <div class="info">

        <div class="info-row">

            <span class="label">
                Customer
            </span>

            <span class="value">
                {{ $delivery['buyer_name'] ?? 'Customer' }}
            </span>

        </div>


        <div class="info-row">

            <span class="label">
                Phone
            </span>

            <span class="value">
                {{ $delivery['phone'] ?? 'No phone provided' }}
            </span>

        </div>


        <div class="info-row">

            <span class="label">
                Delivery Address
            </span>

            <span class="value">
                {{ $delivery['address'] ?? 'No address provided' }}
            </span>

        </div>


        <div class="info-row">

            <span class="label">
                Payment
            </span>

            <span class="value">
                {{ $delivery['payment'] ?? 'Cash on Delivery' }}
            </span>

        </div>


        <div class="info-row">

            <span class="label">
                Order Date
            </span>

            <span class="value">
                {{ $delivery['date'] ?? 'N/A' }}
            </span>

        </div>


        @if(!empty($delivery['rider_name']))

            <div class="info-row">

                <span class="label">
                    Assigned Rider
                </span>

                <span class="value">
                    {{ $delivery['rider_name'] }}
                </span>

            </div>

        @endif

    </div>

</div>


<!-- ORDER ITEMS -->

<div class="card">

    <div class="items-title">
        🛒 Order Items
    </div>


    @if(
        !empty($delivery['items']) &&
        is_array($delivery['items'])
    )

        @foreach($delivery['items'] as $item)

            <div class="item">

                <div>

                    <div class="item-name">
                        {{ $item['name'] ?? 'Product' }}
                    </div>

                    <div class="item-details">

                        ₱{{ number_format($item['price'] ?? 0, 2) }}

                        ×

                        {{ $item['quantity'] ?? 1 }}

                    </div>

                </div>


                <strong>

                    ₱{{ number_format($item['subtotal'] ?? 0, 2) }}

                </strong>

            </div>

        @endforeach

    @else

        <p class="no-items">
            No item details available.
        </p>

    @endif


    <div class="total">

        <span>
            Total
        </span>

        <span>
            ₱{{ number_format($delivery['total'] ?? 0, 2) }}
        </span>

    </div>

</div>


<!-- UPDATE STATUS -->

@if(
    !empty($delivery['rider_id']) &&
    (string) $delivery['rider_id'] === (string) ($user['id'] ?? '')
)

    @if($currentStatus !== 'Delivered')

        <div class="card update-box">

            <h3>
                🔄 Update Delivery Status
            </h3>

            <form
                method="POST"
                action="{{ route('rider.delivery.status', $delivery['id']) }}"
            >

                @csrf

                <select name="status" required>

                    <option value="">
                        Select new status
                    </option>

                    @if($currentStatus === 'Picked Up')

                        <option value="On the Way">
                            On the Way
                        </option>

                    @endif

                    @if($currentStatus === 'On the Way')

                        <option value="Delivered">
                            Delivered
                        </option>

                    @endif

                </select>

                <button
                    type="submit"
                    class="btn update-btn"
                >
                    🔄 Update Status
                </button>

            </form>

        </div>

    @endif

@endif


<a
    href="{{ route('rider.deliveries') }}"
    class="back-btn"
>
    ← Back to My Deliveries
</a>


</main>

</body>
</html>
