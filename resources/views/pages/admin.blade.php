<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

```
<title>Admin Dashboard — GizmoMart</title>

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
    }

    a {
        text-decoration: none;
        color: inherit;
    }

    /* =========================
       SIDEBAR
    ========================= */

    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 235px;
        height: 100vh;
        background: #ffffff;
        border-right: 1px solid #f7e5e0;
        padding: 28px 18px;
        z-index: 1000;
    }

    .logo {
        font-size: 23px;
        font-weight: 700;
        color: #e8420f;
        padding: 0 12px;
        margin-bottom: 40px;
    }

    .logo span {
        color: #172033;
    }

    .admin-label {
        color: #b99c93;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 0 12px;
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
        border-radius: 8px;
        color: #8d6c62;
        font-size: 13px;
        transition: 0.2s;
    }

    .menu a:hover {
        background: #fff4f1;
        color: #e8420f;
    }

    .menu a.active {
        background: #e8420f;
        color: white;
        font-weight: 600;
    }

    .logout {
        position: absolute;
        bottom: 25px;
        left: 18px;
        right: 18px;
    }

    .logout a {
        display: block;
        padding: 12px;
        border-radius: 8px;
        color: #8d6c62;
        font-size: 13px;
    }

    .logout a:hover {
        background: #fff1f1;
        color: #dc2626;
    }

    /* =========================
       MAIN
    ========================= */

    .main {
        margin-left: 235px;
        padding: 35px 45px;
    }

    /* =========================
       HEADER
    ========================= */

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
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
        font-size: 30px;
        margin-top: 7px;
    }

    .admin-profile {
        background: white;
        border: 1px solid #f7e5e0;
        padding: 10px 15px;
        border-radius: 10px;
        color: #7c5a50;
        font-size: 13px;
    }

    /* =========================
       STAT CARDS
    ========================= */

    .stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border: 1px solid #f7e5e0;
        border-radius: 14px;
        padding: 22px;
        transition: 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(39, 84, 150, 0.08);
    }

    .stat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .stat-title {
        color: #977970;
        font-size: 12px;
    }

    .stat-icon {
        width: 35px;
        height: 35px;
        background: #fff1ed;
        color: #e8420f;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
    }

    .stat-number {
        font-size: 28px;
        font-weight: 700;
        margin-top: 15px;
    }

    .stat-change {
        color: #16a34a;
        font-size: 11px;
        margin-top: 7px;
    }

    /* =========================
       CONTENT GRID
    ========================= */

    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }

    .panel {
        background: white;
        border: 1px solid #f7e5e0;
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
        font-size: 18px;
    }

    .view-all {
        color: #e8420f;
        font-size: 12px;
        font-weight: 600;
    }

    /* =========================
       TABLE
    ========================= */

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        text-align: left;
        color: #b99c93;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 8px;
        border-bottom: 1px solid #f7efed;
    }

    td {
        padding: 14px 8px;
        border-bottom: 1px solid #f8f2f0;
        font-size: 12px;
    }

    .order-product {
        font-weight: 600;
    }

    .order-id {
        color: #b99c93;
    }

    .status {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 600;
    }

    .completed {
        background: #eaf8ef;
        color: #16a34a;
    }

    .pending {
        background: #fff7e6;
        color: #d9a106;
    }

    .processing {
        background: #fff1ed;
        color: #e8420f;
    }

    /* =========================
       QUICK ACTIONS
    ========================= */

    .actions {
        display: grid;
        gap: 10px;
    }

    .action {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px;
        border: 1px solid #fbe9e4;
        background: #fffaf8;
        border-radius: 9px;
        transition: 0.2s;
        cursor: pointer;
    }

    .action:hover {
        border-color: #fad3c7;
        background: #fff4f1;
        transform: translateX(3px);
    }

    .action-icon {
        width: 35px;
        height: 35px;
        background: #ffefea;
        color: #e8420f;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .action strong {
        display: block;
        font-size: 12px;
        margin-bottom: 3px;
    }

    .action small {
        color: #a99088;
        font-size: 10px;
    }

    /* =========================
       FOOTER
    ========================= */

    footer {
        margin-top: 30px;
        padding: 20px 0;
        color: #b99c93;
        font-size: 11px;
        border-top: 1px solid #f7e5e0;
        display: flex;
        justify-content: space-between;
    }

    /* =========================
       RESPONSIVE
    ========================= */

    @media (max-width: 1050px) {

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
        }

        .logo::first-letter {
            font-size: 23px;
        }

        .logo span,
        .admin-label,
        .menu a span {
            display: none;
        }

        .menu a {
            text-align: center;
            font-size: 18px;
        }

        .logout {
            left: 10px;
            right: 10px;
        }

        .logout a {
            text-align: center;
            font-size: 18px;
        }

        .main {
            margin-left: 70px;
            padding: 25px 20px;
        }

        .topbar {
            align-items: flex-start;
            gap: 15px;
        }

    }

    @media (max-width: 550px) {

        .stats {
            grid-template-columns: 1fr;
        }

        .topbar {
            flex-direction: column;
        }

        .admin-profile {
            width: 100%;
        }

        .panel {
            overflow-x: auto;
        }

        table {
            min-width: 600px;
        }

        footer {
            flex-direction: column;
            gap: 7px;
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
```

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

