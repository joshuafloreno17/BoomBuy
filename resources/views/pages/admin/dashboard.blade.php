<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard — BoomBuy</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            overflow-x: hidden;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8ff;
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
            border-right: 1px solid #e1e9f6;
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
            color: #1769e0;
        }

        .logo span {
            color: #172033;
        }

        .admin-label {
            padding: 0 12px;

            color: #94a3b8;
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

            color: #64748b;
            font-size: 13px;
            font-weight: 600;

            transition: 0.2s;
        }

        .menu a:hover {
            background: #f1f6ff;
            color: #1769e0;
        }

        .menu a.active {
            background: #eaf2ff;
            color: #1769e0;
        }

        .logout {
            margin-top: 35px;
        }

        .logout a {
            display: block;

            padding: 12px;
            border-radius: 9px;

            color: #ef4444;
            font-size: 13px;
            font-weight: 600;
        }

        .logout a:hover {
            background: #fff1f2;
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
            color: #3977d5;
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
            border: 1px solid #e1e9f6;

            padding: 9px 13px;
            border-radius: 10px;

            flex-shrink: 0;
        }

        .profile-icon {
            width: 35px;
            height: 35px;

            background: #eaf2ff;
            color: #1769e0;

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
            color: #94a3b8;
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
            border: 1px solid #e1e9f6;
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
            color: #718096;
            font-size: 12px;
        }

        .stat-icon {
            width: 38px;
            height: 38px;

            border-radius: 9px;
            background: #edf5ff;

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
            border: 1px solid #e1e9f6;
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
            color: #1769e0;
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
            border-bottom: 1px solid #edf1f7;
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

            background: #f1f6ff;
            border-radius: 9px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;
        }

        .order-name {
            font-size: 12px;
            font-weight: 700;
        }

        .order-id {
            color: #94a3b8;
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
            background: #fff7ed;
            color: #ea580c;
        }

        .processing {
            background: #eff6ff;
            color: #2563eb;
        }

        /* =========================
           PRODUCTS
        ========================= */

        .product-row {
            display: flex;
            align-items: center;

            gap: 12px;

            padding: 13px 0;
            border-bottom: 1px solid #edf1f7;
        }

        .product-row:last-child {
            border-bottom: none;
        }

        .product-icon {
            width: 42px;
            height: 42px;

            border-radius: 9px;
            background: #edf5ff;

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
        }

        .product-sales {
            color: #94a3b8;
            font-size: 10px;
            margin-top: 4px;
        }

        .product-price {
            color: #1769e0;
            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        /* =========================
           QUICK ACTIONS
        ========================= */

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);

            gap: 12px;
        }

        .quick-action {
            background: #f7faff;
            border: 1px solid #e5edfa;
            border-radius: 10px;

            padding: 17px;

            text-align: center;

            transition: 0.2s;
        }

        .quick-action:hover {
            background: #edf5ff;
            border-color: #cfe0fa;

            transform: translateY(-2px);
        }

        .quick-action-icon {
            font-size: 22px;
            margin-bottom: 8px;
        }

        .quick-action span {
            display: block;

            color: #334155;
            font-size: 11px;
            font-weight: 700;
        }

        /* =========================
           ACCOUNTS
        ========================= */

        .accounts-panel {
            margin-top: 20px;
        }

        .accounts-description {
            color: #718096;
            font-size: 12px;
            margin-top: 5px;
        }

        .manage-button {
            background: #1769e0;
            color: white;

            padding: 10px 15px;
            border-radius: 8px;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .manage-button:hover {
            background: #1258bd;
        }

        .account-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);

            gap: 12px;
            margin-top: 20px;
        }

        .account-stat {
            background: #f8faff;
            border: 1px solid #e1e9f6;
            border-radius: 12px;

            padding: 18px;
        }

        .account-stat-title {
            color: #64748b;
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

            background: #f8faff;
            color: #64748b;

            font-size: 11px;
        }

        .account-table td {
            padding: 13px 12px;

            border-bottom: 1px solid #edf1f7;

            font-size: 13px;
        }

        .account-email {
            color: #64748b;
        }

        .role-badge {
            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 10px;
            font-weight: 700;
        }

        .role-buyer {
            background: #e8f2ff;
            color: #1769e0;
        }

        .role-seller {
            background: #fff7ed;
            color: #c2410c;
        }

        .role-rider {
            background: #f0fdf4;
            color: #15803d;
        }

        .no-accounts {
            text-align: center;

            padding: 30px;

            color: #94a3b8;
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
                color: #1769e0;
            }

            .admin-label,
            .menu a span,
            .logout a span {
                display: none;
            }

            .menu a,
            .logout a {
                text-align: center;
                font-size: 18px;
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
        }

        /* =========================
           SMALL MOBILE
        ========================= */

        @media (max-width: 550px) {

            .stats {
                grid-template-columns: 1fr;
            }

            .quick-actions {
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

            .manage-button {
                font-size: 10px;
                padding: 9px 10px;
            }
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

            <a href="/admin" class="active">
                📊 <span>Dashboard</span>
            </a>

            <a href="/admin/products">
                📦 <span>Products</span>
            </a>

            <a href="/admin/orders">
                🛒 <span>Orders</span>
            </a>

            <a href="{{ route('admin.accounts') }}">
                👥 <span>Accounts</span>
            </a>

            <a href="#">
                📈 <span>Reports</span>
            </a>

            <a href="#">
                ⚙️ <span>Settings</span>
            </a>

        </nav>

        <div class="logout">

            <a href="/admin/login">
                🚪 <span>Logout</span>
            </a>

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
                    8
                </div>

                <div class="stat-change">
                    ↑ 2 new this month
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
                    24
                </div>

                <div class="stat-change">
                    ↑ 12% from last month
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
                    15
                </div>

                <div class="stat-change">
                    ↑ 5 new customers
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
                    ₱45,890
                </div>

                <div class="stat-change">
                    ↑ 8% from last month
                </div>

            </div>

        </section>


        <!-- =========================
             ORDERS + TOP PRODUCTS
        ========================= -->

        <div class="content-grid">

            <!-- RECENT ORDERS -->

            <section class="panel">

                <div class="panel-header">

                    <h2>
                        Recent Orders
                    </h2>

                    <a href="/admin/orders" class="view-all">
                        View All
                    </a>

                </div>


                <div class="order">

                    <div class="order-info">

                        <div class="order-icon">
                            📱
                        </div>

                        <div>

                            <div class="order-name">
                                Nova X5 Pro
                            </div>

                            <div class="order-id">
                                #BB-1001 · Joshua
                            </div>

                        </div>

                    </div>

                    <div class="order-right">

                        <div class="order-price">
                            ₱18,999
                        </div>

                        <span class="status completed">
                            Completed
                        </span>

                    </div>

                </div>


                <div class="order">

                    <div class="order-info">

                        <div class="order-icon">
                            💻
                        </div>

                        <div>

                            <div class="order-name">
                                AirBook 14
                            </div>

                            <div class="order-id">
                                #BB-1002 · Maria
                            </div>

                        </div>

                    </div>

                    <div class="order-right">

                        <div class="order-price">
                            ₱34,990
                        </div>

                        <span class="status processing">
                            Processing
                        </span>

                    </div>

                </div>


                <div class="order">

                    <div class="order-info">

                        <div class="order-icon">
                            🎧
                        </div>

                        <div>

                            <div class="order-name">
                                SoundCore Pro
                            </div>

                            <div class="order-id">
                                #BB-1003 · Carlo
                            </div>

                        </div>

                    </div>

                    <div class="order-right">

                        <div class="order-price">
                            ₱2,799
                        </div>

                        <span class="status pending">
                            Pending
                        </span>

                    </div>

                </div>


                <div class="order">

                    <div class="order-info">

                        <div class="order-icon">
                            ⌚
                        </div>

                        <div>

                            <div class="order-name">
                                FitWatch S2
                            </div>

                            <div class="order-id">
                                #BB-1004 · Andrea
                            </div>

                        </div>

                    </div>

                    <div class="order-right">

                        <div class="order-price">
                            ₱3,499
                        </div>

                        <span class="status completed">
                            Completed
                        </span>

                    </div>

                </div>

            </section>


            <!-- TOP PRODUCTS -->

            <section class="panel">

                <div class="panel-header">

                    <h2>
                        Top Products
                    </h2>

                    <a href="/products" class="view-all">
                        Shop
                    </a>

                </div>


                <div class="product-row">

                    <div class="product-icon">
                        📱
                    </div>

                    <div class="product-info">

                        <div class="product-name">
                            Nova X5 Pro
                        </div>

                        <div class="product-sales">
                            42 sold
                        </div>

                    </div>

                    <div class="product-price">
                        ₱18,999
                    </div>

                </div>


                <div class="product-row">

                    <div class="product-icon">
                        🎧
                    </div>

                    <div class="product-info">

                        <div class="product-name">
                            SoundCore Pro
                        </div>

                        <div class="product-sales">
                            36 sold
                        </div>

                    </div>

                    <div class="product-price">
                        ₱2,799
                    </div>

                </div>


                <div class="product-row">

                    <div class="product-icon">
                        ⌚
                    </div>

                    <div class="product-info">

                        <div class="product-name">
                            FitWatch S2
                        </div>

                        <div class="product-sales">
                            29 sold
                        </div>

                    </div>

                    <div class="product-price">
                        ₱3,499
                    </div>

                </div>


                <div class="product-row">

                    <div class="product-icon">
                        🎮
                    </div>

                    <div class="product-info">

                        <div class="product-name">
                            GamePad X
                        </div>

                        <div class="product-sales">
                            24 sold
                        </div>

                    </div>

                    <div class="product-price">
                        ₱2,199
                    </div>

                </div>

            </section>

        </div>


        <!-- =========================
             QUICK ACTIONS
        ========================= -->

        <section class="panel">

            <div class="panel-header">

                <h2>
                    Quick Actions
                </h2>

            </div>


            <div class="quick-actions">

                <a href="/admin/products/add" class="quick-action">

                    <div class="quick-action-icon">
                        ➕
                    </div>

                    <span>
                        Add Product
                    </span>

                </a>


                <a href="/admin/products" class="quick-action">

                    <div class="quick-action-icon">
                        📦
                    </div>

                    <span>
                        Manage Products
                    </span>

                </a>


                <a href="/admin/orders" class="quick-action">

                    <div class="quick-action-icon">
                        🛒
                    </div>

                    <span>
                        View Orders
                    </span>

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

                                            <span class="role-badge role-buyer">
                                                🛒 Buyer
                                            </span>

                                        @elseif(($user['role'] ?? '') === 'seller')

                                            <span class="role-badge role-seller">
                                                🏪 Seller
                                            </span>

                                        @elseif(($user['role'] ?? '') === 'rider')

                                            <span class="role-badge role-rider">
                                                🛵 Rider
                                            </span>

                                        @else

                                            <span class="role-badge">
                                                {{ ucfirst($user['role'] ?? 'Unknown') }}
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