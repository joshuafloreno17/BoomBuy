<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Add Product — BoomBuy Seller'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-add-product.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar
        active="products"
        :user="$user"
    />


    <!-- MAIN -->
    <main class="main-content">

        <div class="container">

            <x-seller-page-head title="Add Product" subtitle="Add a new product to your BoomBuy store." />


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
                            color: #8a7f86;
                            font-size: 11px;
                        ">
                            Enter the number of units available for sale. If you add variations below, each one keeps its own stock instead.
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
    <div id="category" role="textbox" aria-readonly="true" style="display:flex; align-items:center; gap:8px; min-height:44px; padding:0 14px; border:1px solid #e8d6cc; border-radius:10px; background:#fff8f3; font-weight:700; color:#1b1a1f;">
        <i class="bi bi-lock-fill" style="color:#c2380f;"></i>
        {{ \App\Support\Categories::LIST[$registeredCategory] }}
    </div>
    <small style="display:block; margin-top:6px; font-size:12px; color:#5e5759;">
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

                        <div class="image-upload-box">

                            @include('partials.file-picker', [
                                'id' => 'image',
                                'name' => 'image',
                                'accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp',
                                'label' => 'Upload your product image',
                                'hint' => 'JPG, PNG or WEBP · up to 5 MB · square photos look best',
                                'required' => true,
                            ])

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

                    <!-- MORE PHOTOS -->
                    @include('partials.product-photos-field', ['product' => null])


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

                        <div style="margin-bottom:10px; color:#8a7f86; font-size:11px;">
                            Does this product come in different colors, sizes, etc.? Add them here.
                        </div>

                        {{-- Column names for the rows below; shown once there is a row. --}}
                        <div id="variationHead" class="var-head" hidden>
                            <span>Type</span>
                            <span>Value</span>
                            <span>Extra price (₱) <small>· 0 = same</small></span>
                            <span>Stock</span>
                            <span></span>
                        </div>
                        <div id="variationRows"></div>

                        <button
                            type="button"
                            id="addVariationBtn"
                            style="
                                background:#fff1ea;
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
        var head = document.getElementById('variationHead');
        var addBtn = document.getElementById('addVariationBtn');
        var index = 0;

        function syncHead() {
            head.hidden = rowsContainer.children.length === 0;
        }

        function addRow() {
            var row = document.createElement('div');
            row.className = 'var-row';

            // Each field carries its own label too: phones stack them instead of showing the header.
            row.innerHTML =
                '<label class="var-field"><span>Type</span><input type="text" name="variations[' + index + '][type]" placeholder="e.g. Color" autocomplete="off"></label>' +
                '<label class="var-field"><span>Value</span><input type="text" name="variations[' + index + '][value]" placeholder="e.g. Red"></label>' +
                '<label class="var-field"><span>Extra price (₱) · 0 = same</span><input type="number" step="0.01" name="variations[' + index + '][price_adjustment]" placeholder="0" value="0"></label>' +
                '<label class="var-field"><span>Stock</span><input type="number" min="0" name="variations[' + index + '][stock]" placeholder="0" value="0"></label>' +
                '<button type="button" class="remove-variation-btn" aria-label="Remove this variation"><i class="bi bi-x-lg"></i><span>Remove</span></button>';

            row.querySelector('.remove-variation-btn').addEventListener('click', function () {
                row.remove();
                syncHead();
            });

            rowsContainer.appendChild(row);
            index++;
            syncHead();
        }

        addBtn.addEventListener('click', addRow);
    })();
</script>

    @include('partials.pwa-register')

</body>
</html>
