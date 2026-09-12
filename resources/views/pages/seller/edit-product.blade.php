<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product — BoomBuy</title>

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

        .layout {
            display: flex;
        }

        /* ===== SIDEBAR ===== */

        .sidebar {
            width: 230px;
            background: #ffffff;
            border-right: 1px solid #ffe9e2;
            padding: 25px 18px;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            overflow-y: auto;
        }

        .logo {
            font-family: 'Baloo 2', sans-serif;
            font-size: 23px;
            font-weight: 800;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .sidebar-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #b99c93;
            margin-top: 22px;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .sidebar .menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 8px;
        }

        .sidebar .menu a {
            display: block;
            padding: 12px;
            border-radius: 10px;
            color: #8d6c62;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .sidebar .menu a:hover {
            background: #fff4f1;
            color: #e8420f;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid #ffe9e2;
        }

        .sidebar-footer .back {
            display: block;
            padding: 12px;
            border-radius: 10px;
            color: #8d6c62;
            font-size: 13px;
            font-weight: 600;
        }

        .sidebar-footer .back:hover {
            background: #fff4f1;
            color: #e8420f;
        }

        /* ===== MAIN ===== */

        .main-content {
            margin-left: 230px;
            width: calc(100% - 230px);
            min-width: 0;
        }

        .container {
            width: 86%;
            max-width: 850px;
            margin: 45px auto 80px;
        }

        .card {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 16px;
            padding: 32px;
        }

        .header {
            margin-bottom: 28px;
        }

        .header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
        }

        .header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 30px;
            margin-top: 8px;
            letter-spacing: -0.01em;
        }

        .header p {
            color: #977970;
            font-size: 13px;
            margin-top: 7px;
        }

        /* ===== CURRENT PRODUCT ===== */

        .current-product {
            background: #fffaf8;
            border: 1px solid #f7e5e0;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 25px;
            color: #8d6c62;
            font-size: 12px;
        }

        .current-product strong {
            color: #172033;
        }

        /* ===== FORM ===== */

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
            color: #563a32;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #f1ded8;
            border-radius: 8px;
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            font-size: 13px;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #e8420f;
            box-shadow: 0 0 0 3px rgba(232, 66, 15, 0.08);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        /* ===== CATEGORY ===== */

        select {
            cursor: pointer;
        }

        select option {
            padding: 8px;
        }

        /* ===== BUTTONS ===== */

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 28px;
        }

        .cancel-btn,
        .save-btn {
            border: none;
            padding: 11px 17px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .cancel-btn {
            background: #f7f0ee;
            color: #6a4e46;
        }

        .cancel-btn:hover {
            background: #f1e5e1;
            transform: translateY(-1px);
        }

        .save-btn {
            background: #e8420f;
            color: white;
        }

        .save-btn:hover {
            background: #c43408;
            transform: translateY(-1px);
        }

        /* ===== ERROR ===== */

        .error {
            background: #fff3f0;
            color: #dc2626;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
        }

        /* ===== FOOTER ===== */

        footer {
            margin-left: 230px;
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

        /* ===== RESPONSIVE ===== */

        @media (max-width: 900px) {

            .sidebar {
                width: 72px;
                padding: 20px 8px;
            }

            .sidebar .label-text,
            .sidebar-label {
                display: none;
            }

            .sidebar .menu a {
                text-align: center;
            }

            .sidebar-footer .back {
                text-align: center;
                font-size: 0;
            }

            .sidebar-footer .back::before {
                content: "←";
                font-size: 18px;
            }

            .main-content {
                margin-left: 72px;
                width: calc(100% - 72px);
            }

            footer {
                margin-left: 72px;
            }
        }

        @media (max-width: 600px) {

            .container {
                width: 92%;
                margin-top: 25px;
            }

            .card {
                padding: 22px;
            }

            .row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .header h1 {
                font-size: 25px;
            }

            .actions {
                flex-direction: column;
            }

            .cancel-btn,
            .save-btn {
                width: 100%;
                text-align: center;
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

<div class="layout">

    {{-- SIDEBAR --}}
    <aside class="sidebar">

        <a href="/" class="logo">
            Boom<span>Buy</span>
        </a>

        <div class="sidebar-label">
            Seller Panel
        </div>

        <nav class="menu">

            <a href="{{ route('seller.dashboard') }}">
                📊 <span class="label-text">Dashboard</span>
            </a>

            <a href="{{ route('seller.products.create') }}">
                ➕ <span class="label-text">Add Product</span>
            </a>

            <a href="{{ route('seller.orders') }}">
                🛒 <span class="label-text">Orders</span>
            </a>

        </nav>

        <div class="sidebar-footer">

            <a href="{{ route('seller.dashboard') }}" class="back">
                ← Back to Dashboard
            </a>

        </div>

    </aside>


    {{-- MAIN CONTENT --}}
    <main class="main-content">

        <div class="container">

            <div class="card">

                <div class="header">

                    <small>
                        Seller Dashboard
                    </small>

                    <h1>
                        Edit Product
                    </h1>

                    <p>
                        Update your product information below.
                    </p>

                </div>


                {{-- ERROR MESSAGE --}}
                @if($errors->any())

                    <div class="error">

                        @foreach($errors->all() as $error)

                            <div>
                                • {{ $error }}
                            </div>

                        @endforeach

                    </div>

                @endif


                {{-- SESSION ERROR --}}
                @if(session('error'))

                    <div class="error">
                        {{ session('error') }}
                    </div>

                @endif


                {{-- CURRENT PRODUCT --}}
                <div class="current-product">

                    Editing product:

                    <strong>
                        {{ $product->name ?? 'Unnamed Product' }}
                    </strong>

                    —

                    {{ \Illuminate\Support\Str::slug($product->name) }}

                </div>


                {{-- UPDATE PRODUCT FORM --}}
                <form
                    action="{{ route('seller.products.update', ['id' => $product->id]) }}"
                    method="POST"
                >

                    @csrf

                    @method('PUT')


                    {{-- PRODUCT NAME --}}
                    <div class="form-group">

                        <label for="name">
                            Product Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $product->name ?? '') }}"
                            required
                        >

                    </div>


                    {{-- CATEGORY + PRICE --}}
                    <div class="row">

                        <div class="form-group">

                            <label for="category">
                                Category
                            </label>

                            @php

                                $currentCategory = old(
                                    'category',
                                    $product->category ?? ''
                                );

                                /*
                                Convert old categories into the
                                new BoomBuy category system.
                                */

                                $categoryAliases = [

                                    'Smartphone' => 'electronics',
                                    'Laptop' => 'electronics',
                                    'Audio' => 'electronics',
                                    'Wearable' => 'electronics',

                                    'Accessories' => 'jewelry-accessories',

                                    'Electronics' => 'electronics',
                                    "Women's Fashion" => 'womens-fashion',
                                    "Men's Fashion" => 'mens-fashion',
                                    'Kids & Baby' => 'kids-baby',
                                    'Home & Living' => 'home-living',
                                    'Sports & Outdoors' => 'sports-outdoors',
                                    'Beauty & Personal Care' => 'beauty-personal-care',
                                    'Food & Beverages' => 'food-beverages',
                                    'Automotive' => 'automotive',
                                    'Office & School' => 'office-school',
                                    'Pet Supplies' => 'pet-supplies',
                                    'Toys, Games & Hobbies' => 'toys-games-hobbies',
                                    'Jewelry & Accessories' => 'jewelry-accessories',
                                    'Shoes' => 'shoes',
                                    'Tools & Home Improvement' => 'tools-home-improvement',
                                    'Garden & Outdoor' => 'garden-outdoor',

                                ];

                                $selectedCategory =
                                    $categoryAliases[$currentCategory]
                                    ?? $currentCategory;

                            @endphp


                            <select
                                id="category"
                                name="category"
                                required
                            >

                                <option value="">
                                    Select Category
                                </option>


                                <option
                                    value="electronics"
                                    {{ $selectedCategory === 'electronics' ? 'selected' : '' }}
                                >
                                    📱 Electronics
                                </option>


                                <option
                                    value="womens-fashion"
                                    {{ $selectedCategory === 'womens-fashion' ? 'selected' : '' }}
                                >
                                    👗 Women's Fashion
                                </option>


                                <option
                                    value="mens-fashion"
                                    {{ $selectedCategory === 'mens-fashion' ? 'selected' : '' }}
                                >
                                    👕 Men's Fashion
                                </option>


                                <option
                                    value="kids-baby"
                                    {{ $selectedCategory === 'kids-baby' ? 'selected' : '' }}
                                >
                                    👶 Kids & Baby
                                </option>


                                <option
                                    value="home-living"
                                    {{ $selectedCategory === 'home-living' ? 'selected' : '' }}
                                >
                                    🏠 Home & Living
                                </option>


                                <option
                                    value="sports-outdoors"
                                    {{ $selectedCategory === 'sports-outdoors' ? 'selected' : '' }}
                                >
                                    ⚽ Sports & Outdoors
                                </option>


                                <option
                                    value="beauty-personal-care"
                                    {{ $selectedCategory === 'beauty-personal-care' ? 'selected' : '' }}
                                >
                                    💄 Beauty & Personal Care
                                </option>


                                <option
                                    value="food-beverages"
                                    {{ $selectedCategory === 'food-beverages' ? 'selected' : '' }}
                                >
                                    🍔 Food & Beverages
                                </option>


                                <option
                                    value="automotive"
                                    {{ $selectedCategory === 'automotive' ? 'selected' : '' }}
                                >
                                    🚗 Automotive
                                </option>


                                <option
                                    value="office-school"
                                    {{ $selectedCategory === 'office-school' ? 'selected' : '' }}
                                >
                                    📚 Office & School
                                </option>


                                <option
                                    value="pet-supplies"
                                    {{ $selectedCategory === 'pet-supplies' ? 'selected' : '' }}
                                >
                                    🐶 Pet Supplies
                                </option>


                                <option
                                    value="toys-games-hobbies"
                                    {{ $selectedCategory === 'toys-games-hobbies' ? 'selected' : '' }}
                                >
                                    🎮 Toys, Games & Hobbies
                                </option>


                                <option
                                    value="jewelry-accessories"
                                    {{ $selectedCategory === 'jewelry-accessories' ? 'selected' : '' }}
                                >
                                    💍 Jewelry & Accessories
                                </option>


                                <option
                                    value="shoes"
                                    {{ $selectedCategory === 'shoes' ? 'selected' : '' }}
                                >
                                    👟 Shoes
                                </option>


                                <option
                                    value="tools-home-improvement"
                                    {{ $selectedCategory === 'tools-home-improvement' ? 'selected' : '' }}
                                >
                                    🧰 Tools & Home Improvement
                                </option>


                                <option
                                    value="garden-outdoor"
                                    {{ $selectedCategory === 'garden-outdoor' ? 'selected' : '' }}
                                >
                                    🌱 Garden & Outdoor
                                </option>

                            </select>

                        </div>


                        {{-- PRICE --}}
                        <div class="form-group">

                            <label for="price">
                                Price
                            </label>

                            <input
                                type="number"
                                id="price"
                                name="price"
                                step="0.01"
                                min="0"
                                value="{{ old('price', $product->price ?? '') }}"
                                required
                            >

                        </div>

                    </div>


                    {{-- ICON + STOCK --}}
                    <div class="row">

                        <div class="form-group">

                            <label for="icon">
                                Product Icon
                            </label>

                            <input
                                type="text"
                                id="icon"
                                name="icon"
                                value="{{ old('icon', $product->image ?? '📦') }}"
                                placeholder="Example: 💻"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="stock">
                                Stock
                            </label>

                            <input
                                type="number"
                                id="stock"
                                name="stock"
                                min="0"
                                value="{{ old('stock', $product->stock ?? 0) }}"
                                required
                            >

                        </div>

                    </div>


                    {{-- DESCRIPTION --}}
                    <div class="form-group">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            placeholder="Enter product description..."
                            required
                        >{{ old('description', $product->description ?? '') }}</textarea>

                    </div>


                    {{-- ACTIONS --}}
                    <div class="actions">

                        <a
                            href="{{ route('seller.dashboard') }}"
                            class="cancel-btn"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="save-btn"
                        >
                            ✓ Save Changes
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>


{{-- FOOTER --}}
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