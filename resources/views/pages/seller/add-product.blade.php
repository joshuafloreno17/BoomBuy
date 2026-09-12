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

        /* =========================
           SIDEBAR
        ========================= */

        .layout {
            display: flex;
            min-height: 100vh;
        }

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

        .menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 8px;
        }

        .menu a {
            display: block;
            padding: 12px;
            border-radius: 10px;
            color: #8d6c62;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #fff4f1;
            color: #e8420f;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid #ffe9e2;
        }

        .sidebar-footer a {
            display: block;
            padding: 12px;
            border-radius: 10px;
            color: #8d6c62;
            font-size: 13px;
            font-weight: 600;
        }

        .sidebar-footer a:hover {
            background: #fff4f1;
            color: #e8420f;
        }

        /* =========================
           MAIN
        ========================= */

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
            font-family: 'Baloo 2', sans-serif;
            font-size: 36px;
            font-weight: 800;
            margin-top: 8px;
            letter-spacing: -0.01em;
        }

        .header p {
            color: #977970;
            font-size: 14px;
            margin-top: 8px;
        }

        /* =========================
           ALERTS
        ========================= */

        .error {
            background: #fff3f0;
            color: #dc2626;
            border: 1px solid #ffd0d0;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .success {
            background: #effdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        /* =========================
           FORM
        ========================= */

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
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px;
            color: #172033;
            transition: 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #e8420f;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(232, 66, 15, 0.08);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* =========================
           IMAGE UPLOAD
        ========================= */

        .image-upload {
            border: 1.5px dashed #f3cfc4;
            background: #fffaf8;
            border-radius: 12px;
            padding: 22px;
            text-align: center;
            transition: 0.2s;
        }

        .image-upload:hover {
            border-color: #e8420f;
            background: #fff7f4;
        }

        .image-upload-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .image-upload-title {
            font-size: 13px;
            font-weight: 700;
            color: #563a32;
            margin-bottom: 5px;
        }

        .image-upload-text {
            font-size: 11px;
            color: #a0847b;
            margin-bottom: 14px;
        }

        .file-input {
            background: #ffffff;
            cursor: pointer;
            padding: 10px;
        }

        .image-preview {
            display: none;
            margin-top: 18px;
        }

        .image-preview img {
            width: 160px;
            height: 160px;
            object-fit: cover;
            border-radius: 14px;
            border: 1px solid #f3ddd6;
            box-shadow: 0 8px 20px rgba(39, 84, 150, 0.08);
        }

        .preview-label {
            margin-top: 8px;
            font-size: 11px;
            color: #8d6c62;
        }

        /* =========================
           BUTTONS
        ========================= */

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
            border-radius: 12px;
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
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
        }

        .cancel:hover {
            background: #f1e5e1;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 900px) {

            .sidebar {
                width: 72px;
                padding: 20px 8px;
            }

            .sidebar .label-text,
            .sidebar-label {
                display: none;
            }

            .menu a {
                text-align: center;
            }

            .main-content {
                margin-left: 72px;
                width: calc(100% - 72px);
            }
        }

        @media (max-width: 600px) {

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

            .image-preview img {
                width: 130px;
                height: 130px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
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

            <a href="{{ route('seller.products.create') }}" class="active">
                ➕ <span class="label-text">Add Product</span>
            </a>

            <a href="{{ route('seller.orders') }}">
                🛒 <span class="label-text">Orders</span>
            </a>

        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('seller.dashboard') }}">
                ← <span class="label-text">Back to Dashboard</span>
            </a>
        </div>

    </aside>


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
                    ✕ {{ session('error') }}
                </div>

            @endif


            <!-- SUCCESS -->
            @if(session('success'))

                <div class="success">
                    ✓ {{ session('success') }}
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

        <option value="electronics"
            {{ old('category') == 'electronics' ? 'selected' : '' }}>
            📱 Electronics
        </option>

        <option value="womens-fashion"
            {{ old('category') == 'womens-fashion' ? 'selected' : '' }}>
            👗 Women's Fashion
        </option>

        <option value="mens-fashion"
            {{ old('category') == 'mens-fashion' ? 'selected' : '' }}>
            👕 Men's Fashion
        </option>

        <option value="kids-baby"
            {{ old('category') == 'kids-baby' ? 'selected' : '' }}>
            👶 Kids & Baby
        </option>

        <option value="home-living"
            {{ old('category') == 'home-living' ? 'selected' : '' }}>
            🏠 Home & Living
        </option>

        <option value="sports-outdoors"
            {{ old('category') == 'sports-outdoors' ? 'selected' : '' }}>
            ⚽ Sports & Outdoors
        </option>

        <option value="beauty-personal-care"
            {{ old('category') == 'beauty-personal-care' ? 'selected' : '' }}>
            💄 Beauty & Personal Care
        </option>

        <option value="food-beverages"
            {{ old('category') == 'food-beverages' ? 'selected' : '' }}>
            🍔 Food & Beverages
        </option>

        <option value="automotive"
            {{ old('category') == 'automotive' ? 'selected' : '' }}>
            🚗 Automotive
        </option>

        <option value="office-school"
            {{ old('category') == 'office-school' ? 'selected' : '' }}>
            📚 Office & School
        </option>

        <option value="pet-supplies"
            {{ old('category') == 'pet-supplies' ? 'selected' : '' }}>
            🐶 Pet Supplies
        </option>

        <option value="toys-games-hobbies"
            {{ old('category') == 'toys-games-hobbies' ? 'selected' : '' }}>
            🎮 Toys, Games & Hobbies
        </option>

        <option value="jewelry-accessories"
            {{ old('category') == 'jewelry-accessories' ? 'selected' : '' }}>
            💍 Jewelry & Accessories
        </option>

        <option value="shoes"
            {{ old('category') == 'shoes' ? 'selected' : '' }}>
            👟 Shoes
        </option>

        <option value="tools-home-improvement"
            {{ old('category') == 'tools-home-improvement' ? 'selected' : '' }}>
            🧰 Tools & Home Improvement
        </option>

        <option value="garden-outdoor"
            {{ old('category') == 'garden-outdoor' ? 'selected' : '' }}>
            🌱 Garden & Outdoor
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

                        <div class="image-upload">

                            <div class="image-upload-icon">
                                📷
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


                    <!-- BUTTONS -->
                    <div class="buttons">

                        <button
                            type="submit"
                            class="save"
                        >
                            ✓ Add Product
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


<!-- IMAGE PREVIEW SCRIPT -->
<script>

    const imageInput = document.getElementById('image');
    const previewBox = document.getElementById('image-preview');
    const previewImage = document.getElementById('preview-image');

    imageInput.addEventListener('change', function () {

        const file = this.files[0];

        if (!file) {

            previewBox.style.display = 'none';
            previewImage.src = '';

            return;
        }

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!allowedTypes.includes(file.type)) {

            alert(
                'Please select a JPG, JPEG, PNG, or WEBP image.'
            );

            this.value = '';

            previewBox.style.display = 'none';
            previewImage.src = '';

            return;
        }

        if (file.size > 5 * 1024 * 1024) {

            alert(
                'Product image must not be larger than 5MB.'
            );

            this.value = '';

            previewBox.style.display = 'none';
            previewImage.src = '';

            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {

            previewImage.src = event.target.result;

            previewBox.style.display = 'block';

        };

        reader.readAsDataURL(file);

    });

</script>

</body>
</html>