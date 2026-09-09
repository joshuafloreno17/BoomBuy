<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product — BoomBuy</title>

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

        .navbar {
            background: white;
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

        .admin-label {
            color: #8d6c62;
            font-size: 13px;
        }

        .back {
            color: #e8420f;
            font-size: 13px;
            font-weight: 600;
        }

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

        .success {
            background: #eaf8ef;
            color: #15803d;
            border: 1px solid #bbebca;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .error {
            background: #fff0f0;
            color: #dc2626;
            border: 1px solid #ffd0d0;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .form-box {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 16px;
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
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
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #e8420f;
            background: white;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .hint {
            color: #a99088;
            font-size: 11px;
            margin-top: 6px;
        }

        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .save-btn {
            border: none;
            background: #e8420f;
            color: white;
            padding: 13px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .save-btn:hover {
            background: #c43408;
        }

        .cancel-btn {
            background: #f9f0ed;
            color: #7c5a50;
            padding: 13px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .cancel-btn:hover {
            background: #f3e5e0;
        }

        footer {
            background: white;
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

        @media (max-width: 600px) {

            .container {
                width: 92%;
            }

            .form-box {
                padding: 20px;
            }

            .buttons {
                flex-direction: column;
            }

            .save-btn,
            .cancel-btn {
                text-align: center;
            }

            footer {
                flex-direction: column;
                gap: 8px;
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


<nav class="navbar">

    <a href="/admin" class="logo">
        Boom<span>Buy</span>
    </a>

    <div class="admin-label">
        Admin Panel
    </div>

    <a href="{{ route('admin.products') }}" class="back">
        ← Products
    </a>

</nav>


<main class="container">


    <div class="header">

        <small>
            Administration
        </small>

        <h1>
            Add Product
        </h1>

        <p>
            Add a new product to your BoomBuy marketplace.
        </p>

    </div>


    @if(session('error'))

        <div class="error">
            ✕ {{ session('error') }}
        </div>

    @endif


    @if($errors->any())

        <div class="error">

            @foreach($errors->all() as $error)

                <div>
                    ✕ {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    <div class="form-box">

        <!-- IMPORTANT: POST FORM -->

        <form
            action="{{ route('admin.products.store') }}"
            method="POST"
        >

            @csrf


            <div class="form-group">

                <label for="name">
                    Product Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Example: iPhone 15 Pro"
                    required
                >

            </div>


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

                    <option value="smartphone"
                        {{ old('category') == 'smartphone' ? 'selected' : '' }}>
                        Smartphone
                    </option>

                    <option value="laptop"
                        {{ old('category') == 'laptop' ? 'selected' : '' }}>
                        Laptop
                    </option>

                    <option value="audio"
                        {{ old('category') == 'audio' ? 'selected' : '' }}>
                        Audio
                    </option>

                    <option value="wearable"
                        {{ old('category') == 'wearable' ? 'selected' : '' }}>
                        Wearable
                    </option>

                    <option value="accessories"
                        {{ old('category') == 'accessories' ? 'selected' : '' }}>
                        Accessories
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label for="price">
                    Price
                </label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    value="{{ old('price') }}"
                    placeholder="Example: 18999"
                    min="0"
                    step="0.01"
                    required
                >

            </div>


            <div class="form-group">

                <label for="icon">
                    Product Icon
                </label>

                <input
                    type="text"
                    id="icon"
                    name="icon"
                    value="{{ old('icon') }}"
                    placeholder="Example: 📱"
                    required
                >

                <div class="hint">
                    You can use an emoji such as 📱 💻 🎧 ⌚ 🎮
                </div>

            </div>


            <div class="form-group">

                <label for="description">
                    Product Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Enter product description..."
                    required
                >{{ old('description') }}</textarea>

            </div>


            <div class="buttons">

                <button
                    type="submit"
                    class="save-btn"
                >
                    ✓ Save Product
                </button>


                <a
                    href="{{ route('admin.products') }}"
                    class="cancel-btn"
                >
                    Cancel
                </a>

            </div>


        </form>

    </div>


</main>


<footer>

    <div>
        © 2026 <strong>BoomBuy</strong>
    </div>

    <div>
        Product Management
    </div>

</footer>


</body>

</html>