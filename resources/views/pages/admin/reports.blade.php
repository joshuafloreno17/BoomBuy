<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports — BoomBuy</title>

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
            margin-bottom: 30px;
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
           SUMMARY CARDS
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

        .stat-sub {
            color: #94a3b8;
            font-size: 10px;
            margin-top: 7px;
        }

        /* =========================
           PANELS
        ========================= */

        .panel {
            background: #ffffff;
            border: 1px solid #e1e9f6;
            border-radius: 15px;

            padding: 23px;

            margin-bottom: 20px;
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

        .panel-description {
            color: #718096;
            font-size: 11px;
            margin-top: 5px;
        }

        /* =========================
           SALES TABLE
        ========================= */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 650px;

            border-collapse: collapse;
        }

        th {
            text-align: left;

            padding: 13px;

            background: #f8faff;
            color: #64748b;

            font-size: 10px;
            letter-spacing: .5px;
        }

        td {
            padding: 14px 13px;

            border-bottom: 1px solid #edf1f7;

            font-size: 12px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .amount {
            font-weight: 700;
            color: #1769e0;
        }

        .status {
            display: inline-block;

            padding: 5px 8px;

            border-radius: 5px;

            font-size: 9px;
            font-weight: 700;
        }

        .completed {
            background: #ecfdf5;
            color: #16a34a;
        }

        .processing {
            background: #eff6ff;
            color: #2563eb;
        }

        .pending {
            background: #fff7ed;
            color: #ea580c;
        }

        /* =========================
           REPORT GRID
        ========================= */

        .report-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;

            gap: 20px;
        }

        /* =========================
           SALES OVERVIEW
        ========================= */

        .sales-chart {
            height: 260px;

            display: flex;
            align-items: flex-end;
            justify-content: space-around;

            gap: 14px;

            padding: 20px 10px 0;

            border-bottom: 1px solid #e1e9f6;
        }

        .bar-item {
            flex: 1;

            height: 100%;

            display: flex;
            flex-direction: column;

            justify-content: flex-end;
            align-items: center;

            gap: 8px;
        }

        .bar {
            width: 45px;

            background: #1769e0;

            border-radius: 7px 7px 0 0;

            min-height: 20px;

            transition: .2s;
        }

        .bar:hover {
            background: #1258bd;
        }

        .bar-label {
            font-size: 10px;
            color: #718096;
        }

        .bar-value {
            font-size: 9px;
            color: #64748b;
        }

        /* =========================
           CATEGORY REPORT
        ========================= */

        .category {
            padding: 14px 0;

            border-bottom: 1px solid #edf1f7;
        }

        .category:last-child {
            border-bottom: none;
        }

        .category-top {
            display: flex;
            justify-content: space-between;

            margin-bottom: 7px;
        }

        .category-name {
            font-size: 12px;
            font-weight: 700;
        }

        .category-value {
            color: #1769e0;
            font-size: 11px;
            font-weight: 700;
        }

        .progress {
            width: 100%;
            height: 7px;

            background: #edf2f9;

            border-radius: 20px;

            overflow: hidden;
        }

        .progress-bar {
            height: 100%;

            background: #1769e0;

            border-radius: 20px;
        }

        /* =========================
           EXPORT BUTTON
        ========================= */

        .export-button {
            display: inline-block;

            background: #1769e0;
            color: #ffffff;

            padding: 10px 15px;

            border-radius: 8px;

            font-size: 11px;
            font-weight: 700;
        }

        .export-button:hover {
            background: #1258bd;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .report-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 750px) {

            .sidebar {
                width: 70px;
                padding: 20px 10px;
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

            .profile-name,
            .profile-role {
                display: none;
            }
        }

        @media (max-width: 550px) {

            .stats {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .panel {
                padding: 18px;
            }

            .bar {
                width: 30px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">
            Boom<span>Buy</span>
        </div>

        <div class="admin-label">
            Administration
        </div>

        <nav class="menu">

            <a href="{{ route('admin.dashboard') }}">
                📊 <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.products') }}">
                📦 <span>Products</span>
            </a>

            <a href="{{ route('admin.orders') }}">
                🛒 <span>Orders</span>
            </a>

            <a href="{{ route('admin.accounts') }}">
                👥 <span>Accounts</span>
            </a>

            <a href="{{ route('admin.reports') }}" class="active">
                📈 <span>Reports</span>
            </a>

            <a href="{{ route('admin.settings') }}">
                ⚙️ <span>Settings</span>
            </a>

        </nav>

        <div class="logout">

            <a href="{{ route('admin.login') }}">
                🚪 <span>Logout</span>
            </a>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="main">

        <!-- TOPBAR -->

        <div class="topbar">

            <div>

                <small>
                    BoomBuy Administration
                </small>

                <h1>
                    Reports
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


        <!-- SUMMARY -->

        <section class="stats">

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Total Revenue
                    </div>

                    <div class="stat-icon">
                        💰
                    </div>

                </div>

                <div class="stat-value">
                    ₱45,890
                </div>

                <div class="stat-sub">
                    Revenue from completed orders
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

                <div class="stat-sub">
                    Orders recorded
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Completed Orders
                    </div>

                    <div class="stat-icon">
                        ✅
                    </div>

                </div>

                <div class="stat-value">
                    16
                </div>

                <div class="stat-sub">
                    Successfully delivered
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-title">
                        Average Order
                    </div>

                    <div class="stat-icon">
                        📊
                    </div>

                </div>

                <div class="stat-value">
                    ₱1,912
                </div>

                <div class="stat-sub">
                    Average order value
                </div>

            </div>

        </section>


        <!-- SALES + CATEGORY -->

        <div class="report-grid">

            <!-- SALES OVERVIEW -->

            <section class="panel">

                <div class="panel-header">

                    <div>

                        <h2>
                            Sales Overview
                        </h2>

                        <p class="panel-description">
                            Monthly sales performance
                        </p>

                    </div>

                </div>


                <div class="sales-chart">

                    <div class="bar-item">

                        <div class="bar-value">
                            ₱4.2K
                        </div>

                        <div class="bar" style="height: 35%;"></div>

                        <div class="bar-label">
                            Jan
                        </div>

                    </div>


                    <div class="bar-item">

                        <div class="bar-value">
                            ₱5.8K
                        </div>

                        <div class="bar" style="height: 48%;"></div>

                        <div class="bar-label">
                            Feb
                        </div>

                    </div>


                    <div class="bar-item">

                        <div class="bar-value">
                            ₱6.5K
                        </div>

                        <div class="bar" style="height: 54%;"></div>

                        <div class="bar-label">
                            Mar
                        </div>

                    </div>


                    <div class="bar-item">

                        <div class="bar-value">
                            ₱7.1K
                        </div>

                        <div class="bar" style="height: 60%;"></div>

                        <div class="bar-label">
                            Apr
                        </div>

                    </div>


                    <div class="bar-item">

                        <div class="bar-value">
                            ₱8.3K
                        </div>

                        <div class="bar" style="height: 70%;"></div>

                        <div class="bar-label">
                            May
                        </div>

                    </div>


                    <div class="bar-item">

                        <div class="bar-value">
                            ₱6.9K
                        </div>

                        <div class="bar" style="height: 58%;"></div>

                        <div class="bar-label">
                            Jun
                        </div>

                    </div>


                    <div class="bar-item">

                        <div class="bar-value">
                            ₱7.1K
                        </div>

                        <div class="bar" style="height: 60%;"></div>

                        <div class="bar-label">
                            Jul
                        </div>

                    </div>

                </div>

            </section>


            <!-- CATEGORY SALES -->

            <section class="panel">

                <div class="panel-header">

                    <div>

                        <h2>
                            Sales by Category
                        </h2>

                        <p class="panel-description">
                            Product category performance
                        </p>

                    </div>

                </div>


                <div class="category">

                    <div class="category-top">

                        <span class="category-name">
                            📱 Smartphones
                        </span>

                        <span class="category-value">
                            38%
                        </span>

                    </div>

                    <div class="progress">
                        <div
                            class="progress-bar"
                            style="width: 38%;"
                        ></div>
                    </div>

                </div>


                <div class="category">

                    <div class="category-top">

                        <span class="category-name">
                            💻 Laptops
                        </span>

                        <span class="category-value">
                            27%
                        </span>

                    </div>

                    <div class="progress">
                        <div
                            class="progress-bar"
                            style="width: 27%;"
                        ></div>
                    </div>

                </div>


                <div class="category">

                    <div class="category-top">

                        <span class="category-name">
                            🎧 Audio
                        </span>

                        <span class="category-value">
                            18%
                        </span>

                    </div>

                    <div class="progress">
                        <div
                            class="progress-bar"
                            style="width: 18%;"
                        ></div>
                    </div>

                </div>


                <div class="category">

                    <div class="category-top">

                        <span class="category-name">
                            ⌚ Wearables
                        </span>

                        <span class="category-value">
                            11%
                        </span>

                    </div>

                    <div class="progress">
                        <div
                            class="progress-bar"
                            style="width: 11%;"
                        ></div>
                    </div>

                </div>


                <div class="category">

                    <div class="category-top">

                        <span class="category-name">
                            🎮 Gaming
                        </span>

                        <span class="category-value">
                            6%
                        </span>

                    </div>

                    <div class="progress">
                        <div
                            class="progress-bar"
                            style="width: 6%;"
                        ></div>
                    </div>

                </div>

            </section>

        </div>


        <!-- ORDER REPORT -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Order Report
                    </h2>

                    <p class="panel-description">
                        Recent order and sales activity
                    </p>

                </div>

                <a href="{{ route('admin.orders') }}" class="export-button">
                    View Orders →
                </a>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                ORDER ID
                            </th>

                            <th>
                                CUSTOMER
                            </th>

                            <th>
                                PRODUCT
                            </th>

                            <th>
                                AMOUNT
                            </th>

                            <th>
                                STATUS
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>

                            <td>
                                #BB-1001
                            </td>

                            <td>
                                Joshua
                            </td>

                            <td>
                                Nova X5 Pro
                            </td>

                            <td class="amount">
                                ₱18,999
                            </td>

                            <td>
                                <span class="status completed">
                                    Completed
                                </span>
                            </td>

                        </tr>


                        <tr>

                            <td>
                                #BB-1002
                            </td>

                            <td>
                                Maria
                            </td>

                            <td>
                                AirBook 14
                            </td>

                            <td class="amount">
                                ₱34,990
                            </td>

                            <td>
                                <span class="status processing">
                                    Processing
                                </span>
                            </td>

                        </tr>


                        <tr>

                            <td>
                                #BB-1003
                            </td>

                            <td>
                                Carlo
                            </td>

                            <td>
                                SoundCore Pro
                            </td>

                            <td class="amount">
                                ₱2,799
                            </td>

                            <td>
                                <span class="status pending">
                                    Pending
                                </span>
                            </td>

                        </tr>


                        <tr>

                            <td>
                                #BB-1004
                            </td>

                            <td>
                                Andrea
                            </td>

                            <td>
                                FitWatch S2
                            </td>

                            <td class="amount">
                                ₱3,499
                            </td>

                            <td>
                                <span class="status completed">
                                    Completed
                                </span>
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- PRODUCT PERFORMANCE -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Product Performance
                    </h2>

                    <p class="panel-description">
                        Best-selling products
                    </p>

                </div>

                <a href="{{ route('admin.products') }}" class="export-button">
                    Manage Products →
                </a>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                PRODUCT
                            </th>

                            <th>
                                CATEGORY
                            </th>

                            <th>
                                UNITS SOLD
                            </th>

                            <th>
                                PRICE
                            </th>

                            <th>
                                PERFORMANCE
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>

                            <td>
                                📱 Nova X5 Pro
                            </td>

                            <td>
                                Smartphone
                            </td>

                            <td>
                                42
                            </td>

                            <td class="amount">
                                ₱18,999
                            </td>

                            <td>
                                ⭐⭐⭐⭐⭐
                            </td>

                        </tr>


                        <tr>

                            <td>
                                🎧 SoundCore Pro
                            </td>

                            <td>
                                Audio
                            </td>

                            <td>
                                36
                            </td>

                            <td class="amount">
                                ₱2,799
                            </td>

                            <td>
                                ⭐⭐⭐⭐⭐
                            </td>

                        </tr>


                        <tr>

                            <td>
                                ⌚ FitWatch S2
                            </td>

                            <td>
                                Wearable
                            </td>

                            <td>
                                29
                            </td>

                            <td class="amount">
                                ₱3,499
                            </td>

                            <td>
                                ⭐⭐⭐⭐
                            </td>

                        </tr>


                        <tr>

                            <td>
                                🎮 GamePad X
                            </td>

                            <td>
                                Gaming
                            </td>

                            <td>
                                24
                            </td>

                            <td class="amount">
                                ₱2,199
                            </td>

                            <td>
                                ⭐⭐⭐⭐
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>