
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product — BoomBuy</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8ff;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .navbar {
            background: white;
            border-bottom: 1px solid #e2eaff;
            padding: 18px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #1769e0;
        }

        .logo span {
            color: #172033;
        }

        .back {
            color: #1769e0;
            font-size: 13px;
            font-weight: 700;
        }

        .container {
            width: 86%;
            max-width: 850px;
            margin: 45px auto 80px;
        }

        .card {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 16px;
            padding: 32px;
        }

        .header {
            margin-bottom: 28px;
        }

        .header small {
            color: #3977d5;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
        }

        .header h1 {
            font-size: 30px;
            margin-top: 8px;
        }

        .header p {
            color: #718096;
            font-size: 13px;
            margin-top: 7px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
            color: #334155;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #d9e2f0;
            border-radius: 8px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #1769e0;
            box-shadow: 0 0 0 3px rgba(23, 105, 224, 0.08);
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

        .current-product {
            background: #f8faff;
            border: 1px solid #e1e9f6;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 25px;
            color: #64748b;
            font-size: 12px;
        }

        .current-product strong {
            color: #172033;
        }

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
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .cancel-btn {
            background: #eef2f7;
            color: #475569;
        }

        .cancel-btn:hover {
            background: #e2e8f0;
        }

        .save-btn {
            background: #1769e0;
            color: white;
        }

        .save-btn:hover {
            background: #0f55bd;
        }

        .error {
            background: #fff0f0;
            color: #dc2626;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
        }

        footer {
            background: white;
            border-top: 1px solid #e1e9f6;
            padding: 30px 7%;
            display: flex;
            justify-content: space-between;
            color: #718096;
            font-size: 12px;
        }

        footer strong {
            color: #1769e0;
        }

        @media (max-width: 600px) {
            .container {
                width: 92%;
            }

            .navbar {
                padding: 15px 4%;
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

<nav class="navbar">

    <a href="{{ route('seller.dashboard') }}" class="logo">
        Boom<span>Buy</span>
    </a>

    <a href="{{ route('seller.dashboard') }}" class="back">
        ← Back to Dashboard
    </a>

</nav>


<main class="container">

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


        @if($errors->any())

            <div class="error">

                @foreach($errors->all() as $error)

                    <div>• {{ $error }}</div>

                @endforeach

            </div>

        @endif


        <div class="current-product">

            Editing product:
            <strong>{{ $product['name'] ?? 'Unnamed Product' }}</strong>

            @if(isset($product['slug']))
                — {{ $product['slug'] }}
            @endif

        </div>


        <form
            action="{{ route('seller.products.update', $product['slug']) }}"
            method="POST"
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
                    value="{{ old('name', $product['name'] ?? '') }}"
                    required
                >

            </div>


            <div class="row">

                <div class="form-group">

                    <label for="category">
                        Category
                    </label>

                    <select
                        id="category"
                        name="category"
                        required
                    >

                        @php
                            $currentCategory = old(
                                'category',
                                $product['category'] ?? ''
                            );
                        @endphp

                        <option value="">Select Category</option>

                        <option value="Electronics"
                            {{ $currentCategory == 'Electronics' ? 'selected' : '' }}>
                            Electronics
                        </option>

                        <option value="Laptop"
                            {{ $currentCategory == 'Laptop' ? 'selected' : '' }}>
                            Laptop
                        </option>

                        <option value="Smartphone"
                            {{ $currentCategory == 'Smartphone' ? 'selected' : '' }}>
                            Smartphone
                        </option>

                        <option value="Fashion"
                            {{ $currentCategory == 'Fashion' ? 'selected' : '' }}>
                            Fashion
                        </option>

                        <option value="Home"
                            {{ $currentCategory == 'Home' ? 'selected' : '' }}>
                            Home
                        </option>

                        <option value="Beauty"
                            {{ $currentCategory == 'Beauty' ? 'selected' : '' }}>
                            Beauty
                        </option>

                        <option value="Sports"
                            {{ $currentCategory == 'Sports' ? 'selected' : '' }}>
                            Sports
                        </option>

                        <option value="Other"
                            {{ $currentCategory == 'Other' ? 'selected' : '' }}>
                            Other
                        </option>

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
                        step="0.01"
                        min="0"
                        value="{{ old('price', $product['price'] ?? '') }}"
                        required
                    >

                </div>

            </div>


            <div class="row">

                <div class="form-group">

                    <label for="icon">
                        Product Icon
                    </label>

                    <input
                        type="text"
                        id="icon"
                        name="icon"
                        value="{{ old('icon', $product['icon'] ?? '📦') }}"
                        placeholder="Example: 💻"
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
                        value="{{ old('stock', $product['stock'] ?? 0) }}"
                    >

                </div>

            </div>


            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Enter product description..."
                >{{ old('description', $product['description'] ?? '') }}</textarea>

            </div>


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

</main>


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

