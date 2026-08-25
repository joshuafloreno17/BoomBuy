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

        .products {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .card {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 14px;
            padding: 18px;
        }

        .icon {
            height: 120px;
            background: #eaf2ff;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 50px;
            margin-bottom: 15px;
        }

        .category {
            color: #3977d5;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .name {
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
            .container {
                width: 92%;
            }

            .top {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .products {
                grid-template-columns: 1fr;
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


<main class="container">

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


    @if(session('success'))

        <div style="
            background:#eaf8ef;
            color:#15803d;
            padding:13px 16px;
            border-radius:9px;
            margin-bottom:20px;
            font-size:13px;
            font-weight:600;
        ">
            ✓ {{ session('success') }}
        </div>

    @endif


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


    @if(count($products) > 0)

        <div class="products">

            @foreach($products as $product)

                <div class="card">

                    <div class="icon">
                        {{ $product['icon'] ?? '📦' }}
                    </div>

                    <div class="category">
                        {{ $product['category'] ?? 'Other' }}
                    </div>

                    <div class="name">
                        {{ $product['name'] }}
                    </div>

                    <div class="price">
                        ₱{{ number_format($product['price'] ?? 0) }}
                    </div>

                </div>

            @endforeach

        </div>

    @else

        <div class="empty">

            <h3>
                No Products Yet
            </h3>

            <p style="margin-top:8px;">
                Start selling by adding your first product.
            </p>

        </div>

    @endif

</main>


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