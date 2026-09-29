<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product — BoomBuy Seller</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ asset('css/pages/seller-add-product.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar
        active="products"
        :user="$user"
        logo-href="/"
        :show-notifications="false"
        footer="back"
    />


    <!-- MAIN -->
    <main class="main-content">

        <div class="container">

            <!-- HEADER -->
            <div class="header">

                <small>Seller</small>

                <h1>
                    Add Product
                </h1>

                <p>
                    Add a new product to your BoomBuy store.
                </p>

            </div>


            <!-- ERROR -->
            @if(session('error'))

                <div class="error">
                    <i class="bi bi-x-circle-fill"></i> {{ session('error') }}
                </div>

            @endif


            <!-- SUCCESS -->
            @if(session('success'))

                <div class="success">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                </div>

            @endif


            <!-- FORM -->
            <div class="form-box">

                <form
                    action="{{ route('seller.products.store') }}"
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

                    <!-- QUANTITY / STOCK -->
                    <div class="form-group">
                        <label for="stock">Quantity / Stock</label>

                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            value="{{ old('stock', 1) }}"
                            min="1"
                            step="1"
                            placeholder="Example: 50"
                            required
                        >

                        <div style="
                            margin-top: 7px;
                            color: #a0847b;
                            font-size: 11px;
                        ">
                            Enter the number of units available for sale.
                        </div>
                    </div>


                    <!-- CATEGORY -->
                    <div class="form-group">
                        <label for="category">
                            Category
                        </label>

                        @if(!empty($registeredCategory))
    {{-- Locked: a seller only sells in the category they registered for. --}}
    <input type="hidden" name="category" value="{{ $registeredCategory }}">
    <div id="category" role="textbox" aria-readonly="true" style="display:flex; align-items:center; gap:8px; min-height:44px; padding:0 14px; border:1px solid #f0d9d1; border-radius:10px; background:#fff7f4; font-weight:700; color:#172033;">
        <i class="bi bi-lock-fill" style="color:#c43408;"></i>
        {{ \App\Support\Categories::LIST[$registeredCategory] }}
    </div>
    <small style="display:block; margin-top:6px; font-size:12px; color:#6f5a53;">
        Your shop is registered for this category, so all your products go here.
    </small>
    
@else
<select
                            id="category"
                            name="category"
                            required
                        >
                            <option value="">
                                Select Category
                            </option>

                            <option value="electronics"
                                {{ old('category') == 'electronics' ? 'selected' : '' }}>
                                Electronics
                            </option>

                            <option value="womens-fashion"
                                {{ old('category') == 'womens-fashion' ? 'selected' : '' }}>
                                Women's Fashion
                            </option>

                            <option value="mens-fashion"
                                {{ old('category') == 'mens-fashion' ? 'selected' : '' }}>
                                Men's Fashion
                            </option>

                            <option value="kids-baby"
                                {{ old('category') == 'kids-baby' ? 'selected' : '' }}>
                                Kids & Baby
                            </option>

                            <option value="home-living"
                                {{ old('category') == 'home-living' ? 'selected' : '' }}>
                                Home & Living
                            </option>

                            <option value="sports-outdoors"
                                {{ old('category') == 'sports-outdoors' ? 'selected' : '' }}>
                                Sports & Outdoors
                            </option>

                            <option value="beauty-personal-care"
                                {{ old('category') == 'beauty-personal-care' ? 'selected' : '' }}>
                                Beauty & Personal Care
                            </option>

                            <option value="food-beverages"
                                {{ old('category') == 'food-beverages' ? 'selected' : '' }}>
                                Food & Beverages
                            </option>

                            <option value="automotive"
                                {{ old('category') == 'automotive' ? 'selected' : '' }}>
                                Automotive
                            </option>

                            <option value="office-school"
                                {{ old('category') == 'office-school' ? 'selected' : '' }}>
                                Office & School
                            </option>

                            <option value="pet-supplies"
                                {{ old('category') == 'pet-supplies' ? 'selected' : '' }}>
                                Pet Supplies
                            </option>

                            <option value="toys-games-hobbies"
                                {{ old('category') == 'toys-games-hobbies' ? 'selected' : '' }}>
                                Toys, Games & Hobbies
                            </option>

                            <option value="jewelry-accessories"
                                {{ old('category') == 'jewelry-accessories' ? 'selected' : '' }}>
                                Jewelry & Accessories
                            </option>

                            <option value="shoes"
                                {{ old('category') == 'shoes' ? 'selected' : '' }}>
                                Shoes
                            </option>

                            <option value="tools-home-improvement"
                                {{ old('category') == 'tools-home-improvement' ? 'selected' : '' }}>
                                Tools & Home Improvement
                            </option>

                            <option value="garden-outdoor"
                                {{ old('category') == 'garden-outdoor' ? 'selected' : '' }}>
                                Garden & Outdoor
                            </option>
                        </select>
@endif
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

                        <div class="image-upload">

                            <div class="image-upload-icon">
                                                            </div>

                            <div class="image-upload-title">
                                Upload your product image
                            </div>

                            <div class="image-upload-text">
                                JPG, JPEG, PNG, or WEBP • Maximum 5MB
                            </div>

                            <input
                                type="file"
                                id="image"
                                name="image"
                                class="file-input"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                required
                            >

                            <div
                                id="image-preview"
                                class="image-preview"
                            >

                                <img
                                    id="preview-image"
                                    src=""
                                    alt="Product Preview"
                                >

                                <div class="preview-label">
                                    Image Preview
                                </div>

                            </div>

                        </div>

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


                    <!-- VARIATIONS (OPTIONAL) -->
                    <div class="form-group">

                        <label>
                            Variations (Optional)
                        </label>

                        <div style="margin-bottom:10px; color:#a0847b; font-size:11px;">
                            Does this product come in different colors, sizes, etc.? Add them here.
                        </div>

                        <div id="variationRows"></div>
                        <datalist id="variation-type-options">
                            @foreach(\App\Models\ProductVariation::COMMON_TYPES as $typeOption)
                                <option value="{{ $typeOption }}"></option>
                            @endforeach
                        </datalist>

                        <button
                            type="button"
                            id="addVariationBtn"
                            style="
                                background:#fff4f1;
                                color:#e8420f;
                                border:1px dashed #f0b8a5;
                                border-radius:8px;
                                padding:10px 14px;
                                font-size:12px;
                                font-weight:700;
                                cursor:pointer;
                            "
                        >
                            + Add Variation
                        </button>

                    </div>


                    <!-- BUTTONS -->
                    <div class="buttons">

                        <button
                            type="submit"
                            class="save"
                        >
                            <i class="bi bi-check-circle-fill"></i> Add Product
                        </button>

                        <a
                            href="{{ route('seller.dashboard') }}"
                            class="cancel"
                        >
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>

<script src="{{ asset('js/pages/product-image-preview.js') }}"></script>
<script>
    (function () {
        var rowsContainer = document.getElementById('variationRows');
        var addBtn = document.getElementById('addVariationBtn');
        var index = 0;

        function addRow() {
            var row = document.createElement('div');
            row.style.cssText = 'display:grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap:8px; margin-bottom:8px; align-items:center;';

            row.innerHTML =
                '<input type="text" name="variations[' + index + '][type]" placeholder="Type (e.g. Color)" list="variation-type-options" autocomplete="off">' +
                '<input type="text" name="variations[' + index + '][value]" placeholder="Value (e.g. Red)">' +
                '<input type="number" step="0.01" name="variations[' + index + '][price_adjustment]" placeholder="Extra Price (₱)" value="0">' +
                '<input type="number" min="0" name="variations[' + index + '][stock]" placeholder="Stock" value="0">' +
                '<button type="button" class="remove-variation-btn" style="background:#fff1f1; color:#dc2626; border:none; border-radius:6px; padding:10px 12px; font-size:11px; font-weight:700; cursor:pointer;"><i class="bi bi-x-lg"></i></button>';

            row.querySelectorAll('input').forEach(function (input) {
                input.style.cssText = 'width:100%; padding:10px 12px; border:1px solid #f0ddd6; border-radius:8px; font-size:12px; font-family:inherit; outline:none;';
            });

            row.querySelector('.remove-variation-btn').addEventListener('click', function () {
                row.remove();
            });

            rowsContainer.appendChild(row);
            index++;
        }

        addBtn.addEventListener('click', addRow);
    })();
</script>

    @include('partials.pwa-register')

</body>
</html>
