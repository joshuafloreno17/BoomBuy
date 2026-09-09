<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product — BoomBuy Seller</title>

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
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* NAVBAR */

        .navbar {
            width: 100%;
            background: #ffffff;
            border-bottom: 1px solid #ffe9e2;
            padding: 18px 7%;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .back {
            color: #e8420f;
            font-size: 13px;
            font-weight: 700;
        }

        .back:hover {
            color: #c43408;
        }

        /* CONTAINER */

        .container {
            width: 86%;
            max-width: 850px;
            margin: 45px auto 80px;
        }

        .header {
            margin-bottom: 25px;
        }

        .header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
        }

        .header h1 {
            font-size: 36px;
            margin-top: 8px;
        }

        .header p {
            color: #977970;
            font-size: 14px;
            margin-top: 8px;
        }

        /* ERROR */

        .error {
            background: #fff0f0;
            color: #dc2626;
            border: 1px solid #ffd0d0;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        /* SUCCESS */

        .success {
            background: #effdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        /* FORM */

        .form-box {
            background: #ffffff;
            border: 1px solid #f7e5e0;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(39, 84, 150, 0.08);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #563a32;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #fbe2db;
            background: #fffaf8;
            padding: 12px 14px;
            border-radius: 8px;
            outline: none;

            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            font-size: 13px;
            color: #172033;

            transition: 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #e8420f;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(23, 105, 224, 0.08);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* BUTTONS */

        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .save {
            border: none;
            background: #e8420f;
            color: #ffffff;

            padding: 13px 22px;
            border-radius: 8px;

            font-size: 13px;
            font-weight: 700;

            cursor: pointer;
            transition: 0.2s;
        }

        .save:hover {
            background: #c43408;
            transform: translateY(-1px);
        }

        .cancel {
            background: #f9f0ed;
            color: #7c5a50;

            padding: 13px 22px;
            border-radius: 8px;

            font-size: 13px;
            font-weight: 700;
        }

        .cancel:hover {
            background: #f1e5e1;
        }

        /* MOBILE */

        @media (max-width: 600px) {

            .navbar {
                padding: 16px 5%;
            }

            .container {
                width: 92%;
                margin-top: 30px;
            }

            .header h1 {
                font-size: 28px;
            }

            .form-box {
                padding: 22px;
            }

            .buttons {
                flex-direction: column;
            }

            .save,
            .cancel {
                width: 100%;
                text-align: center;
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


<!-- NAVBAR -->

<nav class="navbar">

    <a href="/" class="logo">
        Boom<span>Buy</span>
    </a>

    <a href="/seller" class="back">
        ← Seller Dashboard
    </a>

</nav>


<!-- MAIN -->

<main class="container">


    <div class="header">

        <small>
            Seller
        </small>

        <h1>
            Add Product
        </h1>

        <p>
            Add a new product to your BoomBuy store.
        </p>

    </div>


    <!-- ERROR MESSAGE -->

    @if(session('error'))

        <div class="error">
            ✕ {{ session('error') }}
        </div>

    @endif


    <!-- SUCCESS MESSAGE -->

    @if(session('success'))

        <div class="success">
            ✓ {{ session('success') }}
        </div>

    @endif


    <!-- FORM -->

    <div class="form-box">

      <form
    action="/seller/products/store"
    method="POST"
    enctype="multipart/form-data"
>
            @csrf


            <!-- PRODUCT NAME -->

            <div class="form-group">

                <label for="name">
                    Product Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Example: Gaming Phone"
                    required
                >

            </div>


            <!-- CATEGORY -->

            <div class="form-group">

                <label for="category">
                    Category
                </label>

                <select
                    id="category"
                    name="category"
                    required
                >

                    <option value="">
                        Select Category
                    </option>

                    <option
                        value="smartphone"
                        {{ old('category') == 'smartphone' ? 'selected' : '' }}
                    >
                        Smartphone
                    </option>

                    <option
                        value="laptop"
                        {{ old('category') == 'laptop' ? 'selected' : '' }}
                    >
                        Laptop
                    </option>

                    <option
                        value="audio"
                        {{ old('category') == 'audio' ? 'selected' : '' }}
                    >
                        Audio
                    </option>

                    <option
                        value="wearable"
                        {{ old('category') == 'wearable' ? 'selected' : '' }}
                    >
                        Wearable
                    </option>

                    <option
                        value="accessories"
                        {{ old('category') == 'accessories' ? 'selected' : '' }}
                    >
                        Accessories
                    </option>

                </select>

            </div>


            <!-- PRICE -->

            <div class="form-group">

                <label for="price">
                    Price
                </label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    value="{{ old('price') }}"
                    min="1"
                    step="0.01"
                    placeholder="Example: 15000"
                    required
                >

            </div>


           <!-- PRODUCT IMAGE -->

<div class="form-group">
    <label for="image">
        Product Image
    </label>

    <input
        type="file"
        id="image"
        name="image"
        accept="image/jpeg,image/png,image/jpg,image/webp"
        required
    >

    <small style="display:block; margin-top:8px; color:#816f6a;">
        Upload a clear product photo. JPG, PNG, or WEBP only.
    </small>
</div>

            <!-- DESCRIPTION -->

            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Enter product description..."
                    required
                >{{ old('description') }}</textarea>

            </div>


            <!-- BUTTONS -->

            <div class="buttons">

                <button
                    type="submit"
                    class="save"
                >
                    ✓ Add Product
                </button>

                <a
                    href="/seller"
                    class="cancel"
                >
                    Cancel
                </a>

            </div>


        </form>

    </div>


</main>


</body>

</html>