```
<div class="logo">
    Gizmo<span>Mart</span>
</div>

<div class="admin-label">
    Administration
</div>

<div class="menu">

    <a href="/admin" class="active">
        📊 <span>Dashboard</span>
    </a>

    <a href="/admin/products">
        📦 <span>Products</span>
    </a>

    <a href="#">
        👥 <span>Users</span>
    </a>

    <a href="#">
        🛒 <span>Orders</span>
    </a>

    <a href="#">
        ⚙️ <span>Settings</span>
    </a>

</div>

<div class="logout">

    <a href="/">
        🚪 <span>Back to Store</span>
    </a>

</div>
```

</aside>

<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">

```
<!-- HEADER -->

<div class="topbar">

    <div>

        <small>
            GizmoMart Administration
        </small>

        <h1>
            Admin Dashboard
        </h1>

    </div>

    <div class="admin-profile">
        👤 Administrator
    </div>

</div>


<!-- =========================
     STATISTICS
========================= -->

<div class="stats">

    <div class="stat-card">

        <div class="stat-top">

            <div class="stat-title">
                Total Products
            </div>

            <div class="stat-icon">
                📦
            </div>

        </div>

        <div class="stat-number">
            8
        </div>

        <div class="stat-change">
            ↑ 2 new this month
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-top">

            <div class="stat-title">
                Total Users
            </div>

            <div class="stat-icon">
                👥
            </div>

        </div>

        <div class="stat-number">
            24
        </div>

        <div class="stat-change">
            ↑ 8% this month
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

        <div class="stat-number">
            56
        </div>

        <div class="stat-change">
            ↑ 12% this month
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

        <div class="stat-number">
            ₱128K
        </div>

        <div class="stat-change">
            ↑ 15% this month
        </div>

    </div>

</div>


<!-- =========================
     CONTENT
========================= -->

<div class="content-grid">


    <!-- RECENT ORDERS -->

    <div class="panel">

        <div class="panel-header">

            <h2>
                Recent Orders
            </h2>

            <a href="#" class="view-all">
                View all →
            </a>

        </div>


        <table>

            <thead>

                <tr>

                    <th>
                        Order
                    </th>

                    <th>
                        Product
                    </th>

                    <th>
                        Amount
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td class="order-id">
                        #GM-1001
                    </td>

                    <td class="order-product">
                        Nova X5 Pro
                    </td>

                    <td>
                        ₱18,999
                    </td>

                    <td>
                        <span class="status completed">
                            Completed
                        </span>
                    </td>

                </tr>


                <tr>

                    <td class="order-id">
                        #GM-1002
                    </td>

                    <td class="order-product">
                        AirBook 14
                    </td>

                    <td>
                        ₱34,990
                    </td>

                    <td>
                        <span class="status processing">
                            Processing
                        </span>
                    </td>

                </tr>


                <tr>

                    <td class="order-id">
                        #GM-1003
                    </td>

                    <td class="order-product">
                        SoundCore Pro
                    </td>

                    <td>
                        ₱2,799
                    </td>

                    <td>
                        <span class="status pending">
                            Pending
                        </span>
                    </td>

                </tr>


                <tr>

                    <td class="order-id">
                        #GM-1004
                    </td>

                    <td class="order-product">
                        FitWatch S2
                    </td>

                    <td>
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


    <!-- QUICK ACTIONS -->

    <div class="panel">

        <div class="panel-header">

            <h2>
                Quick Actions
            </h2>

        </div>


        <div class="actions">

            <!-- MANAGE PRODUCTS -->

            <a href="/admin/products" class="action">

                <div class="action-icon">
                    📦
                </div>

                <div>

                    <strong>
                        Manage Products
                    </strong>

                    <small>
                        View your products
                    </small>

                </div>

            </a>


            <!-- ADD PRODUCT -->

            <a href="/admin/products/create" class="action">

                <div class="action-icon">
                    ➕
                </div>

                <div>

                    <strong>
                        Add Product
                    </strong>

                    <small>
                        Create new product
                    </small>

                </div>

            </a>


            <!-- MANAGE USERS -->

            <a href="#" class="action">

                <div class="action-icon">
                    👥
                </div>

                <div>

                    <strong>
                        Manage Users
                    </strong>

                    <small>
                        View registered users
                    </small>

                </div>

            </a>


            <!-- VIEW ORDERS -->

            <a href="#" class="action">

                <div class="action-icon">
                    🛒
                </div>

                <div>

                    <strong>
                        View Orders
                    </strong>

                    <small>
                        Manage customer orders
                    </small>

                </div>

            </a>

        </div>

    </div>

</div>


<!-- FOOTER -->

<footer>

    <div>
        © 2026 GizmoMart Admin
    </div>

    <div>
        Quality tech. Better everyday.
    </div>

</footer>
```

</main>

</body>
</html>
