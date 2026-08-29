<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Orders — BoomBuy</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f6f8fb;
            color: #1f2937;
        }

        .navbar {
            background: white;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .brand {
            font-size: 24px;
            font-weight: 800;
            color: #111827;
            text-decoration: none;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .seller-name {
            font-weight: 600;
        }

        .logout button {
            border: none;
            background: #ef4444;
            color: white;
            padding: 9px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .empty {
            background: white;
            padding: 50px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 18px rgba(0,0,0,.06);
        }

        .order-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 18px rgba(0,0,0,.06);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .order-id {
            font-size: 19px;
            font-weight: 800;
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
            background: #fef3c7;
            color: #92400e;
        }

        .item {
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .item-info {
            color: #6b7280;
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
            font-size: 20px;
            font-weight: 800;
        }

        .view-btn {
            background: #111827;
            color: white;
            text-decoration: none;
            padding: 11px 18px;
            border-radius: 9px;
            font-weight: 700;
        }

        @media (max-width: 700px) {
            .navbar {
                padding: 16px 4%;
            }

            .nav-right {
                gap: 10px;
            }

            .container {
                width: 94%;
            }

            .order-header,
            .order-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">

    <a href="{{ route('home') }}" class="brand">
        BoomBuy
    </a>

    <div class="nav-right">

        <span class="seller-name">
            Seller: {{ $user['name'] ?? 'Seller' }}
        </span>

        <form action="{{ route('logout') }}" method="POST" class="logout">
            @csrf
            <button type="submit">Logout</button>
        </form>

    </div>

</nav>


<div class="container">

    <a href="{{ route('seller.dashboard') }}" class="back">
        ← Back to Seller Dashboard
    </a>

    <h1>Seller Orders</h1>

    <p class="subtitle">
        View and manage orders containing your products.
    </p>


    @if(session('success'))
        <div class="success">
            ✓ {{ session('success') }}
        </div>
    @endif


    @if(session('error'))
        <div class="error">
            {{ session('error') }}
        </div>
    @endif


    @if(empty($orders))

        <div class="empty">
            <h2>No Orders Yet</h2>

            <p style="margin-top: 10px; color: #6b7280;">
                Orders containing your products will appear here.
            </p>
        </div>

    @else

        @foreach($orders as $order)

            <div class="order-card">

                <div class="order-header">

                    <div>
                        <div class="order-id">
                            Order #{{ $order['id'] ?? 'N/A' }}
                        </div>

                        <div class="date">
                            {{ $order['date'] ?? '' }}
                        </div>
                    </div>

                    <div class="status">
                        {{ $order['status'] ?? 'Pending' }}
                    </div>

                </div>


                @foreach($order['items'] ?? [] as $item)

                    <div class="item">

                        <div class="item-name">
                            {{ $item['name'] ?? 'Product' }}
                        </div>

                        <div class="item-info">
                            Quantity:
                            {{ $item['quantity'] ?? 0 }}

                            • ₱{{ number_format((float)($item['price'] ?? 0), 2) }}
                            each
                        </div>

                        <div style="margin-top: 6px; font-weight: 700;">
                            Subtotal:
                            ₱{{ number_format((float)($item['subtotal'] ?? 0), 2) }}
                        </div>

                    </div>

                @endforeach


                <div class="order-footer">

                    <div>
                        <div style="font-size: 13px; color: #6b7280;">
                            Your Sales
                        </div>

                        <div class="total">
                            ₱{{ number_format((float)($order['seller_total'] ?? 0), 2) }}
                        </div>
                    </div>

                    <a
                        href="{{ route('seller.order.details', $order['id']) }}"
                        class="view-btn"
                    >
                        View Order
                    </a>

                </div>

            </div>

        @endforeach

    @endif

</div>


</body>
</html>
