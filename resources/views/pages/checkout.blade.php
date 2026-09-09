<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Checkout — BoomBuy</title>

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

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #ffe9e2;
            padding: 18px 7%;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #e8420f;
            text-decoration: none;
        }

        .logo span {
            color: #172033;
        }

        .back {
            color: #8d6c62;
            text-decoration: none;
            font-size: 13px;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h1 {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .page-title p {
            color: #977970;
            font-size: 13px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 12px 30px rgba(39, 84, 150, 0.07);
        }

        .card h2 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #563a32;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #fbe2db;
            border-radius: 9px;
            background: #fffaf8;
            outline: none;
            font-size: 13px;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #e8420f;
            background: #ffffff;
            box-shadow:
                0 0 0 3px
                rgba(23, 105, 224, 0.08);
        }

        .error-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 12px 14px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .product {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 16px 0;
            border-bottom: 1px solid #f7efed;
        }

        .product-info {
            display: flex;
            gap: 12px;
        }

        .product-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: #fff2ee;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
        }

        .product-name {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .product-qty {
            font-size: 11px;
            color: #977970;
        }

        .product-price {
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid #ffe9e2;
        }

        .total-label {
            font-size: 15px;
            font-weight: 700;
        }

        .total-price {
            font-size: 22px;
            font-weight: 700;
            color: #e8420f;
        }

        .place-order {
            width: 100%;
            border: none;
            background: #e8420f;
            color: white;
            padding: 15px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            margin-top: 22px;
        }

        .place-order:hover {
            background: #c43408;
        }

        .secure {
            text-align: center;
            color: #b99c93;
            font-size: 11px;
            margin-top: 15px;
        }

        @media (max-width: 800px) {

            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .container {
                width: 94%;
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

</head>

<body>

<nav class="navbar">

    <a href="/" class="logo">
        Boom<span>Buy</span>
    </a>

    <a
        href="{{ route('cart') }}"
        class="back"
    >
        ← Back to Cart
    </a>

</nav>


<div class="container">

    <div class="page-title">

        <h1>Checkout</h1>

        <p>
            Complete your information to place your order.
        </p>

    </div>


    @if(session('error'))

        <div class="error-box">
            {{ session('error') }}
        </div>

    @endif


    <form
        action="{{ route('checkout.place') }}"
        method="POST"
    >

        @csrf


        <div class="checkout-grid">


            {{-- SHIPPING INFORMATION --}}

            <div class="card">

                <h2>
                    📍 Shipping Information
                </h2>


                <div class="form-group">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        value="{{ $user['name'] ?? '' }}"
                        readonly
                    >

                </div>


                <div class="form-group">

                    <label>
                        Email Address
                    </label>

                    <input
                        type="email"
                        value="{{ $user['email'] ?? '' }}"
                        readonly
                    >

                </div>


                <div class="form-group">

                    <label>
                        Delivery Address
                    </label>

                    <input
                        type="text"
                        name="address"
                        value="{{ old('address') }}"
                        placeholder="House No., Street, Barangay, City"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone') }}"
                        placeholder="09XXXXXXXXX"
                        required
                    >

                </div>


                <h2 style="margin-top: 30px;">
                    💳 Payment Method
                </h2>


                <div class="form-group">

                    <label>
                        Select Payment Method
                    </label>

                    <select
                        name="payment"
                        required
                    >

                        <option value="">
                            Select Payment Method
                        </option>

                        <option
                            value="Cash on Delivery"
                            {{ old('payment') === 'Cash on Delivery' ? 'selected' : '' }}
                        >
                            Cash on Delivery
                        </option>

                        <option
                            value="GCash"
                            {{ old('payment') === 'GCash' ? 'selected' : '' }}
                        >
                            GCash
                        </option>

                        <option
                            value="Maya"
                            {{ old('payment') === 'Maya' ? 'selected' : '' }}
                        >
                            Maya
                        </option>

                        <option
                            value="Credit / Debit Card"
                            {{ old('payment') === 'Credit / Debit Card' ? 'selected' : '' }}
                        >
                            Credit / Debit Card
                        </option>

                    </select>

                </div>

            </div>


            {{-- ORDER SUMMARY --}}

            <div class="card">

                <h2>
                    🛒 Order Summary
                </h2>


                @php

                    $total = 0;

                @endphp


                @foreach($cart as $slug => $quantity)

                    @if(isset($products[$slug]))

                        @php

                            $product = $products[$slug];

                            $subtotal =
                                $product['price'] * $quantity;

                            $total += $subtotal;

                        @endphp


                        <div class="product">

                            <div class="product-info">

                                <div class="product-icon">

                                    {{ $product['icon'] ?? '📦' }}

                                </div>


                                <div>

                                    <div class="product-name">

                                        {{ $product['name'] }}

                                    </div>

                                    <div class="product-qty">

                                        Quantity:
                                        {{ $quantity }}

                                    </div>

                                </div>

                            </div>


                            <div class="product-price">

                                ₱{{ number_format($subtotal, 2) }}

                            </div>

                        </div>

                    @endif

                @endforeach


                <div class="total-row">

                    <span class="total-label">
                        Total
                    </span>

                    <span class="total-price">

                        ₱{{ number_format($total, 2) }}

                    </span>

                </div>


                <button
                    type="submit"
                    class="place-order"
                >

                    🛍️ Place Order

                </button>


                <div class="secure">

                    🔒 Your order information is stored securely.

                </div>

            </div>


        </div>

    </form>

</div>

</body>

</html>