<!DOCTYPE html>

<html lang="en">

<head>


<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard — BoomBuy</title>

<style>

    @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        overflow-x: hidden;
    }

    body {
        font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
        background: #fff7f4;
        color: #172033;
        overflow-x: hidden;
    }

    a {
        text-decoration: none;
        color: inherit;
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
        width: 245px;
        background: #ffffff;
        border-right: 1px solid #f7e5e0;
        padding: 25px 18px;
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        z-index: 1000;
        overflow-y: auto;
    }

    .logo {
        padding: 0 12px;
        margin-bottom: 35px;
        font-size: 23px;
        font-weight: 700;
        color: #e8420f;
    }

    .logo span {
        color: #172033;
    }

    .admin-label {
        padding: 0 12px;
        color: #b99c93;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .menu {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .menu a {
        display: block;
        padding: 12px;
        border-radius: 9px;
        color: #8d6c62;
        font-size: 13px;
        font-weight: 600;
        transition: 0.2s;
    }

    .menu a:hover {
        background: #fff4f1;
        color: #e8420f;
    }

    .menu a.active {
        background: #ffefea;
        color: #e8420f;
    }

    .logout {
        margin-top: 35px;
    }

    .logout button:hover {
        background: #fff1f2 !important;
    }

    /* =========================
       MAIN
    ========================= */

    .main {
        margin-left: 245px;
        width: calc(100% - 245px);
        min-width: 0;
        padding: 35px 5%;
    }

    /* =========================
       TOPBAR
    ========================= */

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 35px;
    }

    .topbar small {
        color: #db5a33;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-size: 10px;
        font-weight: 700;
    }

    .topbar h1 {
        font-size: 32px;
        margin-top: 7px;
    }

    .admin-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #ffffff;
        border: 1px solid #f7e5e0;
        padding: 9px 13px;
        border-radius: 10px;
        flex-shrink: 0;
    }

    .profile-icon {
        width: 35px;
        height: 35px;
        background: #ffefea;
        color: #e8420f;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .profile-name {
        font-size: 12px;
        font-weight: 700;
    }

    .profile-role {
        color: #b99c93;
        font-size: 10px;
        margin-top: 2px;
    }

    /* =========================
       STATS
    ========================= */

    .stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 25px;
    }

    .stat-card {
        background: #ffffff;
        border: 1px solid #f7e5e0;
        border-radius: 15px;
        padding: 22px;
        transition: 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(39, 84, 150, 0.08);
    }

    .stat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .stat-title {
        color: #977970;
        font-size: 12px;
    }

    .stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: #fff1ed;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .stat-value {
        font-size: 27px;
        font-weight: 700;
    }

    .stat-change {
        color: #16a34a;
        font-size: 10px;
        margin-top: 7px;
    }

    /* =========================
       CONTENT GRID
    ========================= */

    .content-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .panel {
        background: #ffffff;
        border: 1px solid #f7e5e0;
        border-radius: 15px;
        padding: 23px;
        min-width: 0;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .panel-header h2 {
        font-size: 17px;
    }

    .view-all {
        color: #e8420f;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    /* =========================
       ORDERS
    ========================= */

    .order {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        padding: 13px 0;
        border-bottom: 1px solid #f7efed;
    }

    .order:last-child {
        border-bottom: none;
    }

    .order-info {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .order-icon {
        width: 38px;
        height: 38px;
        background: #fff4f1;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 17px;
    }

    .order-name {
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .order-id {
        color: #b99c93;
        font-size: 10px;
        margin-top: 4px;
    }

    .order-right {
        text-align: right;
        flex-shrink: 0;
    }

    .order-price {
        font-size: 12px;
        font-weight: 700;
    }

    .status {
        display: inline-block;
        margin-top: 4px;
        padding: 4px 7px;
        border-radius: 5px;
        font-size: 9px;
        font-weight: 700;
    }

    .completed {
        background: #ecfdf5;
        color: #16a34a;
    }

    .pending {
        background: #fffaed;
        color: #eaaf0c;
    }

    .processing {
        background: #fff3ef;
        color: #f34f1d;
    }

    .neutral-status {
        background: #f5f5f5;
        color: #777777;
    }

    .received-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-top: 5px;
        padding: 4px 7px;
        border-radius: 5px;
        background: #eafaf0;
        color: #24733e;
        font-size: 9px;
        font-weight: 700;
    }

    /* =========================
       PRODUCTS
    ========================= */

    .product-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 0;
        border-bottom: 1px solid #f7efed;
    }

    .product-row:last-child {
        border-bottom: none;
    }

    .product-icon {
        width: 42px;
        height: 42px;
        border-radius: 9px;
        background: #fff1ed;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .product-info {
        flex: 1;
        min-width: 0;
    }

    .product-name {
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .product-category {
        color: #b99c93;
        font-size: 10px;
        margin-top: 4px;
    }

    .product-price {
        color: #e8420f;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .empty-state {
        text-align: center;
        padding: 35px 15px;
        color: #b99c93;
        font-size: 12px;
    }

    .empty-state-icon {
        font-size: 30px;
        margin-bottom: 10px;
    }

    /* =========================
       ADMIN ACTION
    ========================= */

    .admin-action {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        background: #fff9f7;
        border: 1px solid #fbe9e4;
        border-radius: 12px;
        padding: 18px;
    }

    .admin-action-info {
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .admin-action-icon {
        width: 45px;
        height: 45px;
        border-radius: 11px;
        background: #ffefea;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 21px;
        flex-shrink: 0;
    }

    .admin-action-title {
        font-size: 12px;
        font-weight: 800;
    }

    .admin-action-description {
        color: #977970;
        font-size: 10px;
        margin-top: 4px;
    }

    .manage-button {
        background: #e8420f;
        color: white;
        padding: 10px 15px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .manage-button:hover {
        background: #c4360b;
    }

    /* =========================
       ACCOUNTS
    ========================= */

    .accounts-panel {
        margin-top: 20px;
    }

    .accounts-description {
        color: #977970;
        font-size: 12px;
        margin-top: 5px;
    }

    .account-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-top: 20px;
    }

    .account-stat {
        background: #fffaf8;
        border: 1px solid #f7e5e0;
        border-radius: 12px;
        padding: 18px;
    }

    .account-stat-title {
        color: #8d6c62;
        font-size: 11px;
        margin-bottom: 7px;
    }

    .account-stat-value {
        font-size: 25px;
    }

    /* =========================
       ACCOUNT TABLE
    ========================= */

    .account-table-wrapper {
        width: 100%;
        overflow-x: auto;
        margin-top: 25px;
    }

    .account-table {
        width: 100%;
        min-width: 600px;
        border-collapse: collapse;
    }

    .account-table th {
        text-align: left;
        padding: 12px;
        background: #fffaf8;
        color: #8d6c62;
        font-size: 11px;
    }

    .account-table td {
        padding: 13px 12px;
        border-bottom: 1px solid #f7efed;
        font-size: 13px;
    }

    .account-email {
        color: #8d6c62;
    }

    .role-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
    }

    .role-buyer {
        background: #ffede8;
        color: #e8420f;
    }

    .role-seller {
        background: #fffaed;
        color: #c2910c;
    }

    .role-rider {
        background: #f0fdf4;
        color: #15803d;
    }

    .no-accounts {
        text-align: center;
        padding: 30px;
        color: #b99c93;
        font-size: 12px;
    }

    /* =========================
       TABLET
    ========================= */

    @media (max-width: 1100px) {

        .stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .content-grid {
            grid-template-columns: 1fr;
        }

        .account-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    /* =========================
       MOBILE SIDEBAR
    ========================= */

    @media (max-width: 750px) {

        .sidebar {
            width: 70px;
            padding: 20px 10px;
            overflow-x: hidden;
        }

        .logo {
            font-size: 0;
            text-align: center;
            padding: 0;
        }

        .logo::before {
            content: "B";
            font-size: 23px;
            color: #e8420f;
        }

        .admin-label,
        .menu a span,
        .logout button span {
            display: none;
        }

        .menu a,
        .logout button {
            text-align: center !important;
            font-size: 18px !important;
        }

        .main {
            margin-left: 70px;
            width: calc(100% - 70px);
            padding: 25px 4%;
        }

        .topbar {
            align-items: flex-start;
        }

        .topbar h1 {
            font-size: 25px;
        }

        .profile-name,
        .profile-role {
            display: none;
        }

        .account-stats {
            grid-template-columns: 1fr 1fr;
        }

        .admin-action {
            align-items: flex-start;
            flex-direction: column;
        }

        .manage-button {
            width: 100%;
            text-align: center;
        }
    }

    /* =========================
       SMALL MOBILE
    ========================= */

    @media (max-width: 550px) {

        .stats {
            grid-template-columns: 1fr;
        }

        .account-stats {
            grid-template-columns: 1fr;
        }

        .admin-profile {
            padding: 8px;
        }

        .topbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .order {
            align-items: flex-start;
        }

        .order-right {
            text-align: right;
        }

        .panel {
            padding: 18px;
        }

        .panel-header {
            align-items: flex-start;
        }

        .admin-action {
            padding: 15px;
        }

        .admin-action-info {
            align-items: flex-start;
        }
    }

    /* =========================
       BOOMBUY DESIGN SYSTEM
    ========================= */

    h1,
    h2,
    h3,
    .logo,
    .stat-title,
    .stat-value {
        font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
        letter-spacing: -0.01em;
    }

    button {
        border-radius: 12px !important;
        transition: transform 0.15s ease,
                    box-shadow 0.15s ease,
                    background 0.15s ease;
    }

    button:hover {
        transform: translateY(-1px);
    }

    ::selection {
        background: #ffd7c2;
        color: #7c1a00;
    }

</style>


</head>

<body>

<div class="layout">


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">
        Boom<span>Buy</span>
    </div>

    <div class="admin-label">
        Administration
    </div>

    <nav class="menu">
    <a href="{{ route('admin.dashboard') }}" class="active">
        📊 <span>Dashboard</span>
    </a>

    <a href="{{ route('admin.accounts') }}">
        👥 <span>Accounts</span>
    </a>

    <a href="{{ route('admin.reports') }}">
        📈 <span>Reports</span>
    </a>

    <a href="{{ route('admin.settings') }}">
        ⚙️ <span>Settings</span>
    </a>

    </nav>


    <div class="logout">

        <form
            action="{{ route('admin.logout') }}"
            method="POST"
        >

            @csrf

            <button
                type="submit"
                style="
                    width: 100%;
                    border: none;
                    background: transparent;
                    text-align: left;
                    padding: 12px;
                    border-radius: 9px;
                    color: #ef4444;
                    font-size: 13px;
                    font-weight: 600;
                    cursor: pointer;
                    font-family: 'Plus Jakarta Sans', sans-serif;
                "
            >

                🚪 <span>Logout</span>

            </button>

        </form>

    </div>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <small>
                BoomBuy Administration
            </small>

            <h1>
                Dashboard
            </h1>

        </div>


        <div class="admin-profile">

            <div class="profile-icon">
                👤
            </div>

            <div>

                <div class="profile-name">
                    Administrator
                </div>

                <div class="profile-role">
                    Store Manager
                </div>

            </div>

        </div>

    </div>


    <!-- =========================
         DASHBOARD STATS
    ========================= -->

    <section class="stats">


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-title">
                    Total Products
                </div>

                <div class="stat-icon">
                    📦
                </div>

            </div>

            <div class="stat-value">
                {{ $totalProducts }}
            </div>

            <div class="stat-change">
                Actual BoomBuy products
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-title">
                    Total Orders
                </div>

                <div class="stat-icon">
                    🛒
                </div>

            </div>

            <div class="stat-value">
                {{ $totalOrders }}
            </div>

            <div class="stat-change">
                Actual orders
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-title">
                    Customers
                </div>

                <div class="stat-icon">
                    👥
                </div>

            </div>

            <div class="stat-value">
                {{ $buyerCount }}
            </div>

            <div class="stat-change">
                Registered buyers
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-title">
                    Total Sales
                </div>

                <div class="stat-icon">
                    💰
                </div>

            </div>

            <div class="stat-value">
                ₱{{ number_format($totalSales, 2) }}
            </div>

            <div class="stat-change">
                Delivered order sales
            </div>

        </div>

    </section>


    <!-- =========================
         MONITORING
    ========================= -->

    <div class="content-grid">


        <!-- =========================
             RECENT ORDERS
             MONITORING ONLY
        ========================= -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Recent Orders
                    </h2>

                </div>

                <span
                    style="
                        color:#977970;
                        font-size:10px;
                        font-weight:600;
                    "
                >
                    Monitoring only
                </span>

            </div>


            @if(count($orders) > 0)

                @foreach($orders as $order)

                    @php

                        $firstItem =
                            $order['items'][0] ?? null;

                        $productName =
                            $firstItem['name']
                            ?? 'Order #' . ($order['id'] ?? 'N/A');

                        $orderId =
                            $order['id'] ?? 'N/A';

                        $buyerName =
                            $order['buyer_name']
                            ?? 'Unknown Buyer';

                        $orderTotal =
                            (float) ($order['total'] ?? 0);

                        $orderStatus =
                            $order['status'] ?? 'Pending';

                        $statusClass = match ($orderStatus) {

                            'Delivered'
                                => 'completed',

                            'Pending',
                            'Ready for Pickup'
                                => 'pending',

                            'Processing',
                            'Picked Up',
                            'Out for Delivery',
                            'On the Way'
                                => 'processing',

                            default
                                => 'neutral-status',

                        };

                    @endphp


                    <div class="order">


                        <div class="order-info">

                            <div class="order-icon">
                                📦
                            </div>

                            <div>

                                <div class="order-name">

                                    {{ $productName }}

                                    @if(count($order['items'] ?? []) > 1)

                                        + {{ count($order['items']) - 1 }}
                                        more

                                    @endif

                                </div>

                                <div class="order-id">

                                    #{{ $orderId }}

                                    ·

                                    {{ $buyerName }}

                                </div>

                            </div>

                        </div>


                        <div class="order-right">

                            <div class="order-price">

                                ₱{{ number_format(
                                    $orderTotal,
                                    2
                                ) }}

                            </div>

                            <span
                                class="status {{ $statusClass }}"
                            >
                                {{ $orderStatus }}
                            </span>


                            @if(!empty($order['buyer_received_at']))

                                <div class="received-badge">

                                    ✓ Received

                                </div>

                            @endif

                        </div>

                    </div>

                @endforeach


            @else

                <div class="empty-state">

                    <div class="empty-state-icon">
                        🛒
                    </div>

                    No orders yet.

                </div>

            @endif

        </section>


        <!-- =========================
             SELLER PRODUCTS
             MONITORING ONLY
        ========================= -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Seller Products
                    </h2>

                </div>

                <span
                    style="
                        color:#977970;
                        font-size:10px;
                        font-weight:600;
                    "
                >
                    Monitoring only
                </span>

            </div>


            @if(count($sellerProductsForDashboard) > 0)

                @foreach($sellerProductsForDashboard as $product)

                    @php

                        $productName =
                            $product['name']
                            ?? $product['product_name']
                            ?? 'Unnamed Product';

                        $category =
                            $product['category']
                            ?? 'Product';

                        $price =
                            (float) (
                                $product['price']
                                ?? $product['selling_price']
                                ?? 0
                            );


                        $icon = match (
                            strtolower($category)
                        ) {

                            'electronics',
                            'gadgets',
                            'smartphone',
                            'phones'
                                => '📱',

                            'laptop',
                            'computers'
                                => '💻',

                            'audio',
                            'headphones'
                                => '🎧',

                            'wearable',
                            'watches'
                                => '⌚',

                            'accessories'
                                => '🎮',

                            'women',
                            "women's"
                                => '👗',

                            'men',
                            "men's"
                                => '👕',

                            'kids',
                            'baby',
                            'kids & baby'
                                => '🧸',

                            'home'
                                => '🏠',

                            'sports'
                                => '⚽',

                            'beauty'
                                => '💄',

                            'food'
                                => '🍔',

                            'automotive'
                                => '🚗',

                            'office',
                            'school',
                            'office & school'
                                => '📚',

                            default
                                => '📦',

                        };

                    @endphp


                    <div class="product-row">

                        <div class="product-icon">
                            {{ $icon }}
                        </div>


                        <div class="product-info">

                            <div class="product-name">
                                {{ $productName }}
                            </div>

                            <div class="product-category">
                                {{ $category }}
                            </div>

                        </div>


                        <div class="product-price">

                            ₱{{ number_format(
                                $price,
                                2
                            ) }}

                        </div>

                    </div>

                @endforeach


            @else

                <div class="empty-state">

                    <div class="empty-state-icon">
                        📦
                    </div>

                    No seller products yet.

                </div>

            @endif

        </section>

    </div>


    <!-- =========================
         ADMIN QUICK ACTION
         ACCOUNTS ONLY
    ========================= -->

    <section class="panel">

        <div class="panel-header">

            <h2>
                Admin Management
            </h2>

        </div>


        <div class="admin-action">

            <div class="admin-action-info">

                <div class="admin-action-icon">
                    👥
                </div>

                <div>

                    <div class="admin-action-title">
                        Manage Accounts
                    </div>

                    <div class="admin-action-description">
                        Manage registered Buyer, Seller and Rider accounts.
                    </div>

                </div>

            </div>


            <a
                href="{{ route('admin.accounts') }}"
                class="manage-button"
            >
                Manage Accounts →
            </a>

        </div>

    </section>


    <!-- =========================
         REGISTERED ACCOUNTS
    ========================= -->

    <section class="panel accounts-panel">

        <div class="panel-header">

            <div>

                <h2>
                    👥 Registered Accounts
                </h2>

                <p class="accounts-description">
                    Buyer, Seller and Rider accounts
                </p>

            </div>


            <a
                href="{{ route('admin.accounts') }}"
                class="manage-button"
            >
                Manage Accounts →
            </a>

        </div>


        <!-- ACCOUNT COUNTS -->

        <div class="account-stats">


            <div class="account-stat">

                <div class="account-stat-title">
                    Total Accounts
                </div>

                <strong class="account-stat-value">
                    {{ $totalUsers }}
                </strong>

            </div>


            <div class="account-stat">

                <div class="account-stat-title">
                    🛒 Buyers
                </div>

                <strong class="account-stat-value">
                    {{ $buyerCount }}
                </strong>

            </div>


            <div class="account-stat">

                <div class="account-stat-title">
                    🏪 Sellers
                </div>

                <strong class="account-stat-value">
                    {{ $sellerCount }}
                </strong>

            </div>


            <div class="account-stat">

                <div class="account-stat-title">
                    🛵 Riders
                </div>

                <strong class="account-stat-value">
                    {{ $riderCount }}
                </strong>

            </div>

        </div>


        <!-- RECENT ACCOUNTS -->

        @if(count($users) > 0)

            <div class="account-table-wrapper">

                <table class="account-table">

                    <thead>

                        <tr>

                            <th>
                                NAME
                            </th>

                            <th>
                                EMAIL
                            </th>

                            <th>
                                ROLE
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($users as $user)

                            <tr>

                                <td>
                                    {{ $user['name'] ?? 'N/A' }}
                                </td>

                                <td class="account-email">
                                    {{ $user['email'] ?? 'N/A' }}
                                </td>

                                <td>


                                    @if(($user['role'] ?? '') === 'buyer')

                                        <span
                                            class="role-badge role-buyer"
                                        >
                                            🛒 Buyer
                                        </span>


                                    @elseif(($user['role'] ?? '') === 'seller')

                                        <span
                                            class="role-badge role-seller"
                                        >
                                            🏪 Seller
                                        </span>


                                    @elseif(($user['role'] ?? '') === 'rider')

                                        <span
                                            class="role-badge role-rider"
                                        >
                                            🛵 Rider
                                        </span>


                                    @else

                                        <span class="role-badge">

                                            {{ ucfirst(
                                                $user['role']
                                                ?? 'Unknown'
                                            ) }}

                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


        @else

            <div class="no-accounts">

                No registered accounts yet.

            </div>

        @endif

    </section>


</main>


</div>

</body>

</html>
