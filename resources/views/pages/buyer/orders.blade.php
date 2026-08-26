<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Orders — BoomBuy</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .navbar {
            background: #ffffff;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
            color: #2563eb;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .nav-links a {
            text-decoration: none;
            color: #374151;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: #2563eb;
        }

        .user {
            background: #eff6ff;
            color: #2563eb;
            padding: 9px 15px;
            border-radius: 20px;
            font-weight: 600;
        }

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
            color: #6b7280;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

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
            border: 1px solid #e5e7eb;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding-bottom: 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        .order-id {
            font-size: 18px;
            font-weight: 700;
        }

        .date {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }

        .status {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            background: #fff7ed;
            color: #c2410c;
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
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }

        .item-price {
            font-weight: 700;
            white-space: nowrap;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .info-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
        }

        .info-box span {
            display: block;
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .info-box strong {
            font-size: 14px;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
        }

        .total {
            font-size: 21px;
            font-weight: 800;
            color: #2563eb;
        }

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
            color: #6b7280;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 11px 18px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
        }

        .btn:hover {
            background: #1d4ed8;
        }

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
                gap: 10px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">

    <a href="{{ route('buyer.dashboard') }}" class="logo">
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


<div class="container">

    <div class="page-header">

        <h1>My Orders</h1>

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

            <h2>No Orders Yet</h2>

            <p>
                You haven't placed any orders yet.
            </p>

            <a href="{{ route('buyer.dashboard') }}" class="btn">
                Start Shopping
            </a>

        </div>

    @else

        <div class="orders">

            @foreach($orders as $order)

                <div class="order-card">

                    <div class="order-header">

                        <div>

                            <div class="order-id">
                                Order #{{ $order['id'] }}
                            </div>

                            <div class="date">
                                {{ $order['date'] ?? 'N/A' }}
                            </div>

                        </div>

                        <div class="status">
                            {{ $order['status'] ?? 'Pending' }}
                        </div>

                    </div>


                    <div class="items">

                        @foreach($order['items'] ?? [] as $item)

                            <div class="item">

                                <div>

                                    <div class="item-name">
                                        {{ $item['name'] ?? 'Product' }}
                                    </div>

                                    <div class="item-info">

                                        Quantity:
                                        {{ $item['quantity'] ?? 1 }}

                                        @if(!empty($item['seller_name']))
                                            • Seller:
                                            {{ $item['seller_name'] }}
                                        @endif

                                    </div>

                                </div>

                                <div class="item-price">
                                    ₱{{ number_format($item['subtotal'] ?? 0, 2) }}
                                </div>

                            </div>

                        @endforeach

                    </div>


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


                    <div class="order-footer">

                        <div>

                            <span>
                                Order Total:
                            </span>

                            <div class="total">
                                ₱{{ number_format($order['total'] ?? 0, 2) }}
                            </div>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    @endif

</div>

</body>
</html>