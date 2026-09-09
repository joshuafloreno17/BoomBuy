<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Successful — BoomBuy</title>

    <style>
@import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: #fff7f4;
            color: #172033;

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px;
        }

        .success-card {
            width: 100%;
            max-width: 650px;

            background: white;

            border: 1px solid #f7e5e0;
            border-radius: 18px;

            padding: 45px;

            text-align: center;

            box-shadow: 0 15px 40px rgba(23, 105, 224, 0.08);
        }

        .success-icon {
            width: 80px;
            height: 80px;

            margin: 0 auto 20px;

            border-radius: 50%;

            background: #e9f8ef;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 40px;
        }

        .success-card small {
            color: #db5a33;

            text-transform: uppercase;

            letter-spacing: 2px;

            font-size: 11px;

            font-weight: 700;
        }

        .success-card h1 {
            font-size: 30px;

            margin-top: 8px;

            color: #172033;
        }

        .success-card p {
            color: #977970;

            font-size: 14px;

            line-height: 1.6;

            margin-top: 10px;
        }

        .order-box {
            background: #fff9f7;

            border: 1px solid #f7e5e0;

            border-radius: 12px;

            padding: 20px;

            margin-top: 25px;

            text-align: left;
        }

        .order-row {
            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 10px 0;

            border-bottom: 1px solid #f6e8e4;

            font-size: 13px;
        }

        .order-row:last-child {
            border-bottom: none;
        }

        .order-label {
            color: #977970;
        }

        .order-value {
            font-weight: 700;

            color: #172033;

            text-align: right;
        }

        .total {
            color: #e8420f;

            font-size: 18px;
        }

        .buttons {
            display: flex;

            gap: 12px;

            margin-top: 30px;
        }

        .btn {
            flex: 1;

            padding: 12px 18px;

            border-radius: 8px;

            font-size: 13px;

            font-weight: 700;

            text-align: center;

            transition: 0.2s;
        }

        .btn-primary {
            background: #e8420f;

            color: white;
        }

        .btn-primary:hover {
            background: #c43408;
        }

        .btn-secondary {
            background: #fff2ee;

            color: #e8420f;
        }

        .btn-secondary:hover {
            background: #ffe8e1;
        }

        @media (max-width: 600px) {

            body {
                padding: 15px;
            }

            .success-card {
                padding: 30px 20px;
            }

            .success-card h1 {
                font-size: 25px;
            }

            .buttons {
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

<div class="success-card">

    <div class="success-icon">
        ✅
    </div>

    <small>
        BoomBuy Order
    </small>

    <h1>
        Order Placed Successfully!
    </h1>

    <p>
        Thank you for shopping with BoomBuy.
        Your order has been received and is now being processed.
    </p>


    <div class="order-box">

        <div class="order-row">

            <span class="order-label">
                Order Number
            </span>

            <span class="order-value">
                {{ $order['id'] ?? 'N/A' }}
            </span>

        </div>


        <div class="order-row">

            <span class="order-label">
                Customer
            </span>

            <span class="order-value">
                {{ $order['buyer_name'] ?? 'Buyer' }}
            </span>

        </div>


        <div class="order-row">

            <span class="order-label">
                Payment Method
            </span>

            <span class="order-value">
                {{ $order['payment'] ?? 'N/A' }}
            </span>

        </div>


        <div class="order-row">

            <span class="order-label">
                Status
            </span>

            <span class="order-value">
                {{ $order['status'] ?? 'Pending' }}
            </span>

        </div>


        <div class="order-row">

            <span class="order-label">
                Order Total
            </span>

            <span class="order-value total">
                ₱{{ number_format($order['total'] ?? 0, 2) }}
            </span>

        </div>

    </div>


    <div class="buttons">

        <a
            href="{{ route('buyer.orders') }}"
            class="btn btn-primary"
        >
            📦 View My Orders
        </a>


        <a
            href="{{ route('products') }}"
            class="btn btn-secondary"
        >
            🛍️ Continue Shopping
        </a>

    </div>

</div>

</body>

</html>