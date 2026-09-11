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

        :root {
            --orange: #f34f1d;
            --orange-dark: #df4516;
            --orange-light: #fff1eb;
            --cream: #fbf6f5;
            --text: #29211f;
            --muted: #816f6a;
            --border: #ebe6e5;
            --sidebar-width: 245px;
        }

        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            background: var(--cream);
            color: var(--text);
            min-height: 100vh;
        }

        /* =========================================
           MAIN LAYOUT
        ========================================= */

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* =========================================
           SIDEBAR
        ========================================= */

        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            height: 100vh;
            position: sticky;
            top: 0;

            background: #ffffff;
            border-right: 1px solid var(--border);

            padding: 28px 18px;

            display: flex;
            flex-direction: column;
        }

        .sidebar .logo {
            display: block;
            padding: 0 14px 28px;

            text-decoration: none;

            font-family: 'Baloo 2', sans-serif;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -1px;

            color: var(--orange);
        }

        .sidebar .logo span {
            color: #ff8a5c;
        }

        .sidebar-label {
            padding: 0 14px;
            margin-bottom: 14px;

            color: #a38c85;

            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 13px 14px;

            border-radius: 12px;

            color: #62524d;
            text-decoration: none;

            font-size: 14px;
            font-weight: 700;

            transition: all .2s ease;
        }

        .menu a:hover {
            background: var(--orange-light);
            color: var(--orange);
            transform: translateX(2px);
        }

        .menu a.active {
            background: var(--orange);
            color: #ffffff;

            box-shadow: 0 7px 18px rgba(243, 79, 29, .18);
        }

        .menu a.active:hover {
            background: var(--orange-dark);
            color: #ffffff;
        }

        .sidebar-footer {
            margin-top: auto;

            padding: 15px 10px 5px;

            border-top: 1px solid var(--border);
        }

        .sidebar-user {
            color: #8d6c62;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.5;
        }

        /* =========================================
           MAIN CONTENT
        ========================================= */

        .main-content {
            flex: 1;
            min-width: 0;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
        }

        /* =========================================
           HEADINGS
        ========================================= */

        h1,
        h2,
        h3,
        .logo,
        .status,
        .price,
        .total {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }

        h1 {
            font-size: 32px;
            line-height: 1.15;
            margin-bottom: 8px;
        }

        h2 {
            font-size: 23px;
            line-height: 1.2;
            margin-bottom: 8px;
        }

        /* =========================================
           BACK BUTTON
        ========================================= */

        .back {
            display: inline-flex;
            align-items: center;

            margin-bottom: 25px;

            color: var(--orange);
            text-decoration: none;

            font-size: 14px;
            font-weight: 800;

            transition: color .2s ease;
        }

        .back:hover {
            color: var(--orange-dark);
        }

        /* =========================================
           CARDS
        ========================================= */

        .card {
            background: #ffffff;

            border: 1px solid rgba(235, 230, 229, .8);
            border-radius: 18px;

            padding: 30px;

            box-shadow: 0 8px 25px rgba(31, 41, 55, .06);

            margin-bottom: 20px;
        }

        .order-id {
            color: var(--muted);

            margin-bottom: 18px;

            font-size: 14px;
            font-weight: 600;
        }

        /* =========================================
           STATUS
        ========================================= */

        .status {
            display: inline-block;

            padding: 8px 15px;

            border-radius: 20px;

            background: #fffaed;
            color: #c2910c;

            font-size: 13px;
            font-weight: 800;

            margin-bottom: 25px;
        }

        /* =========================================
           INFO GRID
        ========================================= */

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-box {
            background: #fcf9f8;

            padding: 18px;

            border-radius: 13px;

            border: 1px solid #f0e9e7;
        }

        .label {
            color: var(--muted);

            font-size: 13px;
            font-weight: 600;

            margin-bottom: 6px;
        }

        .value {
            color: var(--text);

            font-size: 14px;
            font-weight: 800;

            line-height: 1.5;
            word-break: break-word;
        }

        /* =========================================
           ORDER ITEMS
        ========================================= */

        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 20px;

            padding: 18px 0;

            border-bottom: 1px solid var(--border);
        }

        .item:last-of-type {
            border-bottom: none;
        }

        .item-name {
            font-size: 17px;
            font-weight: 800;
        }

        .item-info {
            color: var(--muted);

            margin-top: 5px;

            font-size: 14px;
            font-weight: 500;
        }

        .price {
            color: var(--text);

            font-size: 18px;
            font-weight: 800;

            white-space: nowrap;
        }

        /* =========================================
           SUMMARY
        ========================================= */

        .summary {
            display: flex;
            justify-content: space-between;
            align-items: center;

            padding: 12px 0;
        }

        .total {
            color: var(--orange);

            font-size: 22px;
            font-weight: 800;

            border-top: 1px solid var(--border);

            margin-top: 10px;
            padding-top: 18px;
        }

        /* =========================================
           DELIVERY
        ========================================= */

        .delivery-box {
            margin-top: 20px;
        }

        /* =========================================
           FORM
        ========================================= */

        form {
            margin-top: 20px;
        }

        select {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #dbd3d1;
            border-radius: 10px;

            margin-bottom: 12px;

            background: #ffffff;
            color: var(--text);

            font-family: inherit;
            font-size: 15px;

            outline: none;
        }

        select:focus {
            border-color: var(--orange);

            box-shadow: 0 0 0 3px rgba(243, 79, 29, .1);
        }

        button {
            width: 100%;

            padding: 13px;

            border: none;
            border-radius: 12px;

            background: var(--orange);
            color: #ffffff;

            font-family: inherit;
            font-size: 14px;
            font-weight: 800;

            cursor: pointer;

            transition:
                transform .15s ease,
                box-shadow .15s ease,
                background .15s ease;
        }

        button:hover {
            background: var(--orange-dark);

            transform: translateY(-1px);

            box-shadow: 0 7px 18px rgba(243, 79, 29, .18);
        }

        /* =========================================
           ALERTS
        ========================================= */

        .success {
            background: #ecfdf5;
            color: #047857;

            padding: 14px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;
            font-weight: 600;
        }

        .error {
            background: #fef2f2;
            color: #b91c1c;

            padding: 14px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;
            font-weight: 600;
        }

        /* =========================================
           FOOTER
        ========================================= */

        footer {
            text-align: center;

            padding: 30px;

            color: var(--muted);

            font-size: 13px;
        }

        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 850px) {

            :root {
                --sidebar-width: 210px;
            }

            .container {
                width: 92%;
            }
        }

        @media (max-width: 700px) {

            .layout {
                display: block;
            }

            .sidebar {
                width: 100%;
                min-width: 0;

                height: auto;
                min-height: auto;

                position: relative;

                padding: 18px 15px;

                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .sidebar .logo {
                padding: 0 8px 15px;

                font-size: 27px;
            }

            .sidebar-label {
                padding: 0 8px;
            }

            .menu {
                flex-direction: row;

                overflow-x: auto;

                padding-bottom: 3px;
            }

            .menu a {
                white-space: nowrap;
            }

            .sidebar-footer {
                display: none;
            }

            .container {
                width: 92%;

                margin: 25px auto;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .item {
                align-items: flex-start;

                flex-direction: column;
            }

            .price {
                align-self: flex-end;
            }

            h1 {
                font-size: 27px;
            }

            h2 {
                font-size: 21px;
            }

            .card {
                padding: 22px;
            }
        }

        /* =========================================
           BOOMBUY VIBRANT DESIGN
        ========================================= */

        ::selection {
            background: #ffd7c2;
            color: #7c1a00;
        }
    </style>
</head>

<body>

<div class="layout">

    <!-- =========================================
         SIDEBAR
    ========================================== -->

    <aside class="sidebar">

        <a href="/" class="logo">
            Boom<span>Buy</span>
        </a>

        <div class="sidebar-label">
            Seller Panel
        </div>

        <nav class="menu">

            <a href="{{ route('seller.dashboard') }}">
                📊
                <span>Dashboard</span>
            </a>

            <a href="{{ route('seller.products.create') }}">
                ➕
                <span>Add Product</span>
            </a>

            <a href="{{ route('seller.orders') }}" class="active">
                🛒
                <span>Orders</span>
            </a>

        </nav>

        <div class="sidebar-footer">

            <div class="sidebar-user">
                Seller: {{ $user['name'] ?? 'Seller' }}
            </div>

        </div>

    </aside>


    <!-- =========================================
         MAIN CONTENT
    ========================================== -->

    <main class="main-content">

        <div class="container">

            <!-- BACK -->

            <a href="{{ route('seller.orders') }}" class="back">
                ← Back to Seller Orders
            </a>


            <!-- ALERTS -->

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


            <!-- =========================================
                 ORDER INFORMATION
            ========================================== -->

            <div class="card">

                <h1>
                    Seller Order Details
                </h1>

                <div class="order-id">
                    Order #{{ $order['id'] ?? 'N/A' }}
                </div>

                <div class="status">
                    {{ $order['status'] ?? 'Pending' }}
                </div>


                <div class="info-grid">

                    <!-- BUYER -->

                    <div class="info-box">

                        <div class="label">
                            Buyer
                        </div>

                        <div class="value">
                            {{ $order['buyer_name'] ?? 'Buyer' }}
                        </div>

                    </div>


                    <!-- ORDER DATE -->

                    <div class="info-box">

                        <div class="label">
                            Order Date
                        </div>

                        <div class="value">
                            {{ $order['date'] ?? 'N/A' }}
                        </div>

                    </div>


                    <!-- PHONE -->

                    <div class="info-box">

                        <div class="label">
                            Phone
                        </div>

                        <div class="value">
                            {{ $order['phone'] ?? 'N/A' }}
                        </div>

                    </div>


                    <!-- PAYMENT -->

                    <div class="info-box">

                        <div class="label">
                            Payment Method
                        </div>

                        <div class="value">
                            {{ $order['payment'] ?? 'N/A' }}
                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================================
                 PRODUCTS
            ========================================== -->

            <div class="card">

                <h2>
                    Your Products in This Order
                </h2>

                @forelse(($order['items'] ?? []) as $item)

                    <div class="item">

                        <div>

                            <div class="item-name">
                                {{ $item['name'] ?? 'Product' }}
                            </div>

                            <div class="item-info">

                                Quantity:
                                {{ $item['quantity'] ?? 0 }}

                                • ₱{{ number_format((float)($item['price'] ?? 0), 2) }}

                                each

                            </div>

                        </div>

                        <div class="price">

                            ₱{{ number_format((float)($item['subtotal'] ?? 0), 2) }}

                        </div>

                    </div>

                @empty

                    <div class="info-box">
                        <div class="value">
                            No products found in this order.
                        </div>
                    </div>

                @endforelse


                <!-- SELLER TOTAL -->

                <div class="summary total">

                    <span>
                        Your Sales
                    </span>

                    <span>
                        ₱{{ number_format((float)($order['seller_total'] ?? 0), 2) }}
                    </span>

                </div>

            </div>


            <!-- =========================================
                 DELIVERY INFORMATION
            ========================================== -->

            <div class="card">

                <h2>
                    Delivery Information
                </h2>

                <div class="info-box delivery-box">

                    <div class="label">
                        Delivery Address
                    </div>

                    <div class="value">
                        {{ $order['address'] ?? 'N/A' }}
                    </div>

                </div>

            </div>


            <!-- =========================================
                 UPDATE STATUS
            ========================================== -->

            <div class="card">

                <h2>
                    Update Order Status
                </h2>

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


        <!-- FOOTER -->

        <footer>

            © 2026 <strong>BoomBuy</strong>
            <br>
            Seller Order Management

        </footer>

    </main>

</div>

</body>
</html>