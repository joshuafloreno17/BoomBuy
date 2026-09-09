<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Order Details — BoomBuy</title>

    <style>
@import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fbf6f5;
            color: #1f2937;
        }

        .navbar {
            background: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ebe6e5;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #f34f1d;
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
            color: #f34f1d;
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
            color: #816f6a;
            margin-bottom: 20px;
        }

        .status {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 20px;
            background: #fffaed;
            color: #c2910c;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 0;
            border-bottom: 1px solid #ebe6e5;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 700;
            font-size: 17px;
        }

        .item-info {
            color: #816f6a;
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
            color: #f34f1d;
            border-top: 1px solid #ebe6e5;
            margin-top: 10px;
            padding-top: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-box {
            background: #fcf9f8;
            padding: 18px;
            border-radius: 12px;
        }

        .label {
            color: #816f6a;
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
            border: 1px solid #dbd3d1;
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
            background: #f34f1d;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #df4516;
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
            color: #816f6a;
        }

        @media (max-width: 700px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .item {
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
