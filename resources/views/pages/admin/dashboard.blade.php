<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard — GizmoMart</title>

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
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 245px;
            background: #ffffff;
            border-right: 1px solid #e1e9f6;
            padding: 25px 18px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
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
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 25px;
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

        .main {
            margin-left: 245px;
            width: calc(100% - 245px);
            padding: 35px 5%;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .panel-header h2 {
            font-size: 17px;
        }

        .view-all {
            color: #1769e0;
            font-size: 11px;
            font-weight: 700;
        }

        .order {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        }

        .order-icon {
            width: 38px;
            height: 38px;
            background: #f1f6ff;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
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
        }

        .product-info {
            flex: 1;
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
        }

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

        @media (max-width: 1100px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .content-grid {
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
                content: "G";
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
                gap: 15px;
            }

            .topbar h1 {
                font-size: 25px;
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

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .admin-profile {
                padding: 8px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">
            Gizmo<span>Mart</span>
        </div>

        <div class="admin-label">
            Administration
        </div>

        <nav class="menu">

            <a href="/admin" class="active">
                📊 <span>Dashboard</span>
            </a>

            <!-- PRODUCTS -->
            <a href="/admin/products">
                📦 <span>Products</span>
            </a>

            <!-- ORDERS -->
            <a href="/admin/orders">
                🛒 <span>Orders</span>
            </a>

            <a href="#">
                👥 <span>Customers</span>
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


    <!-- MAIN -->

    <main class="main">

        <div class="topbar">

            <div>

                <small>
                    GizmoMart Administration
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


        <!-- STATS -->

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


        <!-- CONTENT -->

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
                                #GM-1001 · Joshua
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
                                #GM-1002 · Maria
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
                                #GM-1003 · Carlo
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
                                #GM-1004 · Andrea
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


        <!-- QUICK ACTIONS -->

        <section class="panel">

            <div class="panel-header">

                <h2>
                    Quick Actions
                </h2>

            </div>


            <div class="quick-actions">

                <!-- ADD PRODUCT -->
                <a href="/admin/products/add" class="quick-action">

                    <div class="quick-action-icon">
                        ➕
                    </div>

                    <span>
                        Add Product
                    </span>

                </a>


                <!-- MANAGE PRODUCTS -->
                <a href="/admin/products" class="quick-action">

                    <div class="quick-action-icon">
                        📦
                    </div>

                    <span>
                        Manage Products
                    </span>

                </a>


                <!-- VIEW ORDERS -->
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

    </main>

</div>

</body>
</html>