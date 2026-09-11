<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Orders — BoomBuy</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>

        .received-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    margin-top: 8px;

    padding: 7px 11px;

    border-radius: 999px;

    background: #eafaf0;
    color: #24733e;

    border: 1px solid #ccefd9;

    font-size: 10px;
    font-weight: 800;

    white-space: nowrap;
}

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fff8f5;
            color: #2d2523;
        }

        /* =========================
           LAYOUT
        ========================= */

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 240px;
            background: #ffffff;
            border-right: 1px solid #f3e4df;

            padding: 28px 18px;

            display: flex;
            flex-direction: column;

            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;

            z-index: 1000;
        }

        .logo {
            text-decoration: none;
            font-family: 'Baloo 2', sans-serif;
            font-size: 30px;
            font-weight: 800;
            color: #25201f;

            padding: 0 10px;
            margin-bottom: 5px;
        }

        .logo span {
            color: #f45b2a;
        }

        .sidebar-label {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;

            color: #b9a39c;

            padding: 0 10px;
            margin-top: 25px;
            margin-bottom: 8px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .menu a {
            text-decoration: none;

            display: flex;
            align-items: center;
            gap: 12px;

            padding: 13px 14px;

            border-radius: 13px;

            color: #816f69;

            font-size: 13px;
            font-weight: 700;

            transition: 0.2s ease;
        }

        .menu a:hover {
            background: #fff4ef;
            color: #ef571f;
            transform: translateX(2px);
        }

        .menu a.active {
            background: #fff0e9;
            color: #ef571f;
        }

        .menu-icon {
            width: 24px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-footer {
            margin-top: auto;

            padding-top: 18px;

            border-top: 1px solid #f3e4df;
        }

        .seller-info {
            padding: 0 10px;
            margin-bottom: 12px;

            font-size: 12px;
            font-weight: 700;

            color: #816f69;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .logout {
            width: 100%;

            border: none;

            background: #fff0ed;
            color: #e4472a;

            padding: 12px;

            border-radius: 12px;

            font-family: inherit;
            font-size: 13px;
            font-weight: 800;

            cursor: pointer;

            transition: 0.2s ease;
        }

        .logout:hover {
            background: #ffe1d9;
            transform: translateY(-1px);
        }

        /* =========================
           MAIN
        ========================= */

        .main-content {
            margin-left: 240px;
            width: calc(100% - 240px);
            min-height: 100vh;
        }

        .container {
            width: min(1100px, 92%);
            margin: 0 auto;

            padding: 42px 0 60px;
        }

        /* =========================
           PAGE HEADER
        ========================= */

        .page-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;

            gap: 20px;

            margin-bottom: 30px;
        }

        .page-heading small {
            display: block;

            font-size: 12px;
            font-weight: 800;

            color: #f45b2a;

            text-transform: uppercase;
            letter-spacing: 0.08em;

            margin-bottom: 5px;
        }

        h1 {
            font-family: 'Baloo 2', sans-serif;

            font-size: 38px;
            line-height: 1;

            font-weight: 800;

            color: #2a2220;
        }

        .subtitle {
            margin-top: 8px;

            color: #927c75;

            font-size: 14px;
            line-height: 1.6;
        }

        .back-btn {
            text-decoration: none;

            display: inline-flex;
            align-items: center;
            gap: 7px;

            background: #ffffff;

            color: #e95220;

            border: 1px solid #f4ded6;

            padding: 11px 16px;

            border-radius: 12px;

            font-size: 12px;
            font-weight: 800;

            transition: 0.2s ease;
        }

        .back-btn:hover {
            background: #fff4ef;
            transform: translateY(-1px);
        }

        /* =========================
           ALERTS
        ========================= */

        .alert {
            padding: 14px 17px;

            border-radius: 13px;

            margin-bottom: 20px;

            font-size: 13px;
            font-weight: 700;
        }

        .success {
            background: #eafaf0;
            border: 1px solid #ccefd9;
            color: #24733e;
        }

        .error {
            background: #fff0f0;
            border: 1px solid #f4cece;
            color: #a43636;
        }

        /* =========================
           ORDER SUMMARY
        ========================= */

        .summary-grid {
            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 15px;

            margin-bottom: 25px;
        }

        .summary-card {
            background: #ffffff;

            border: 1px solid #f3e5e0;

            border-radius: 17px;

            padding: 19px 20px;

            box-shadow: 0 5px 20px rgba(104, 70, 60, 0.05);
        }

        .summary-label {
            font-size: 11px;
            font-weight: 800;

            color: #9c8982;

            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .summary-value {
            font-family: 'Baloo 2', sans-serif;

            font-size: 28px;
            font-weight: 800;

            color: #2d2523;

            margin-top: 3px;
        }

        .summary-card.orange .summary-value {
            color: #f05a28;
        }

        .summary-card.green .summary-value {
            color: #24965a;
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            background: #ffffff;

            border: 1px solid #f3e5e0;

            border-radius: 20px;

            padding: 65px 30px;

            text-align: center;

            box-shadow: 0 5px 25px rgba(104, 70, 60, 0.05);
        }

        .empty-icon {
            width: 65px;
            height: 65px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 15px;

            border-radius: 18px;

            background: #fff1eb;

            font-size: 27px;
        }

        .empty h2 {
            font-family: 'Baloo 2', sans-serif;

            font-size: 26px;
            font-weight: 800;

            color: #2d2523;
        }

        .empty p {
            margin-top: 7px;

            color: #927c75;

            font-size: 13px;
        }

        /* =========================
           ORDER CARD
        ========================= */

        .order-card {
            background: #ffffff;

            border: 1px solid #f2e4df;

            border-radius: 20px;

            margin-bottom: 18px;

            overflow: hidden;

            box-shadow: 0 6px 24px rgba(104, 70, 60, 0.055);

            transition: 0.2s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);

            box-shadow: 0 10px 28px rgba(104, 70, 60, 0.08);
        }

        /* =========================
           ORDER HEADER
        ========================= */

        .order-header {
            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 20px;

            padding: 20px 22px;

            background: #fffaf8;

            border-bottom: 1px solid #f4e7e2;
        }

        .order-id {
            font-family: 'Baloo 2', sans-serif;

            font-size: 21px;
            font-weight: 800;

            color: #2d2523;
        }

        .date {
            margin-top: 2px;

            color: #9a8780;

            font-size: 11px;
            font-weight: 600;
        }

        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-flex;
            align-items: center;

            gap: 7px;

            padding: 8px 13px;

            border-radius: 999px;

            background: #fff3cd;
            color: #9a7210;

            font-size: 11px;
            font-weight: 800;

            white-space: nowrap;
        }

        .status::before {
            content: "";

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: currentColor;
        }

        /* =========================
           CUSTOMER
        ========================= */

        .customer {
            margin: 18px 22px 0;

            padding: 14px 16px;

            background: #fff8f5;

            border: 1px solid #f4e6e1;

            border-radius: 13px;
        }

        .customer-title {
            font-size: 10px;
            font-weight: 800;

            color: #a18d86;

            text-transform: uppercase;
            letter-spacing: 0.08em;

            margin-bottom: 3px;
        }

        .customer-name {
            font-size: 14px;
            font-weight: 800;

            color: #3a2f2b;
        }

        .customer-phone {
            margin-top: 4px;

            color: #927e76;

            font-size: 12px;
        }

        /* =========================
           ITEMS
        ========================= */

        .items {
            padding: 8px 22px 0;
        }

        .item {
            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 20px;

            padding: 17px 0;

            border-bottom: 1px solid #f2ece9;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-size: 14px;
            font-weight: 800;

            color: #342a27;

            margin-bottom: 5px;
        }

        .item-info {
            color: #95827b;

            font-size: 11px;
            font-weight: 600;
        }

        .subtotal {
            font-size: 14px;
            font-weight: 800;

            color: #3c302c;

            white-space: nowrap;
        }

        /* =========================
           FOOTER
        ========================= */

        .order-footer {
            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 20px;

            margin-top: 5px;

            padding: 18px 22px 21px;

            border-top: 1px solid #f1e8e4;
        }

        .total-label {
            color: #a08b83;

            font-size: 10px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .total {
            margin-top: 2px;

            font-family: 'Baloo 2', sans-serif;

            color: #f05a28;

            font-size: 25px;
            font-weight: 800;
        }

        .view-btn {
            text-decoration: none;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 11px 18px;

            background: #f45b2a;
            color: #ffffff;

            border-radius: 12px;

            font-size: 12px;
            font-weight: 800;

            transition: 0.2s ease;

            box-shadow: 0 5px 12px rgba(244, 91, 42, 0.16);
        }

        .view-btn:hover {
            background: #e94e20;

            transform: translateY(-1px);

            box-shadow: 0 7px 15px rgba(244, 91, 42, 0.22);
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .sidebar {
                width: 75px;
                padding: 24px 8px;
            }

            .logo {
                font-size: 21px;
                text-align: center;
                padding: 0;
            }

            .logo span {
                display: none;
            }

            .sidebar-label,
            .label-text,
            .seller-info {
                display: none;
            }

            .menu a {
                justify-content: center;
                padding: 13px 8px;
            }

            .menu-icon {
                width: auto;
            }

            .main-content {
                margin-left: 75px;
                width: calc(100% - 75px);
            }

            .container {
                width: 92%;
            }
        }

        @media (max-width: 650px) {

            .page-top {
                flex-direction: column;
                align-items: flex-start;
            }

            h1 {
                font-size: 32px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .order-header,
            .order-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .item {
                align-items: flex-start;
                flex-direction: column;
                gap: 6px;
            }

            .subtotal {
                align-self: flex-end;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    {{-- =========================
         SIDEBAR
    ========================== --}}

    <aside class="sidebar">

        <a href="/" class="logo">
            Boom<span>Buy</span>
        </a>

        <div class="sidebar-label">
            Seller Panel
        </div>

        <nav class="menu">

            <a href="{{ route('seller.dashboard') }}">
                <span class="menu-icon">📊</span>
                <span class="label-text">Dashboard</span>
            </a>

            <a href="{{ route('seller.products.create') }}">
                <span class="menu-icon">➕</span>
                <span class="label-text">Add Product</span>
            </a>

            <a href="{{ route('seller.orders') }}" class="active">
                <span class="menu-icon">🛒</span>
                <span class="label-text">Orders</span>
            </a>

        </nav>

        <div class="sidebar-footer">

            <div class="seller-info">
                Seller: {{ $user['name'] ?? 'Seller' }}
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf

                <button type="submit" class="logout">
                    Logout
                </button>
            </form>

        </div>

    </aside>


    {{-- =========================
         MAIN CONTENT
    ========================== --}}

    <main class="main-content">

        <div class="container">

            {{-- PAGE HEADER --}}

            <div class="page-top">

                <div class="page-heading">

                    <small>Seller Panel</small>

                    <h1>Orders</h1>

                    <p class="subtitle">
                        View and manage orders containing your products.
                    </p>

                </div>

                <a
                    href="{{ route('seller.dashboard') }}"
                    class="back-btn"
                >
                    ← Back to Dashboard
                </a>

            </div>


            {{-- ALERTS --}}

            @if(session('success'))

                <div class="alert success">
                    ✓ {{ session('success') }}
                </div>

            @endif


            @if(session('error'))

                <div class="alert error">
                    {{ session('error') }}
                </div>

            @endif


            {{-- SUMMARY --}}

            @if(!$orders->isEmpty())

                @php
                    $totalOrders = $orders->count();

                    $pendingOrders = $orders->filter(function ($order) {
                        return in_array(
                            strtolower($order->status),
                            ['pending', 'processing']
                        );
                    })->count();

                    $totalSales = $orders->sum(function ($order) {
                        return (float) $order->seller_total;
                    });
                @endphp

                <div class="summary-grid">

                    <div class="summary-card">
                        <div class="summary-label">
                            Total Orders
                        </div>

                        <div class="summary-value">
                            {{ $totalOrders }}
                        </div>
                    </div>

                    <div class="summary-card orange">
                        <div class="summary-label">
                            Pending Orders
                        </div>

                        <div class="summary-value">
                            {{ $pendingOrders }}
                        </div>
                    </div>

                    <div class="summary-card green">
                        <div class="summary-label">
                            Your Sales
                        </div>

                        <div class="summary-value">
                            ₱{{ number_format($totalSales, 2) }}
                        </div>
                    </div>

                </div>

            @endif


            {{-- =========================
                 NO ORDERS
            ========================== --}}

            @if($orders->isEmpty())

                <div class="empty">

                    <div class="empty-icon">
                        🛒
                    </div>

                    <h2>No Orders Yet</h2>

                    <p>
                        Orders containing your products will appear here.
                    </p>

                </div>

            @else


                {{-- =========================
                     ORDER LIST
                ========================== --}}

                @foreach($orders as $order)

                    <div class="order-card">

                        {{-- ORDER HEADER --}}

                        <div class="order-header">

                            <div>

                                <div class="order-id">
                                    Order #{{ $order->id }}
                                </div>

                                <div class="date">
                                    {{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y • h:i A') }}
                                </div>

                            </div>

              <div>

    <div class="status">
        {{ ucwords(str_replace('_', ' ', $order->status)) }}
    </div>

    @if(!empty($order->buyer_received_at))

        <div class="received-badge">
            ✓ Received by Buyer
        </div>

    @endif

</div>

                        </div>


                        {{-- CUSTOMER --}}

                        @if($order->shipping_name)

                            <div class="customer">

                                <div class="customer-title">
                                    Customer
                                </div>

                                <div class="customer-name">
                                    {{ $order->shipping_name }}
                                </div>

                                @if($order->shipping_phone)

                                    <div class="customer-phone">
                                        📞 {{ $order->shipping_phone }}
                                    </div>

                                @endif

                            </div>

                        @endif


                        {{-- PRODUCTS --}}

                        <div class="items">

                            @foreach($order->items as $item)

                                @php
                                    $subtotal =
                                        (float) $item->price *
                                        (int) $item->quantity;
                                @endphp

                                <div class="item">

                                    <div>

                                        <div class="item-name">
                                            {{ $item->product_name }}
                                        </div>

                                        <div class="item-info">

                                            Quantity:
                                            {{ $item->quantity }}

                                            • ₱{{ number_format((float) $item->price, 2) }}
                                            each

                                        </div>

                                    </div>

                                    <div class="subtotal">

                                        ₱{{ number_format($subtotal, 2) }}

                                    </div>

                                </div>

                            @endforeach

                        </div>


                        {{-- ORDER FOOTER --}}

                        <div class="order-footer">

                            <div>

                                <div class="total-label">
                                    Your Sales
                                </div>

                                <div class="total">
                                    ₱{{ number_format((float) $order->seller_total, 2) }}
                                </div>

                            </div>

                           @if(empty($order->buyer_received_at))

    <a
        href="{{ route('seller.order.details', ['id' => $order->id]) }}"
        class="view-btn"
    >
        View Order →
    </a>

@endif

                        </div>

                    </div>

                @endforeach

            @endif

        </div>

    </main>

</div>

</body>
</html>