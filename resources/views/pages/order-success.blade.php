<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Successful — BoomBuy</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8ff;
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

            border: 1px solid #e1e9f6;
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
            color: #3977d5;

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
            color: #718096;

            font-size: 14px;

            line-height: 1.6;

            margin-top: 10px;
        }

        .order-box {
            background: #f7faff;

            border: 1px solid #e1e9f6;

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

            border-bottom: 1px solid #e5ebf5;

            font-size: 13px;
        }

        .order-row:last-child {
            border-bottom: none;
        }

        .order-label {
            color: #718096;
        }

        .order-value {
            font-weight: 700;

            color: #172033;

            text-align: right;
        }

        .total {
            color: #1769e0;

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
            background: #1769e0;

            color: white;
        }

        .btn-primary:hover {
            background: #0f55bd;
        }

        .btn-secondary {
            background: #eef4ff;

            color: #1769e0;
        }

        .btn-secondary:hover {
            background: #e1ebff;
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