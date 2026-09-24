<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product — BoomBuy</title>

    @include('partials.pwa-head')

    <link rel="stylesheet" href="{{ asset('css/pages/seller-edit-product.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar
        :user="$user"
        logo-href="/"
        :show-notifications="false"
        footer="back"
    />


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

    @include('partials.pwa-register')

</body>

</html>
