<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Order Details — BoomBuy</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .navbar {
            background: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #2563eb;
        }

        .seller {
            font-weight: 600;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 25px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,.06);
            margin-bottom: 20px;
        }

        h1 {
            margin-bottom: 10px;
        }

        .order-id {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .status {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 20px;
            background: #fff7ed;
            color: #c2410c;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 700;
            font-size: 17px;
        }

        .item-info {
            color: #6b7280;
            margin-top: 5px;
        }

        .price {
            font-weight: 700;
        }

        .summary {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
        }

        .total {
            font-size: 22px;
            font-weight: 800;
            color: #2563eb;
            border-top: 1px solid #e5e7eb;
            margin-top: 10px;
            padding-top: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-box {
            background: #f8fafc;
            padding: 18px;
            border-radius: 12px;
        }

        .label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .value {
            font-weight: 700;
        }

        form {
            margin-top: 20px;
        }

        select {
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            width: 100%;
            margin-bottom: 12px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .success {
            background: #ecfdf5;
            color: #047857;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .error {
            background: #fef2f2;
            color: #b91c1c;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        footer {
            text-align: center;
            padding: 30px;
            color: #6b7280;
        }

        @media (max-width: 700px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .item {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="logo">BoomBuy</div>

    <div class="seller">
        Seller: {{ $user['name'] ?? 'Seller' }}
    </div>
</nav>

<div class="container">

    <a href="{{ route('seller.orders') }}" class="back">
        ← Back to Seller Orders
    </a>

    @if(session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="error">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">

        <h1>Seller Order Details</h1>

        <div class="order-id">
            Order #{{ $order['id'] ?? 'N/A' }}
        </div>

        <div class="status">
            {{ $order['status'] ?? 'Pending' }}
        </div>

        <div class="info-grid">

            <div class="info-box">
                <div class="label">Buyer</div>
                <div class="value">
                    {{ $order['buyer_name'] ?? 'Buyer' }}
                </div>
            </div>

            <div class="info-box">
                <div class="label">Order Date</div>
                <div class="value">
                    {{ $order['date'] ?? 'N/A' }}
                </div>
            </div>

            <div class="info-box">
                <div class="label">Phone</div>
                <div class="value">
                    {{ $order['phone'] ?? 'N/A' }}
                </div>
            </div>

            <div class="info-box">
                <div class="label">Payment Method</div>
                <div class="value">
                    {{ $order['payment'] ?? 'N/A' }}
                </div>
            </div>

        </div>

    </div>


    <div class="card">

        <h2>Your Products in This Order</h2>

        @foreach(($order['items'] ?? []) as $item)

            <div class="item">

                <div>
                    <div class="item-name">
                        {{ $item['name'] ?? 'Product' }}
                    </div>

                    <div class="item-info">
                        Quantity:
                        {{ $item['quantity'] ?? 0 }}

                        • ₱{{ number_format($item['price'] ?? 0, 2) }}
                        each
                    </div>
                </div>

                <div class="price">
                    ₱{{ number_format($item['subtotal'] ?? 0, 2) }}
                </div>

            </div>

        @endforeach

        <div class="summary total">
            <span>Your Sales</span>

            <span>
                ₱{{ number_format($order['seller_total'] ?? 0, 2) }}
            </span>
        </div>

    </div>


    <div class="card">

        <h2>Delivery Information</h2>

        <div class="info-box" style="margin-top:20px;">

            <div class="label">
                Delivery Address
            </div>

            <div class="value">
                {{ $order['address'] ?? 'N/A' }}
            </div>

        </div>

    </div>


    <div class="card">

        <h2>Update Order Status</h2>

        <form
            method="POST"
            action="{{ route('seller.order.status', $order['id']) }}"
        >

            @csrf

            <select name="status" required>

                <option value="">
                    Select Status
                </option>

                <option value="Processing">
                    Processing
                </option>

                <option value="Ready for Pickup">
                    Ready for Pickup
                </option>

                <option value="Cancelled">
                    Cancelled
                </option>

            </select>

            <button type="submit">
                Update Order Status
            </button>

        </form>

    </div>

</div>

<footer>
    © 2026 <strong>BoomBuy</strong><br>
    Seller Order Management
</footer>

</body>
</html>
