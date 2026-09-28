<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product — BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">

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
            background: #fff3f0;
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

        .current-image {
            width: 90px;
            height: 90px;
            border-radius: 10px;
            object-fit: cover;
            margin-bottom: 10px;
            border: 1px solid #f7e5e0;
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

<div class="layout">

    <x-layout.admin-sidebar active="products" />

    <main class="main">
    <div class="container">


    <div class="header">

        <small>
            Administration
        </small>

        <h1>
            Edit Product
        </h1>

        <p>
            Update this product's details.
        </p>

    </div>


    @if(session('error'))

        <div class="error">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            {{ session('error') }}
        </div>

    @endif


    @if($errors->any())

        <div class="error">

            @foreach($errors->all() as $error)

                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    <div class="form-box">

        <form
            action="{{ route('admin.products.update', ['id' => $product->id]) }}"
            method="POST"
            enctype="multipart/form-data"
        >

            @csrf
            @method('PUT')


            <div class="form-group">

                <label for="name">
                    Product Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $product->name) }}"
                    placeholder="Example: iPhone 15 Pro"
                    required
                >

            </div>


            <div class="form-group">

                <label for="category">
                    Category
                </label>

                @php
                    $currentCategory = \App\Support\Categories::slug(old('category', $product->category));
                @endphp

                <select
                    id="category"
                    name="category"
                    required
                >

                    <option value="">
                        Select Category
                    </option>

                    @foreach(\App\Support\Categories::LIST as $categorySlug => $categoryLabel)
                        <option value="{{ $categorySlug }}" {{ $currentCategory === $categorySlug ? 'selected' : '' }}>
                            {{ $categoryLabel }}
                        </option>
                    @endforeach

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
                    value="{{ old('price', $product->price) }}"
                    placeholder="Example: 18999"
                    min="0"
                    step="0.01"
                    required
                >

            </div>


            <div class="form-group">

                <label for="stock">
                    Stock Quantity
                </label>

                <input
                    type="number"
                    id="stock"
                    name="stock"
                    value="{{ old('stock', $product->stock) }}"
                    min="0"
                    step="1"
                    placeholder="Example: 50"
                    required
                >

            </div>


            <div class="form-group">

                <label for="image">
                    Product Image
                </label>

                @if($product->image)

                    <img
                        class="current-image"
                        src="{{ str_starts_with($product->image, 'http') ? $product->image : asset('storage/' . ltrim($product->image, '/')) }}"
                        alt="{{ $product->name }}"
                    >

                @endif

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >

                <div class="hint">
                    Leave empty to keep the current photo. JPG, JPEG, PNG, or WEBP • Maximum 5MB
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
                >{{ old('description', $product->description) }}</textarea>

            </div>


            <div class="buttons">

                <button
                    type="submit"
                    class="save-btn"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><polyline points="20 6 9 17 4 12"/></svg>
                    Save Changes
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


    </div>
    </main>

</div>

<footer>

    <div>
        © 2026 <strong>BoomBuy</strong>
    </div>

    <div>
        Product Management
    </div>

</footer>


    @include('partials.pwa-register')

</body>

</html>
