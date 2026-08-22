<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product — GizmoMart</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

       .navbar {
    height: 70px;
    background: #2563eb;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 50px;
}
        

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .logo span {
            color: #3b82f6;
        }

        .back-btn {
            text-decoration: none;
            color: white;
            background: #374151;
            padding: 10px 18px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .back-btn:hover {
            background: #4b5563;
        }

        .container {
            max-width: 900px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .page-title p {
            color: #6b7280;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.07);
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: 0.2s;
            font-family: inherit;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .image-preview {
            margin-top: 12px;
            width: 150px;
            height: 150px;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            display: none;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            border: none;
            padding: 13px 25px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }

        .cancel-btn {
            background: #e5e7eb;
            color: #374151;
        }

        .cancel-btn:hover {
            background: #d1d5db;
        }

        .save-btn {
            background: #2563eb;
            color: white;
        }

        .save-btn:hover {
            background: #1d4ed8;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error ul {
            margin-left: 20px;
            margin-top: 8px;
        }

        @media (max-width: 700px) {
            .navbar {
                padding: 0 20px;
            }

            .container {
                margin-top: 30px;
            }

            .form-card {
                padding: 25px 20px;
            }

            .row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="logo">
            Gizmo<span>Mart</span>
        </div>

        <a href="{{ url('/admin/products') }}" class="back-btn">
            ← Back to Products
        </a>
    </nav>

    <main class="container">

        <div class="page-title">
            <h1>Add New Product</h1>
            <p>Add a new product to your GizmoMart store.</p>
        </div>

        @if(session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="error">
                <strong>Please fix the following:</strong>

                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-card">

            <form action="{{ url('/admin/products/store') }}" method="POST" enctype="multipart/form-data">

                @csrf

                <div class="form-group">
                    <label for="name">Product Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Example: Nova X5 Pro"
                        value="{{ old('name') }}"
                        required
                    >
                </div>

                <div class="row">

                    <div class="form-group">
                        <label for="category">Category</label>

                        <select id="category" name="category" required>
                            <option value="">Select Category</option>

                            <option value="Smartphones"
                                {{ old('category') == 'Smartphones' ? 'selected' : '' }}>
                                Smartphones
                            </option>

                            <option value="Laptops"
                                {{ old('category') == 'Laptops' ? 'selected' : '' }}>
                                Laptops
                            </option>

                            <option value="Audio"
                                {{ old('category') == 'Audio' ? 'selected' : '' }}>
                                Audio
                            </option>

                            <option value="Wearables"
                                {{ old('category') == 'Wearables' ? 'selected' : '' }}>
                                Wearables
                            </option>

                            <option value="Accessories"
                                {{ old('category') == 'Accessories' ? 'selected' : '' }}>
                                Accessories
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price">Price (₱)</label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            placeholder="18999"
                            min="0"
                            step="0.01"
                            value="{{ old('price') }}"
                            required
                        >
                    </div>

                </div>

                <div class="row">

                    <div class="form-group">
                        <label for="stock">Stock</label>

                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            placeholder="50"
                            min="0"
                            value="{{ old('stock') }}"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>

                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept="image/*"
                            onchange="previewImage(event)"
                        >

                        <div class="image-preview" id="imagePreview">
                            <img id="preview" src="" alt="Image Preview">
                        </div>
                    </div>

                </div>

                <div class="form-group">
                    <label for="description">Product Description</label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Enter product description..."
                        required
                    >{{ old('description') }}</textarea>
                </div>

                <div class="buttons">

                    <a href="{{ url('/admin/products') }}" class="btn cancel-btn">
                        Cancel
                    </a>

                    <button type="submit" class="btn save-btn">
                        + Add Product
                    </button>

                </div>

            </form>

        </div>

    </main>

    <script>
        function previewImage(event) {

            const input = event.target;
            const previewBox = document.getElementById('imagePreview');
            const preview = document.getElementById('preview');

            if (input.files && input.files[0]) {

                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewBox.style.display = 'flex';
                };

                reader.readAsDataURL(input.files[0]);

            } else {

                preview.src = '';
                previewBox.style.display = 'none';

            }
        }
    </script>

</body>
</html>