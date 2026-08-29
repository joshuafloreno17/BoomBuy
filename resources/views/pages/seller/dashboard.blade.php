```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seller Dashboard — BoomBuy</title>

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

        /* NAVBAR */

        .navbar {
            background: white;
            border-bottom: 1px solid #e2eaff;
            padding: 18px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #1769e0;
        }

        .logo span {
            color: #172033;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .user {
            color: #64748b;
            font-size: 13px;
        }

        .logout {
            border: none;
            background: #fff0f0;
            color: #dc2626;
            padding: 8px 12px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .logout:hover {
            background: #ffe0e0;
        }

        /* CONTAINER */

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 45px auto 80px;
        }

        /* WELCOME */

        .welcome {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .welcome small {
            color: #3977d5;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
        }

        .welcome h1 {
            font-size: 34px;
            margin-top: 8px;
        }

        .welcome p {
            color: #718096;
            font-size: 14px;
            margin-top: 8px;
        }

        /* ALERT */

        .alert-success {
            background: #eaf8ef;
            color: #15803d;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert-error {
            background: #fff0f0;
            color: #dc2626;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        /* STATISTICS */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 14px;
            padding: 20px;

            display: flex;
            align-items: center;
            gap: 15px;

            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 18px rgba(0,0,0,0.06);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            background: #eaf2ff;
            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
            flex-shrink: 0;
        }

        .stat-title {
            color: #718096;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .stat-number {
            color: #172033;
            font-size: 24px;
            font-weight: 700;
        }

        /* SECTION HEADER */

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .top h2 {
            font-size: 23px;
        }

        .add-btn {
            background: #1769e0;
            color: white;
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .add-btn:hover {
            background: #0f55bd;
        }

        /* PRODUCTS TABLE */

        .products-box {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 14px;
            overflow: hidden;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
        }

        .products-table th {
            background: #f8faff;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 15px 18px;
            border-bottom: 1px solid #e5eaf3;
        }

        .products-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #edf1f7;
            font-size: 13px;
            vertical-align: middle;
        }

        .products-table tr:last-child td {
            border-bottom: none;
        }

        .products-table tbody tr:hover {
            background: #fafcff;
        }

        /* PRODUCT */

        .product-info {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .product-icon {
            width: 52px;
            height: 52px;
            background: #eaf2ff;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
            flex-shrink: 0;
        }

        .product-name {
            font-weight: 700;
            color: #172033;
        }

        .product-slug {
            color: #94a3b8;
            font-size: 11px;
            margin-top: 3px;
        }

        .category-badge {
            display: inline-block;
            background: #eef5ff;
            color: #1769e0;
            padding: 6px 9px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
        }

        .product-price {
            color: #1769e0;
            font-weight: 700;
        }

        .rating {
            color: #f59e0b;
            font-weight: 700;
        }

        .reviews {
            color: #94a3b8;
            font-size: 11px;
            margin-left: 3px;
        }

        /* ACTIONS */

        .actions {
            display: flex;
            gap: 7px;
        }

        .edit-btn,
        .delete-btn {
            border: none;
            padding: 8px 11px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .edit-btn {
            background: #eef5ff;
            color: #1769e0;
        }

        .edit-btn:hover {
            background: #dceaff;
        }

        .delete-btn {
            background: #fff0f0;
            color: #dc2626;
        }

        .delete-btn:hover {
            background: #ffe0e0;
        }

        /* EMPTY */

        .empty {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 14px;
            padding: 55px 30px;
            text-align: center;
            color: #718096;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 12px;
        }

        .empty h3 {
            color: #172033;
        }

        /* FOOTER */

        footer {
            background: white;
            border-top: 1px solid #e1e9f6;
            padding: 30px 7%;

            display: flex;
            justify-content: space-between;

            color: #718096;
            font-size: 12px;
        }

        footer strong {
            color: #1769e0;
        }

        /* RESPONSIVE */

        @media (max-width: 900px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .products-box {
                overflow-x: auto;
            }

            .products-table {
                min-width: 800px;
            }
        }

        @media (max-width: 600px) {

            .container {
                width: 92%;
            }

            .navbar {
                padding: 15px 4%;
            }

            .nav-right {
                gap: 8px;
            }

            .user {
                display: none;
            }

            .welcome h1 {
                font-size: 26px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .top {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            footer {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->

<nav class="navbar">

    <a href="/" class="logo">
        Boom<span>Buy</span>
    </a>

    <div class="nav-right">

        <span class="user">
            Seller: {{ $user['name'] ?? 'Seller' }}
        </span>

        <form action="{{ route('logout') }}" method="POST">

            @csrf

            <button type="submit" class="logout">
                Logout
            </button>

        </form>

    </div>

</nav>


<!-- MAIN -->

<main class="container">

    <!-- WELCOME -->

    <section class="welcome">

        <small>
            Seller Dashboard
        </small>

        <h1>
            Welcome, {{ $user['name'] ?? 'Seller' }}! 🏪
        </h1>

        <p>
            Manage your products and sell them through BoomBuy.
        </p>

    </section>


    <!-- SUCCESS MESSAGE -->

    @if(session('success'))

        <div class="alert-success">
            ✓ {{ session('success') }}
        </div>

    @endif


    <!-- ERROR MESSAGE -->

    @if(session('error'))

        <div class="alert-error">
            ✕ {{ session('error') }}
        </div>

    @endif


    <!-- STATISTICS -->

    <section class="stats">

        <div class="stat-card">

            <div class="stat-icon">
                📦
            </div>

            <div>

                <div class="stat-title">
                    Total Products
                </div>

                <div class="stat-number">
                    {{ $totalProducts ?? count($products ?? []) }}
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🧾
            </div>

            <div>

                <div class="stat-title">
                    Total Orders
                </div>

                <div class="stat-number">
                    {{ $totalOrders ?? 0 }}
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⏳
            </div>

            <div>

                <div class="stat-title">
                    Pending Orders
                </div>

                <div class="stat-number">
                    {{ $pendingOrders ?? 0 }}
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                💰
            </div>

            <div>

                <div class="stat-title">
                    Total Sales
                </div>

                <div class="stat-number">
                    ₱{{ number_format($totalSales ?? 0, 2) }}
                </div>

            </div>

        </div>

    </section>


    <!-- PRODUCTS HEADER -->

    <div class="top">

        <h2>
            My Products
        </h2>

        <a
            href="{{ route('seller.products.create') }}"
            class="add-btn"
        >
            + Add Product
        </a>

    </div>


    <!-- PRODUCTS -->

    @if(count($products ?? []) > 0)

        <div class="products-box">

            <table class="products-table">

                <thead>

                    <tr>

                        <th>
                            Product
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Rating
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($products as $product)

                        <tr>

                            <!-- PRODUCT -->

                            <td>

                                <div class="product-info">

                                    <div class="product-icon">
                                        {{ $product['icon'] ?? '📦' }}
                                    </div>

                                    <div>

                                        <div class="product-name">
                                            {{ $product['name'] ?? 'Unnamed Product' }}
                                        </div>

                                        <div class="product-slug">
                                            {{ $product['slug'] ?? '' }}
                                        </div>

                                    </div>

                                </div>

                            </td>


                            <!-- CATEGORY -->

                            <td>

                                <span class="category-badge">
                                    {{ $product['category'] ?? 'Other' }}
                                </span>

                            </td>


                            <!-- PRICE -->

                            <td>

                                <span class="product-price">
                                    ₱{{ number_format($product['price'] ?? 0, 2) }}
                                </span>

                            </td>


                            <!-- RATING -->

                            <td>

                                <span class="rating">
                                    ★ {{ $product['rating'] ?? '0.0' }}
                                </span>

                                <span class="reviews">
                                    ({{ $product['reviews'] ?? 0 }})
                                </span>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <div class="actions">

                                    <a
                                        href="{{ route('seller.products.edit', $product['slug']) }}"
                                        class="edit-btn"
                                    >
                                        ✏ Edit
                                    </a>

                                    <form
                                        action="{{ route('seller.products.delete', $product['slug']) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this product?');"
                                    >

                                        @csrf

                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="delete-btn"
                                        >
                                            🗑 Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @else

        <div class="empty">

            <div class="empty-icon">
                📦
            </div>

            <h3>
                No Products Yet
            </h3>

            <p style="margin-top:8px;">
                Start selling by adding your first product.
            </p>

        </div>

    @endif

</main>


<!-- FOOTER -->

<footer>

    <div>
        © 2026 <strong>BoomBuy</strong>
    </div>

    <div>
        Seller Dashboard
    </div>

</footer>

</body>
</html>
```
