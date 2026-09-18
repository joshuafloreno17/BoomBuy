<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart — BoomBuy</title>

    @include('partials.pwa-head')

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

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 45px auto 80px;
        }

        .back {
            display: inline-block;
            color: #db5a33;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 34px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #977970;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .success {
            background: #ecfdf3;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .error {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
            align-items: start;
        }

        .cart-box {
            background: #fff;
            border: 1px solid #f7e5e0;
            border-radius: 18px;
            overflow: hidden;
        }

        .cart-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f9ebe7;
            font-size: 16px;
            font-weight: 700;
        }

        .cart-item {
            padding: 22px 24px;
            display: grid;
            grid-template-columns: 90px 1fr auto;
            gap: 18px;
            align-items: center;
            border-bottom: 1px solid #f7efed;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 90px;
            height: 90px;
            border-radius: 13px;
            background: linear-gradient(145deg, #ffede8, #ffdfd5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 43px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info h3 {
            font-size: 16px;
            margin-bottom: 6px;
        }

        .category {
            display: inline-block;
            background: #fff1ed;
            color: #db5a33;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .unit-price {
            color: #977970;
            font-size: 12px;
        }

        .quantity {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #fbe2db;
            background: #fffaf8;
            color: #e8420f;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 700;
        }

        .qty-btn:hover {
            background: #ffefea;
        }

        .qty-number {
            min-width: 25px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }

        .item-right {
            text-align: right;
        }

        .item-total {
            color: #e8420f;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .remove-btn {
            border: none;
            background: transparent;
            color: #ef4444;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        .empty-cart {
            background: #fff;
            border: 1px solid #f7e5e0;
            border-radius: 18px;
            padding: 70px 25px;
            text-align: center;
        }

        .empty-icon {
            font-size: 65px;
            margin-bottom: 15px;
        }

        .empty-cart h2 {
            font-size: 22px;
            margin-bottom: 8px;
        }

        .empty-cart p {
            color: #977970;
            font-size: 13px;
            margin-bottom: 22px;
        }

        .shop-btn {
            display: inline-block;
            background: #e8420f;
            color: #fff;
            padding: 13px 22px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
        }

        .summary {
            background: #fff;
            border: 1px solid #f7e5e0;
            border-radius: 18px;
            padding: 25px;
            position: sticky;
            top: 20px;
        }

        .summary h2 {
            font-size: 19px;
            margin-bottom: 22px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            color: #8d6c62;
            font-size: 13px;
        }

        .summary-row strong {
            color: #172033;
        }

        .summary-total {
            border-top: 1px solid #f6e8e4;
            margin-top: 18px;
            padding-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-total span {
            font-size: 14px;
            font-weight: 700;
        }

        .summary-total strong {
            color: #e8420f;
            font-size: 24px;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            border: none;
            background: #e8420f;
            color: #fff;
            padding: 14px;
            border-radius: 9px;
            margin-top: 22px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }

        .checkout-btn:hover {
            background: #c43408;
        }

        .continue {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #db5a33;
            font-size: 12px;
            font-weight: 600;
        }

        footer {
            background: #fff;
            border-top: 1px solid #f7e5e0;
            padding: 30px 7%;
            display: flex;
            justify-content: space-between;
            color: #977970;
            font-size: 12px;
        }

        footer strong {
            color: #e8420f;
        }

        h1, h2, h3, .logo {
            font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }

        button,
        .btn,
        .checkout-btn,
        .shop-btn {
            border-radius: 12px !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        button:hover,
        .btn:hover,
        .checkout-btn:hover,
        .shop-btn:hover {
            transform: translateY(-1px);
        }

        ::selection {
            background: #ffd7c2;
            color: #7c1a00;
        }

        @media (max-width: 850px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .summary {
                position: static;
            }
        }

        @media (max-width: 600px) {
            .container {
                width: 92%;
            }

            .cart-item {
                grid-template-columns: 70px 1fr;
            }

            .product-image {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }

            .item-right {
                grid-column: 2;
                text-align: left;
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

@include('partials.buyer-navbar', ['activeNav' => 'cart'])

<div class="container">

    <a href="/products" class="back">
        ← Continue Shopping
    </a>

    <h1>Shopping Cart</h1>

    <p class="subtitle">
        Review your items before checkout.
    </p>

    @if(session('success'))
        <div class="success">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="error">
            {{ session('error') }}
        </div>
    @endif

    @php
        /*
        |--------------------------------------------------------------------------
        | GET CART
        |--------------------------------------------------------------------------
        */

        $cart = session()->get('cart', []);

        /*
        |--------------------------------------------------------------------------
        | GET PRODUCTS FROM DATABASE
        |--------------------------------------------------------------------------
        */

        $databaseProducts = \App\Models\Product::whereIn(
            'id',
            array_keys($cart)
        )->get()->keyBy('id');

        $subtotal = 0;
        $totalItems = 0;
    @endphp

    @if(empty($cart))

        <div class="empty-cart">

            <div class="empty-icon">
                🛒
            </div>

            <h2>
                Your cart is empty
            </h2>

            <p>
                Looks like you haven't added anything yet.
            </p>

            <a href="/products" class="shop-btn">
                🛍️ Start Shopping
            </a>

        </div>

    @else

        <div class="cart-layout">

            <div class="cart-box">

                <div class="cart-header">
                    Cart Items
                </div>

                @foreach($cart as $productId => $quantity)

                    @php
                        $product = $databaseProducts->get($productId);
                    @endphp

                    @if($product)

                        @php
                            $quantity = (int) $quantity;

                            $itemTotal =
                                (float) $product->price * $quantity;

                            $subtotal += $itemTotal;

                            $totalItems += $quantity;
                        @endphp

                        <div class="cart-item" id="cart-item-{{ $product->id }}" data-product-id="{{ $product->id }}">

                            <div class="product-image">

                                @if($product->image)

                                    <img
                                        src="{{ asset('storage/' . ltrim($product->image, '/')) }}"
                                        alt="{{ $product->name }}"
                                    >

                                @else

                                    📦

                                @endif

                            </div>

                            <div class="product-info">

                                <span class="category">
                                    {{ $product->category ?? 'Other' }}
                                </span>

                                <h3>
                                    {{ $product->name }}
                                </h3>

                                <div class="unit-price">
                                    ₱{{ number_format($product->price, 2) }} each
                                </div>

                                <div class="quantity">

                                    <form
                                        class="qty-form"
                                        data-product-id="{{ $product->id }}"
                                        action="{{ route('cart.update', $product->id) }}"
                                        method="POST"
                                    >

                                        @csrf

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="decrease"
                                        >

                                        <button
                                            type="submit"
                                            class="qty-btn"
                                        >
                                            −
                                        </button>

                                    </form>

                                    <span class="qty-number" id="qty-{{ $product->id }}">
                                        {{ $quantity }}
                                    </span>

                                    <form
                                        class="qty-form"
                                        data-product-id="{{ $product->id }}"
                                        action="{{ route('cart.update', $product->id) }}"
                                        method="POST"
                                    >

                                        @csrf

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="increase"
                                        >

                                        <button
                                            type="submit"
                                            class="qty-btn"
                                        >
                                            +
                                        </button>

                                    </form>

                                </div>

                            </div>

                            <div class="item-right">

                                <div class="item-total" id="item-total-{{ $product->id }}">
                                    ₱{{ number_format($itemTotal, 2) }}
                                </div>

                                <form
                                    class="remove-form"
                                    data-product-id="{{ $product->id }}"
                                    action="{{ route('cart.remove', $product->id) }}"
                                    method="POST"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="remove-btn"
                                    >
                                        🗑 Remove
                                    </button>

                                </form>

                            </div>

                        </div>

                    @endif

                @endforeach

            </div>

            <div class="summary">

                <h2>
                    Order Summary
                </h2>

                <div class="summary-row">

                    <span>
                        Items
                    </span>

                    <strong id="summary-items">
                        {{ $totalItems }}
                    </strong>

                </div>

                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <strong id="summary-subtotal">
                        ₱{{ number_format($subtotal, 2) }}
                    </strong>

                </div>

                <div class="summary-row">

                    <span>
                        Shipping
                    </span>

                    <strong>
                        FREE
                    </strong>

                </div>

                <div class="summary-total">

                    <span>
                        Total
                    </span>

                    <strong id="summary-total">
                        ₱{{ number_format($subtotal, 2) }}
                    </strong>

                </div>

                <a
                    href="{{ route('checkout') }}"
                    class="checkout-btn"
                >
                    💳 Proceed to Checkout
                </a>

                <a
                    href="/products"
                    class="continue"
                >
                    Continue Shopping
                </a>

            </div>

        </div>

    @endif

</div>

<footer>

    <div>
        © 2026 <strong>BoomBuy</strong>
    </div>

    <div>
        Quality products. Better everyday.
    </div>

</footer>

    <script>
        (function () {
            function updateCartBadge(count) {
                var badge = document.getElementById('cartCount');
                if (!badge) return;

                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            }

            function applySummary(data) {
                var itemsEl = document.getElementById('summary-items');
                var subtotalEl = document.getElementById('summary-subtotal');
                var totalEl = document.getElementById('summary-total');

                if (itemsEl) itemsEl.textContent = data.total_items;
                if (subtotalEl) subtotalEl.textContent = '₱' + data.subtotal;
                if (totalEl) totalEl.textContent = '₱' + data.subtotal;

                if (typeof data.cart_count !== 'undefined') {
                    updateCartBadge(data.cart_count);
                }
            }

            function submitCartForm(form) {
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(form)
                })
                    .then(function (res) {
                        return res.json();
                    })
                    .then(function (data) {
                        return data;
                    })
                    .catch(function () {
                        form.submit();
                        return null;
                    })
                    .then(function (data) {
                        if (!data) return;

                        var productId = form.dataset.productId;

                        if (data.removed) {
                            var row = document.getElementById('cart-item-' + productId);
                            if (row) row.remove();
                        } else {
                            var qtyEl = document.getElementById('qty-' + productId);
                            var totalEl = document.getElementById('item-total-' + productId);

                            if (qtyEl) qtyEl.textContent = data.quantity;
                            if (totalEl) totalEl.textContent = '₱' + data.item_total;
                        }

                        if (document.querySelectorAll('.cart-item').length === 0) {
                            location.reload();
                            return;
                        }

                        applySummary(data);
                    });
            }

            document.querySelectorAll('.qty-form, .remove-form').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    submitCartForm(form);
                });
            });
        })();
    </script>

    @include('partials.pwa-register')

</body>
</html>