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


                    <!-- OPTIONS AND THEIR PHOTOS (optional) -->
                    <div class="form-group">

                        <label>
                            Options and their photos <span style="font-weight:500; color:#8a7f86;">(optional · up to {{ \App\Support\ProductPhotos::MAX_PER_OPTION }} photos each)</span>
                        </label>

                        <div class="opt-intro">
                            Does it come in different colors, sizes or kinds? Add each one as an option with its own photos. Buyers see only that option's photos after picking it.
                            Leave the photos empty when every option looks the same (like sizes).
                        </div>

                        <div class="opt-type" id="optType" hidden>
                            <label for="variationType">Variation type</label>
                            <input type="text" id="variationType" placeholder="e.g. Color, Size, Mount" autocomplete="off">
                        </div>

                        <div id="variationRows" class="opt-list"></div>

                        <button type="button" id="addVariationBtn" class="opt-add-btn">+ Add option</button>

                    </div>


                    <!-- PHOTOS FOR ALL OPTIONS (or the product's photos when it has no options) -->
                    @include('partials.product-photos-uploader')

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

<script>
    (function () {
        var rowsContainer = document.getElementById('variationRows');
        var typeBox = document.getElementById('optType');
        var typeInput = document.getElementById('variationType');
        var addBtn = document.getElementById('addVariationBtn');
        var index = 0;
        var OPTION_MAX = {{ \App\Support\ProductPhotos::MAX_PER_OPTION }};
        var say = window.bbAlert || window.alert;

        // The one variation type goes on every option (the form sends it per option).
        function syncType() {
            rowsContainer.querySelectorAll('.opt-type-field').forEach(function (i) { i.value = typeInput.value.trim(); });
        }
        typeInput.addEventListener('input', syncType);

        function renumber() {
            var cards = rowsContainer.querySelectorAll('.opt-card');
            cards.forEach(function (card, n) { card.querySelector('[data-opt-n]').textContent = n + 1; });
            typeBox.hidden = cards.length === 0;
            document.dispatchEvent(new Event('bb:options-changed'));
            markCover();
        }

        // Badge and "Set as cover" on the photos (shared with Photos for all options).
        function markCover() {
            document.dispatchEvent(new Event('bb:option-photos'));
        }

        function addRow() {
            var card = document.createElement('div');
            card.className = 'opt-card';
            card.dataset.row = index;

            card.innerHTML =
                '<input type="hidden" class="opt-type-field" name="variations[' + index + '][type]">' +
                '<div class="opt-fields">' +
                    '<label class="opt-field opt-name"><span>Option <b data-opt-n></b> name</span><input type="text" name="variations[' + index + '][value]" placeholder="e.g. Red, XL, Air Vent" required></label>' +
                    '<label class="opt-field"><span>Extra ₱ <small>· 0 = same</small></span><input type="number" step="0.01" name="variations[' + index + '][price_adjustment]" value="0"></label>' +
                    '<label class="opt-field"><span>Stock</span><input type="number" min="0" name="variations[' + index + '][stock]" value="0"></label>' +
                    '<button type="button" class="opt-remove" aria-label="Remove this option" title="Remove option"><i class="bi bi-x-lg"></i></button>' +
                '</div>' +
                '<div class="var-photos">' +
                    '<span class="var-photos-lbl">Photos for <b data-opt-name>this option</b> · <span data-opt-count>0</span>/' + OPTION_MAX + '</span>' +
                    '<div class="var-photos-list">' +
                        '<label class="var-photos-add"><input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple><i class="bi bi-camera-fill"></i><span>+ Photos</span></label>' +
                    '</div>' +
                    '<input type="file" class="var-photos-field" name="variations[' + index + '][photos][]" multiple hidden>' +
                '</div>';

            // This option's own photos (sent in the order shown).
            var files = [];
            var list = card.querySelector('.var-photos-list');
            var addTile = card.querySelector('.var-photos-add');
            var picker = addTile.querySelector('input');
            var field = card.querySelector('.var-photos-field');

            function syncPhotos() {
                var dt = new DataTransfer();
                files.forEach(function (f) { dt.items.add(f); });
                field.files = dt.files;

                list.querySelectorAll('.var-photo-tile').forEach(function (t) { t.remove(); });
                files.forEach(function (file, i) {
                    var tile = document.createElement('div');
                    tile.className = 'var-photo-tile';
                    tile.innerHTML = '<img alt="">' +
                        '<button type="button" class="photo-remove is-small" title="Remove" aria-label="Remove this photo"><i class="bi bi-x-lg"></i></button>';
                    window.bbCover.decorate(tile, file, 'opt:' + card.dataset.row + ':' + i);
                    tile.querySelector('img').src = URL.createObjectURL(file);
                    tile.querySelector('.photo-remove').addEventListener('click', function () { files.splice(i, 1); syncPhotos(); });
                    list.insertBefore(tile, addTile);
                });
                addTile.hidden = files.length >= OPTION_MAX;
                card.querySelector('[data-opt-count]').textContent = files.length;
                markCover();
            }

            picker.addEventListener('change', function () {
                var skipped = 0;
                Array.prototype.slice.call(picker.files || []).forEach(function (file) {
                    if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size > 5 * 1024 * 1024 || files.length >= OPTION_MAX) { skipped++; return; }
                    files.push(file);
                });
                picker.value = '';
                if (skipped) say(skipped + ' photo' + (skipped === 1 ? ' was' : 's were') + ' not added. Use JPG, PNG or WEBP up to 5 MB, up to ' + OPTION_MAX + ' per option.');
                syncPhotos();
            });

            // "Photos for Red" follows the option name.
            card.querySelector('input[name$="[value]"]').addEventListener('input', function (e) {
                card.querySelector('[data-opt-name]').textContent = e.target.value.trim() || 'this option';
            });

            card.querySelector('.opt-remove').addEventListener('click', function () {
                card.remove();
                renumber();
            });

            rowsContainer.appendChild(card);
            index++;
            syncType();
            renumber();
            card.querySelector('input[name$="[value]"]').focus();
        }

        addBtn.addEventListener('click', addRow);

        // Options need a type (Color, Size…) so buyers see "Choose a color".
        addBtn.form.addEventListener('submit', function (e) {
            if (rowsContainer.children.length && !typeInput.value.trim()) {
                e.preventDefault();
                say('Please fill in the variation type (for example Color, Size or Mount).');
                typeInput.focus();
            }
            syncType();
        });
    })();
</script>

    @include('partials.pwa-register')

</body>
</html>
