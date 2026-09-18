<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Orders - BoomBuy</title>

    @include('partials.pwa-head')

    <style>
@import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

.received-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    margin-top: 8px;

    padding: 7px 11px;

    border-radius: 999px;

    background: #e9f8ef;
    color: #087a3d;

    border: 1px solid #ccefd9;

    font-size: 11px;
    font-weight: 800;

    white-space: nowrap;
}

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        .navbar {
            background: white;
            border-bottom: 1px solid #f6e1db;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
            color: #f13f09;
        }

        .logo span {
            color: #172033;
        }

        .back {
            text-decoration: none;
            color: #f13f09;
            font-weight: 600;
        }

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 40px auto;
        }

        h1 {
            margin-bottom: 8px;
        }

        .subtitle {
            color: #8e7067;
            margin-bottom: 28px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: #e9f8ef;
            color: #087a3d;
        }

        .empty {
            background: white;
            border: 1px solid #f6e1db;
            border-radius: 14px;
            padding: 50px;
            text-align: center;
            color: #8e7067;
        }

        .order-card {
            background: white;
            border: 1px solid #f6e1db;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 18px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
        }

        .order-id {
            font-weight: 800;
            font-size: 18px;
        }

        .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            background: #fff4d6;
            color: #9a6700;
            font-size: 13px;
            font-weight: 700;
        }

        .info {
            color: #7c5f57;
            line-height: 1.7;
        }

        .items {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f6efed;
        }

        .item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f6efed;
            font-size: 18px;
            font-weight: 800;
            display: flex;
            justify-content: space-between;
        }

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            cursor: pointer;
            font-weight: 700;
        }

        .btn-blue {
            background: #f13f09;
            color: white;
        }

        .btn-green {
            background: #198754;
            color: white;
        }

        .btn-gray {
            background: #f7f0ee;
            color: #553b33;
        }

        select {
            padding: 10px 12px;
            border: 1px solid #e6d1cb;
            border-radius: 8px;
            background: white;
        }

        .details {
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 8px;
            background: #fff2ee;
            color: #f13f09;
            font-weight: 700;
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

@media (max-width: 640px) {
    .navbar { padding: 14px 5%; }
    .container { width: 92%; margin: 24px auto; }
    .order-header { flex-wrap: wrap; gap: 8px; }
    .order-card { padding: 16px; }
    .actions { flex-direction: column; align-items: stretch; }
    .actions .btn, .actions select, .actions .details { width: 100%; text-align: center; }
    h1 { font-size: 22px; }
}
</style>
</head>

<body>

<nav class="navbar">
    <div class="logo">
        Boom<span>Buy</span>
    </div>

    <a href="{{ route('admin.dashboard') }}" class="back">
        ← Admin Dashboard
    </a>
</nav>

<div class="container">

    <h1>Orders</h1>
    <div class="subtitle">
        Manage all BoomBuy customer orders.
    </div>

    @if(session('success'))
        <div class="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><polyline points="20 6 9 17 4 12"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert" style="background:#fdecec;color:#b42318;">
            {{ session('error') }}
        </div>
    @endif


    @if(empty($orders))

        <div class="empty">
            <h2>No Orders Yet</h2>
            <p>There are currently no orders in BoomBuy.</p>
        </div>

    @else

        @foreach($orders as $order)

            <div class="order-card">

                <div class="order-header">

                    <div>
                        <div class="order-id">
                            {{ $order['id'] ?? 'Unknown Order' }}
                        </div>

                        <div class="info">
                            {{ $order['date'] ?? '' }}
                        </div>
                    </div>

                <div>

    <div class="status">
        {{ $order['status'] ?? 'Pending' }}
    </div>

    @if(!empty($order['buyer_received_at']))

        <div class="received-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><polyline points="20 6 9 17 4 12"/></svg>
            Received by Buyer
        </div>

    @endif

</div>

                </div>


                <div class="info">

                    <strong>Buyer:</strong>
                    {{ $order['buyer_name'] ?? 'Buyer' }}

                    <br>

                    <strong>Email:</strong>
                    {{ $order['buyer_email'] ?? 'N/A' }}

                    <br>

                    <strong>Phone:</strong>
                    {{ $order['phone'] ?? 'N/A' }}

                    <br>

                    <strong>Address:</strong>
                    {{ $order['address'] ?? 'N/A' }}

                    <br>

                    <strong>Payment:</strong>
                    {{ $order['payment'] ?? 'N/A' }}

                </div>


                <div class="items">

                    <strong>Order Items</strong>

                    @foreach(($order['items'] ?? []) as $item)

                        <div class="item">

                            <span>
                                {{ $item['name'] ?? 'Product' }}
                                × {{ $item['quantity'] ?? 1 }}
                            </span>

                            <span>
                                ₱{{ number_format($item['subtotal'] ?? 0, 2) }}
                            </span>

                        </div>

                    @endforeach

                </div>


                <div class="total">

                    <span>Total</span>

                    <span>
                        ₱{{ number_format($order['total'] ?? 0, 2) }}
                    </span>

                </div>


                @if(!empty($order['rider_name']))

                    <div class="info" style="margin-top:15px;">
                        <strong>Rider:</strong>
                        {{ $order['rider_name'] }}
                    </div>

                @endif


                <div class="actions">

                    <a
                        href="{{ route('admin.order.details', $order['id']) }}"
                        class="details"
                    >
                        View Details
                    </a>


                    <form
                        method="POST"
                        action="{{ route('admin.order.status', $order['id']) }}"
                    >

                        @csrf

                        <select name="status">

                            <option value="Pending"
                                {{ ($order['status'] ?? '') === 'Pending' ? 'selected' : '' }}>
                                Pending
                            </option>

                            <option value="Processing"
                                {{ ($order['status'] ?? '') === 'Processing' ? 'selected' : '' }}>
                                Processing
                            </option>

                            <option value="Ready for Pickup"
                                {{ ($order['status'] ?? '') === 'Ready for Pickup' ? 'selected' : '' }}>
                                Ready for Pickup
                            </option>

                            <option value="Picked Up"
                                {{ ($order['status'] ?? '') === 'Picked Up' ? 'selected' : '' }}>
                                Picked Up
                            </option>

                            <option value="On the Way"
                                {{ ($order['status'] ?? '') === 'On the Way' ? 'selected' : '' }}>
                                On the Way
                            </option>

                            <option value="Delivered"
                                {{ ($order['status'] ?? '') === 'Delivered' ? 'selected' : '' }}>
                                Delivered
                            </option>

                            <option value="Cancelled"
                                {{ ($order['status'] ?? '') === 'Cancelled' ? 'selected' : '' }}>
                                Cancelled
                            </option>

                        </select>

                        <button
                            type="submit"
                            class="btn btn-blue"
                        >
                            Update Status
                        </button>

                    </form>

                </div>

            </div>

        @endforeach

    @endif

</div>

    @include('partials.pwa-register')

</body>
</html>