<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Buyer Dashboard — BoomBuy</title>

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
            gap: 20px;
        }

        .user-name {
            color: #64748b;
            font-size: 13px;
        }

        .cart {
            color: #1769e0;
            font-size: 13px;
            font-weight: 700;
        }

        .logout {
            border: none;
            background: #fff0f0;
            color: #dc2626;
            padding: 8px 12px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
        }

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 45px auto 80px;
        }

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

        .section-title {
            margin-bottom: 18px;
        }

        .section-title h2 {
            font-size: 23px;
        }

        .section-title p {
            color: #718096;
            font-size: 13px;
            margin-top: 5px;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .product-card {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 14px;
            padding: 18px;
            transition: 0.2s;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(23, 105, 224, 0.08);
        }

        .product-icon {
            height: 130px;
            border-radius: 10px;
            background: #eaf2ff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 55px;
            margin-bottom: 15px;
        }

        .category {
            color: #3977d5;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 700;
            margin-top: 5px;
        }

        .price {
            color: #1769e0;
            font-size: 17px;
            font-weight: 700;
            margin-top: 8px;
        }

        .view-btn {
            display: block;
            background: #1769e0;
            color: white;
            text-align: center;

            padding: 10px;
            border-radius: 7px;

            font-size: 12px;
            font-weight: 700;

            margin-top: 13px;
        }

        .view-btn:hover {
            background: #0f55bd;
        }

        .empty {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 14px;
            padding: 50px;
            text-align: center;
            color: #718096;
        }

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

        @media (max-width: 950px) {

            .products {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .navbar {
                padding: 15px 5%;
            }

            .nav-right {
                gap: 10px;
            }

            .user-name {
                display: none;
            }

            .container {
                width: 92%;
            }

            .products {
                grid-template-columns: 1fr;
            }

            .welcome h1 {
                font-size: 27px;
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


<nav class="navbar">

    <a href="/" class="logo">
        Boom<span>Buy</span>
    </a>


    <div class="nav-right">

        <span class="user-name">
            Welcome, {{ $user['name'] ?? 'Buyer' }}
        </span>

        <a href="{{ route('cart') }}" class="cart">
            🛒 Cart
        </a>

        <form action="{{ route('logout') }}" method="POST">
            @csrf

            <button class="logout" type="submit">
                Logout
            </button>
        </form>

    </div>

</nav>


<main class="container">


    <section class="welcome">

        <small>
            Buyer Dashboard
        </small>

        <h1>
            Welcome, {{ $user['name'] ?? 'Buyer' }}! 👋
        </h1>

        <p>
            Browse products and find something you like from BoomBuy.
        </p>

    </section>


    <div class="section-title">

        <h2>
            Available Products
        </h2>

        <p>
            Explore the latest products available on BoomBuy.
        </p>

    </div>


    @if(count($products) > 0)

        <div class="products">

            @foreach($products as $product)

                <div class="product-card">

                    <div class="product-icon">
                        {{ $product['icon'] ?? '📦' }}
                    </div>

                    <div class="category">
                        {{ $product['category'] ?? 'Other' }}
                    </div>

                    <div class="product-name">
                        {{ $product['name'] }}
                    </div>

                    <div class="price">
                        ₱{{ number_format($product['price'] ?? 0) }}
                    </div>

                    <a
                        href="{{ route('product.details', $product['slug']) }}"
                        class="view-btn"
                    >
                        View Product
                    </a>

                </div>

            @endforeach

        </div>

    @else

        <div class="empty">

            <h3>
                No Products Available
            </h3>

            <p>
                There are currently no products available.
            </p>

        </div>

    @endif


</main>


<footer>

    <div>
        © 2026 <strong>BoomBuy</strong>
    </div>

    <div>
        Buyer Dashboard
    </div>

</footer>


</body>

</html>