<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Orders — BoomBuy</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @include('partials.pwa-head')

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

        .summary-card.red .summary-value {
            color: #d6362b;
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
           SECTION HEADING (used for Returns block)
        ========================= */

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            margin: 40px 0 16px;
        }

        .section-heading h2 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #2d2523;
        }

        .section-heading .count-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            padding: 6px 12px;

            border-radius: 999px;

            background: #fff0e9;
            color: #e4491f;

            font-size: 11px;
            font-weight: 800;
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

        /* status color variants — used by both orders and return requests */
        .status.status-approved {
            background: #eafaf0;
            color: #24733e;
        }

        .status.status-rejected {
            background: #fdeceb;
            color: #c23b2c;
        }

        .status.status-pending {
            background: #fff3cd;
            color: #9a7210;
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
           RETURN / REFUND REQUESTS
        ========================= */

        .return-card {
            background: #ffffff;

            border: 1px solid #f2e4df;

            border-radius: 20px;

            margin-bottom: 18px;

            overflow: hidden;

            box-shadow: 0 6px 24px rgba(104, 70, 60, 0.055);

            transition: 0.2s ease;
        }

        .return-card:hover {
            transform: translateY(-2px);

            box-shadow: 0 10px 28px rgba(104, 70, 60, 0.08);
        }

        .return-header {
            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 20px;

            padding: 20px 22px;

            background: #fff8f5;

            border-bottom: 1px solid #f4e7e2;
        }

        .return-id {
            font-family: 'Baloo 2', sans-serif;

            font-size: 19px;
            font-weight: 800;

            color: #2d2523;
        }

        .return-id .order-ref {
            font-size: 11px;
            font-weight: 700;
            color: #a08b83;

            margin-left: 6px;
        }

        .return-body {
            padding: 18px 22px 4px;
        }

        .return-block {
            margin-bottom: 16px;
        }

        .return-block-title {
            font-size: 10px;
            font-weight: 800;

            color: #a18d86;

            text-transform: uppercase;
            letter-spacing: 0.08em;

            margin-bottom: 5px;
        }

        .return-block-text {
            font-size: 13px;
            font-weight: 600;
            color: #3a2f2b;
            line-height: 1.6;

            background: #fff8f5;
            border: 1px solid #f4e6e1;
            border-radius: 12px;
            padding: 12px 14px;
        }

        .seller-note-box {
            background: #fff1eb;
            border: 1px solid #f7d9cb;
        }

        .return-footer {
            display: flex;

            justify-content: flex-end;
            align-items: center;

            gap: 10px;

            padding: 16px 22px 22px;

            border-top: 1px solid #f1e8e4;
            margin-top: 8px;
        }

        .btn {
            border: none;
            cursor: pointer;
            font-family: inherit;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;

            padding: 11px 18px;

            border-radius: 12px;

            font-size: 12px;
            font-weight: 800;

            transition: 0.2s ease;
        }

        .btn-approve {
            background: #24965a;
            color: #ffffff;
            box-shadow: 0 5px 12px rgba(36, 150, 90, 0.18);
        }

        .btn-approve:hover {
            background: #1e8049;
            transform: translateY(-1px);
        }

        .btn-reject {
            background: #ffffff;
            color: #d6362b;
            border: 1px solid #f4c9c3;
        }

        .btn-reject:hover {
            background: #fff0ee;
            transform: translateY(-1px);
        }

        /* =========================
           REJECT MODAL
        ========================= */

        .modal-overlay {
            display: none;

            position: fixed;
            inset: 0;

            background: rgba(45, 37, 35, 0.45);

            z-index: 2000;

            align-items: center;
            justify-content: center;

            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: #ffffff;

            border-radius: 20px;

            width: min(440px, 100%);

            padding: 26px 26px 22px;

            box-shadow: 0 20px 50px rgba(45, 37, 35, 0.25);
        }

        .modal-box h3 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 21px;
            font-weight: 800;
            color: #2d2523;

            margin-bottom: 6px;
        }

        .modal-box p {
            font-size: 12px;
            color: #927c75;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .modal-box label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: #a18d86;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }

        .modal-box textarea {
            width: 100%;

            min-height: 100px;

            resize: vertical;

            border: 1px solid #f0ddd5;
            border-radius: 12px;

            padding: 12px 14px;

            font-family: inherit;
            font-size: 13px;
            color: #2d2523;

            background: #fff8f5;
        }

        .modal-box textarea:focus {
            outline: none;
            border-color: #f45b2a;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;

            margin-top: 18px;
        }

        .btn-cancel {
            background: #fff0ed;
            color: #816f69;
        }

        .btn-cancel:hover {
            background: #f4e6e1;
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
            .order-footer,
            .return-header {
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

            .return-footer {
                justify-content: flex-start;
                flex-wrap: wrap;
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


            {{-- =========================
                 RETURN / REFUND REQUESTS
            ========================== --}}

            @if(isset($returnRequests) && !$returnRequests->isEmpty())

                @php
                    $pendingReturns = $returnRequests->filter(function ($r) {
                        return strtolower($r->status) === 'pending';
                    })->count();
                @endphp

                <div class="section-heading">

                    <h2>Return / Refund Requests</h2>

                    @if($pendingReturns > 0)
                        <span class="count-pill">
                            {{ $pendingReturns }} pending
                        </span>
                    @endif

                </div>

                @foreach($returnRequests as $request)

                    @php
                        $statusLower = strtolower($request->status);
                        $statusClass = 'status-pending';

                        if ($statusLower === 'approved') {
                            $statusClass = 'status-approved';
                        } elseif ($statusLower === 'rejected') {
                            $statusClass = 'status-rejected';
                        }
                    @endphp

                    <div class="return-card">

                        <div class="return-header">

                            <div>

                                <div class="return-id">
                                    Return #{{ $request->id }}
                                    <span class="order-ref">
                                        — Order #{{ $request->order_id }}
                                    </span>
                                </div>

                                <div class="date">
                                    Requested {{ \Carbon\Carbon::parse($request->created_at)->format('M d, Y • h:i A') }}
                                </div>

                            </div>

                            <div class="status {{ $statusClass }}">
                                {{ ucwords($request->status) }}
                            </div>

                        </div>

                        @if($request->order && $request->order->shipping_name)

                            <div class="customer" style="margin-top:18px;">

                                <div class="customer-title">
                                    Customer
                                </div>

                                <div class="customer-name">
                                    {{ $request->order->shipping_name }}
                                </div>

                                @if($request->order->shipping_phone)

                                    <div class="customer-phone">
                                        📞 {{ $request->order->shipping_phone }}
                                    </div>

                                @endif

                            </div>

                        @endif

                        <div class="return-body">

                            <div class="return-block">

                                <div class="return-block-title">
                                    Reason for Return
                                </div>

                                <div class="return-block-text">
                                    {{ $request->reason ?? 'No reason provided.' }}
                                </div>

                            </div>

                            @if(!empty($request->buyer_note))

                                <div class="return-block">

                                    <div class="return-block-title">
                                        Buyer Note
                                    </div>

                                    <div class="return-block-text">
                                        {{ $request->buyer_note }}
                                    </div>

                                </div>

                            @endif

                            @if(!empty($request->seller_note))

                                <div class="return-block">

                                    <div class="return-block-title">
                                        Your Note (Seller)
                                    </div>

                                    <div class="return-block-text seller-note-box">
                                        {{ $request->seller_note }}
                                    </div>

                                </div>

                            @endif

                        </div>

                        @if($statusLower === 'pending')

                            <div class="return-footer">

                                {{-- APPROVE --}}
                                <form
                                    action="{{ route('seller.returns.approve', ['id' => $request->id]) }}"
                                    method="POST"
                                >
                                    @csrf

                                    <button type="submit" class="btn btn-approve">
                                        ✓ Approve
                                    </button>
                                </form>

                                {{-- REJECT (opens modal) --}}
                                <button
                                    type="button"
                                    class="btn btn-reject"
                                    onclick="openRejectModal({{ $request->id }})"
                                >
                                    ✕ Reject
                                </button>

                            </div>

                        @endif

                    </div>

                @endforeach

            @endif

        </div>

    </main>

</div>


{{-- =========================
     REJECT MODAL (shared)
========================== --}}

<div class="modal-overlay" id="rejectModalOverlay">

    <div class="modal-box">

        <h3>Reject Return Request</h3>

        <p>
            Please add a note explaining why this return/refund request
            is being rejected. The buyer will see this note.
        </p>

        <form
            id="rejectForm"
            method="POST"
            action=""
        >
            @csrf

            <label for="seller_note">Seller Note</label>

            <textarea
                name="seller_note"
                id="seller_note"
                placeholder="e.g. Item does not meet return policy conditions..."
                required
            ></textarea>

            <div class="modal-actions">

                <button
                    type="button"
                    class="btn btn-cancel"
                    onclick="closeRejectModal()"
                >
                    Cancel
                </button>

                <button type="submit" class="btn btn-reject" style="background:#d6362b; color:#fff; border:none;">
                    Confirm Reject
                </button>

            </div>

        </form>

    </div>

</div>


<script>
    function openRejectModal(requestId) {
        const overlay = document.getElementById('rejectModalOverlay');
        const form = document.getElementById('rejectForm');

        // Build the reject route dynamically using the base URL pattern.
        form.action = "{{ url('seller/returns') }}/" + requestId + "/reject";

        document.getElementById('seller_note').value = '';
        overlay.classList.add('active');
    }

    function closeRejectModal() {
        document.getElementById('rejectModalOverlay').classList.remove('active');
    }

    // Close modal when clicking outside the box
    document.getElementById('rejectModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) {
            closeRejectModal();
        }
    });
</script>

    @include('partials.pwa-register')

</body>
</html>