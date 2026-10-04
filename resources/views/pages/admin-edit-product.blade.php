<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Edit Product — BoomBuy'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">

    <link rel="stylesheet" href="{{ vasset('css/views/admin-edit-product.css') }}">

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
