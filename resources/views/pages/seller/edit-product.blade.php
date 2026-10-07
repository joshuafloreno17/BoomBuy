<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Edit Product — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-edit-product.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar
        active="dashboard"
        :user="$user"
    />


    {{-- MAIN CONTENT --}}
    <main class="main-content">

        <div class="container">

            <x-seller-page-head
                title="Edit Product"
                subtitle="Update your product information below."
                :crumbs="['My Products' => route('seller.dashboard')]"
            />

            <div class="card">


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

                    <a
                        href="{{ route('seller.products.variations', $product->id) }}"
                        style="float:right; color:#e8420f; font-weight:700; font-size:12px;"
                    >
                        <i class="bi bi-palette"></i> Manage Variations
                    </a>

                </div>


                {{-- UPDATE PRODUCT FORM --}}
                <form
                    action="{{ route('seller.products.update', ['id' => $product->id]) }}"
                    method="POST"
                    enctype="multipart/form-data"
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


                            @if(!empty($registeredCategory))
    {{-- Locked: a seller only sells in the category they registered for. --}}
    <input type="hidden" name="category" value="{{ $registeredCategory }}">
    <div id="category" role="textbox" aria-readonly="true" style="display:flex; align-items:center; gap:8px; min-height:44px; padding:0 14px; border:1px solid #e8d6cc; border-radius:10px; background:#fff8f3; font-weight:700; color:#1b1a1f;">
        <i class="bi bi-lock-fill" style="color:#c2380f;"></i>
        {{ \App\Support\Categories::LIST[$registeredCategory] }}
    </div>
    <small style="display:block; margin-top:6px; font-size:12px; color:#5e5759;">
        Your shop is registered for this category, so all your products go here.
    </small>
    @if(\App\Support\Categories::slug($product->category) !== $registeredCategory)
        <small style="display:block; margin-top:6px; font-size:12px; font-weight:700; color:#8a4b00;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            This product is listed under {{ $product->category }}. Saving moves it to {{ \App\Support\Categories::LIST[$registeredCategory] }}.
        </small>
    @endif
@else
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
                                    Electronics
                                </option>


                                <option
                                    value="womens-fashion"
                                    {{ $selectedCategory === 'womens-fashion' ? 'selected' : '' }}
                                >
                                    Women's Fashion
                                </option>


                                <option
                                    value="mens-fashion"
                                    {{ $selectedCategory === 'mens-fashion' ? 'selected' : '' }}
                                >
                                    Men's Fashion
                                </option>


                                <option
                                    value="kids-baby"
                                    {{ $selectedCategory === 'kids-baby' ? 'selected' : '' }}
                                >
                                    Kids & Baby
                                </option>


                                <option
                                    value="home-living"
                                    {{ $selectedCategory === 'home-living' ? 'selected' : '' }}
                                >
                                    Home & Living
                                </option>


                                <option
                                    value="sports-outdoors"
                                    {{ $selectedCategory === 'sports-outdoors' ? 'selected' : '' }}
                                >
                                    Sports & Outdoors
                                </option>


                                <option
                                    value="beauty-personal-care"
                                    {{ $selectedCategory === 'beauty-personal-care' ? 'selected' : '' }}
                                >
                                    Beauty & Personal Care
                                </option>


                                <option
                                    value="food-beverages"
                                    {{ $selectedCategory === 'food-beverages' ? 'selected' : '' }}
                                >
                                    Food & Beverages
                                </option>


                                <option
                                    value="automotive"
                                    {{ $selectedCategory === 'automotive' ? 'selected' : '' }}
                                >
                                    Automotive
                                </option>


                                <option
                                    value="office-school"
                                    {{ $selectedCategory === 'office-school' ? 'selected' : '' }}
                                >
                                    Office & School
                                </option>


                                <option
                                    value="pet-supplies"
                                    {{ $selectedCategory === 'pet-supplies' ? 'selected' : '' }}
                                >
                                    Pet Supplies
                                </option>


                                <option
                                    value="toys-games-hobbies"
                                    {{ $selectedCategory === 'toys-games-hobbies' ? 'selected' : '' }}
                                >
                                    Toys, Games & Hobbies
                                </option>


                                <option
                                    value="jewelry-accessories"
                                    {{ $selectedCategory === 'jewelry-accessories' ? 'selected' : '' }}
                                >
                                    Jewelry & Accessories
                                </option>


                                <option
                                    value="shoes"
                                    {{ $selectedCategory === 'shoes' ? 'selected' : '' }}
                                >
                                    Shoes
                                </option>


                                <option
                                    value="tools-home-improvement"
                                    {{ $selectedCategory === 'tools-home-improvement' ? 'selected' : '' }}
                                >
                                    Tools & Home Improvement
                                </option>


                                <option
                                    value="garden-outdoor"
                                    {{ $selectedCategory === 'garden-outdoor' ? 'selected' : '' }}
                                >
                                    Garden & Outdoor
                                </option>

                            </select>
@endif

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
                                value="{{ old('price', $product->regularPrice()) }}"
                                required
                            >

                        </div>

                        @include('partials.discount-field', ['discount' => $product->discount_percent])

                    </div>


                    {{-- STOCK (photos are in their own section below) --}}
                    <div class="row">

                        <div class="form-group">

                            <label for="stock">
                                Stock
                            </label>

                            @if($variationCount > 0)
                                {{-- Buyers order an option, so the stock that counts is each option's. --}}
                                <input type="hidden" name="stock" value="{{ $product->stock ?? 0 }}">
                                <div class="stock-by-option">
                                    <strong>{{ number_format($variationStock) }}</strong>
                                    <span>across {{ $variationCount }} {{ \Illuminate\Support\Str::plural('option', $variationCount) }} — stock is set per option.</span>
                                    <a href="{{ route('seller.products.variations', $product->id) }}"><i class="bi bi-sliders"></i> Edit stock per option</a>
                                </div>
                            @else
                                <input
                                    type="number"
                                    id="stock"
                                    name="stock"
                                    min="0"
                                    value="{{ old('stock', $product->stock ?? 0) }}"
                                    required
                                >
                            @endif

                        </div>

                    </div>

                    {{-- MORE PHOTOS --}}
                    @include('partials.product-photos-field', ['product' => $product])


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
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>




    @include('partials.pwa-register')

</body>

</html>
