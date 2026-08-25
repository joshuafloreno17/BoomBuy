<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product — BoomBuy Seller</title>

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
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* NAVBAR */

        .navbar {
            width: 100%;
            background: #ffffff;
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

        .back:hover {
            color: #0f55bd;
        }

        /* CONTAINER */

        .container {
            width: 86%;
            max-width: 850px;
            margin: 45px auto 80px;
        }

        .header {
            margin-bottom: 25px;
        }

        .header small {
            color: #3977d5;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
        }

        .header h1 {
            font-size: 36px;
            margin-top: 8px;
        }

        .header p {
            color: #718096;
            font-size: 14px;
            margin-top: 8px;
        }

        /* ERROR */

        .error {
            background: #fff0f0;
            color: #dc2626;
            border: 1px solid #ffd0d0;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        /* SUCCESS */

        .success {
            background: #effdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        /* FORM */

        .form-box {
            background: #ffffff;
            border: 1px solid #e1e9f6;
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
            color: #334155;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #dce7fa;
            background: #f8faff;
            padding: 12px 14px;
            border-radius: 8px;
            outline: none;

            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #172033;

            transition: 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #1769e0;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(23, 105, 224, 0.08);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* BUTTONS */

        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .save {
            border: none;
            background: #1769e0;
            color: #ffffff;

            padding: 13px 22px;
            border-radius: 8px;

            font-size: 13px;
            font-weight: 700;

            cursor: pointer;
            transition: 0.2s;
        }

        .save:hover {
            background: #0f55bd;
            transform: translateY(-1px);
        }

        .cancel {
            background: #edf2f9;
            color: #52627a;

            padding: 13px 22px;
            border-radius: 8px;

            font-size: 13px;
            font-weight: 700;
        }

        .cancel:hover {
            background: #e2e8f0;
        }

        /* MOBILE */

        @media (max-width: 600px) {

            .navbar {
                padding: 16px 5%;
            }

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

        }

    </style>

</head>


<body>


<!-- NAVBAR -->

<nav class="navbar">

    <a href="/" class="logo">
        Boom<span>Buy</span>
    </a>

    <a href="/seller" class="back">
        ← Seller Dashboard
    </a>

</nav>


<!-- MAIN -->

<main class="container">


    <div class="header">

        <small>
            Seller
        </small>

        <h1>
            Add Product
        </h1>

        <p>
            Add a new product to your BoomBuy store.
        </p>

    </div>


    <!-- ERROR MESSAGE -->

    @if(session('error'))

        <div class="error">
            ✕ {{ session('error') }}
        </div>

    @endif


    <!-- SUCCESS MESSAGE -->

    @if(session('success'))

        <div class="success">
            ✓ {{ session('success') }}
        </div>

    @endif


    <!-- FORM -->

    <div class="form-box">

        <form
            action="/seller/products/store"
            method="POST"
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

                    <option
                        value="smartphone"
                        {{ old('category') == 'smartphone' ? 'selected' : '' }}
                    >
                        Smartphone
                    </option>

                    <option
                        value="laptop"
                        {{ old('category') == 'laptop' ? 'selected' : '' }}
                    >
                        Laptop
                    </option>

                    <option
                        value="audio"
                        {{ old('category') == 'audio' ? 'selected' : '' }}
                    >
                        Audio
                    </option>

                    <option
                        value="wearable"
                        {{ old('category') == 'wearable' ? 'selected' : '' }}
                    >
                        Wearable
                    </option>

                    <option
                        value="accessories"
                        {{ old('category') == 'accessories' ? 'selected' : '' }}
                    >
                        Accessories
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


            <!-- ICON -->

            <div class="form-group">

                <label for="icon">
                    Product Icon
                </label>

                <input
                    type="text"
                    id="icon"
                    name="icon"
                    value="{{ old('icon') }}"
                    placeholder="Example: 🎧"
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


            <!-- BUTTONS -->

            <div class="buttons">

                <button
                    type="submit"
                    class="save"
                >
                    ✓ Add Product
                </button>

                <a
                    href="/seller"
                    class="cancel"
                >
                    Cancel
                </a>

            </div>


        </form>

    </div>


</main>


</body>

</html>