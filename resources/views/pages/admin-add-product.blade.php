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
            background: #f4f8ff;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e1e9f6;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            color: #64748b;
            font-size: 13px;
        }

        .back:hover {
            color: #1769e0;
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
            color: #3977d5;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 10px;
            font-weight: 700;
        }

        .header h1 {
            font-size: 34px;
            margin-top: 8px;
        }

        .header p {
            color: #718096;
            font-size: 13px;
            margin-top: 8px;
        }

        .form-box {
            background: #ffffff;
            border: 1px solid #e1e9f6;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 12px 35px rgba(39, 84, 150, 0.07);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            color: #52627a;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #dce7fa;
            background: #f8fbff;
            border-radius: 9px;
            padding: 12px 13px;
            outline: none;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #172033;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #4b8df8;
            background: #ffffff;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .icon-input {
            display: flex;
            gap: 10px;
        }

        .icon-input input {
            flex: 1;
        }

        .preview {
            margin-top: 25px;
            padding: 20px;
            border-radius: 12px;
            background: #f6f9ff;
            border: 1px solid #e5edfa;
        }

        .preview-title {
            color: #718096;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .preview-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .preview-icon {
            width: 65px;
            height: 65px;
            background: #e8f2ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .preview-name {
            font-weight: 700;
            font-size: 15px;
        }

        .preview-price {
            color: #1769e0;
            font-weight: 700;
            font-size: 13px;
            margin-top: 5px;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
            padding-top: 22px;
            border-top: 1px solid #edf1f7;
        }

        .cancel {
            padding: 12px 20px;
            border-radius: 8px;
            color: #64748b;
            background: #f1f5f9;
            font-size: 13px;
            font-weight: 600;
        }

        .cancel:hover {
            background: #e5eaf1;
        }

        .save {
            border: none;
            padding: 12px 22px;
            border-radius: 8px;
            color: white;
            background: #1769e0;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .save:hover {
            background: #0f55bd;
        }

        footer {
            background: #ffffff;
            border-top: 1px solid #e1e9f6;
            padding: 30px 7%;
            display: flex;
            justify-content: space-between;
            color: #718096;
            font-size: 12px;
        }

        @media (max-width: 650px) {

            .navbar {
                padding: 16px 5%;
            }

            .container {
                width: 92%;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }

            .form-box {
                padding: 20px;
            }

            .actions {
                flex-direction: column;
            }

            .cancel,
            .save {
                text-align: center;
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

    <a href="/admin" class="logo">
        Gizmo<span>Mart</span>
    </a>

    <a href="/admin" class="back">
        ← Back to Dashboard
    </a>

</nav>


<div class="container">

    <div class="header">

        <small>
            Product Management
        </small>

        <h1>
            Add New Product
        </h1>

        <p>
            Add a new product to your GizmoMart store.
        </p>

    </div>


    <div class="form-box">

        <form action="{{ route('admin.products.store') }}" method="POST">

            @csrf

            <div class="form-grid">

                <div class="form-group">

                    <label>
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        placeholder="e.g. Nova X6 Pro"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Category
                    </label>

                    <select name="category" required>

                        <option value="">
                            Select category
                        </option>

                        <option value="smartphone">
                            Smartphone
                        </option>

                        <option value="laptop">
                            Laptop
                        </option>

                        <option value="audio">
                            Audio
                        </option>

                        <option value="wearable">
                            Wearable
                        </option>

                        <option value="accessories">
                            Accessories
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Price
                    </label>

                    <input
                        type="number"
                        name="price"
                        placeholder="e.g. 18999"
                        min="0"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Product Icon
                    </label>

                    <input
                        type="text"
                        name="icon"
                        placeholder="e.g. 📱"
                        maxlength="5"
                        required
                    >

                </div>


                <div class="form-group full">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        placeholder="Enter product description..."
                        required
                    ></textarea>

                </div>

            </div>


            <div class="preview">

                <div class="preview-title">
                    Product Preview
                </div>

                <div class="preview-content">

                    <div class="preview-icon" id="previewIcon">
                        📦
                    </div>

                    <div>

                        <div class="preview-name" id="previewName">
                            Product Name
                        </div>

                        <div class="preview-price" id="previewPrice">
                            ₱0
                        </div>

                    </div>

                </div>

            </div>


            <div class="actions">

                <a href="/admin" class="cancel">
                    Cancel
                </a>

                <button type="submit" class="save">
                    + Add Product
                </button>

            </div>

        </form>

    </div>

</div>


<footer>

    <div>
        © 2026 GizmoMart Admin
    </div>

    <div>
        Quality tech. Better everyday.
    </div>

</footer>


<script>

const nameInput =
    document.querySelector('input[name="name"]');

const priceInput =
    document.querySelector('input[name="price"]');

const iconInput =
    document.querySelector('input[name="icon"]');

const previewName =
    document.getElementById("previewName");

const previewPrice =
    document.getElementById("previewPrice");

const previewIcon =
    document.getElementById("previewIcon");


nameInput.addEventListener("input", function () {

    previewName.textContent =
        this.value || "Product Name";

});


priceInput.addEventListener("input", function () {

    const value = Number(this.value) || 0;

    previewPrice.textContent =
        "₱" + value.toLocaleString("en-PH");

});


iconInput.addEventListener("input", function () {

    previewIcon.textContent =
        this.value || "📦";

});

</script>

</body>
</html>