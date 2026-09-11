<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Products — BoomBuy</title>

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
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .navbar {
            background: white;
            border-bottom: 1px solid #ffe9e2;
            padding: 18px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 23px;
            font-weight: 700;
            color: #e8420f;
        }

        .logo span {
            color: #172033;
        }

        .admin-label {
            color: #8d6c62;
            font-size: 13px;
        }

        .back {
            color: #e8420f;
            font-size: 13px;
            font-weight: 600;
        }

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 45px auto 80px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header small {
            color: #db5a33;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
        }

        .header h1 {
            font-size: 38px;
            margin-top: 8px;
        }

        .header p {
            color: #977970;
            font-size: 14px;
            margin-top: 8px;
        }

        .add-btn {
            background: #e8420f;
            color: white;
            border: none;
            padding: 13px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .add-btn:hover {
            background: #c43408;
        }

        .success {
            background: #eaf8ef;
            color: #15803d;
            border: 1px solid #bbebca;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .error {
            background: #fff3f0;
            color: #dc2626;
            border: 1px solid #ffd0d0;
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .toolbar {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 14px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search {
            flex: 1;
            border: 1px solid #fbe2db;
            background: #fff9f7;
            padding: 11px 14px;
            border-radius: 8px;
            outline: none;
        }

        .search:focus {
            border-color: #e8420f;
            background: white;
        }

        .category-filter {
            border: 1px solid #fbe2db;
            background: white;
            padding: 10px 14px;
            border-radius: 8px;
            color: #7c5a50;
        }

        .table-box {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 15px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #fff9f7;
            color: #8d6c62;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            padding: 15px;
            border-bottom: 1px solid #fbe9e4;
        }

        td {
            padding: 16px 15px;
            border-bottom: 1px solid #f7efed;
            font-size: 13px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .product {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-icon {
            width: 45px;
            height: 45px;
            border-radius: 9px;
            background: #ffefea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-name {
            font-weight: 700;
        }

        .product-category {
            color: #a99088;
            font-size: 11px;
            margin-top: 4px;
        }

        .price {
            color: #e8420f;
            font-weight: 700;
        }

        .stock {
            color: #16a34a;
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 7px;
        }

        .edit,
        .delete {
            border: none;
            padding: 7px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }

        .edit {
            background: #ffefea;
            color: #e8420f;
        }

        .delete {
            background: #fff3f0;
            color: #dc2626;
        }

        .edit:hover {
            background: #ffe4dc;
        }

        .delete:hover {
            background: #ffe0e0;
        }

        .empty {
            text-align: center;
            padding: 50px;
            color: #977970;
        }

        footer {
            background: white;
            border-top: 1px solid #f7e5e0;
            padding: 30px 7%;
            display: flex;
            justify-content: space-between;
            color: #977970;
            font-size: 13px;
        }

        footer div:first-child {
            color: #e8420f;
            font-weight: 600;
        }

        @media (max-width: 800px) {

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .toolbar {
                flex-direction: column;
            }

            .table-box {
                overflow-x: auto;
            }

            table {
                min-width: 750px;
            }

        }

        @media (max-width: 550px) {

            .container {
                width: 92%;
            }

            .header h1 {
                font-size: 30px;
            }

            footer {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

        }

    
/* ===== BoomBuy Vibrant Design System Overrides ===== */
h1, h2, h3, .logo, .hero-title, .hero h1, .section-title, .page-title,
.product-title, .price, .cta, .cta-title, .brand, .checkout-title,
.card-title, .modal-title, .auth-title, .form-title, .empty-title,
.step-title, .order-title, .stat-title, .stat-value, .banner-title {
    font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
    letter-spacing: -0.01em;
}
button, .btn, [class*="btn-"], .add-to-cart, .buy-now, .checkout-btn,
.register-btn, .login-btn, .submit-btn, .primary-btn {
    border-radius: 12px !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
button:hover, .btn:hover, [class*="btn-"]:hover, .add-to-cart:hover,
.buy-now:hover, .primary-btn:hover {
    transform: translateY(-1px);
}
.card, [class*="-card"], .product-card {
    border-radius: 16px !important;
}
::selection {
    background: #ffd7c2;
    color: #7c1a00;
}
</style>

</head>


<body>


<nav class="navbar">

    <a href="/admin" class="logo">
        Boom<span>Buy</span>
    </a>

    <div class="admin-label">
        Admin Panel
    </div>

    <a href="/admin" class="back">
        ← Dashboard
    </a>

</nav>


<main class="container">


    <div class="header">

        <div>

            <small>
                Administration
            </small>

            <h1>
                Manage Products
            </h1>

            <p>
                View and manage all products in BoomBuy.
            </p>

        </div>


        <a
            href="{{ route('admin.products.create') }}"
            class="add-btn"
        >
            + Add Product
        </a>

    </div>


    @if(session('success'))

        <div class="success">
            ✓ {{ session('success') }}
        </div>

    @endif


    @if(session('error'))

        <div class="error">
            ✕ {{ session('error') }}
        </div>

    @endif


    <div class="toolbar">

        <input
            type="text"
            id="search"
            class="search"
            placeholder="Search products..."
        >


        <select
            id="categoryFilter"
            class="category-filter"
        >

            <option value="all">
                All Categories
            </option>

            <option value="Smartphone">
                Smartphones
            </option>

            <option value="Laptop">
                Laptops
            </option>

            <option value="Audio">
                Audio
            </option>

            <option value="Wearable">
                Wearables
            </option>

            <option value="Accessories">
                Accessories
            </option>

        </select>

    </div>


    <div class="table-box">

        <table>

            <thead>

                <tr>

                    <th>Product</th>

                    <th>Category</th>

                    <th>Price</th>

                    <th>Stock</th>

                    <th>Actions</th>

                </tr>

            </thead>


            <tbody id="productTable">

                @forelse($products as $product)

                    <tr>

                        <td>

                            <div class="product">

                                <div class="product-icon">
                                    @php
                                        $pIcon = $product['icon'] ?? '📦';
                                        $pIsImg = is_string($pIcon) && (str_contains($pIcon, '.jpg') || str_contains($pIcon, '.jpeg') || str_contains($pIcon, '.png') || str_contains($pIcon, '.webp') || str_contains($pIcon, '/'));
                                    @endphp
                                    @if($pIsImg)
                                        <img src="{{ str_starts_with($pIcon, 'http') ? $pIcon : asset('storage/' . ltrim($pIcon, '/')) }}" alt="{{ $product['name'] ?? 'Product' }}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
                                    @else
                                        {{ $pIcon }}
                                    @endif
                                </div>


                                <div>

                                    <div class="product-name">
                                        {{ $product['name'] ?? 'Unnamed Product' }}
                                    </div>

                                    <div class="product-category">

                                        {{ $product['slug'] ?? \Illuminate\Support\Str::slug($product['name'] ?? 'product') }}

                                    </div>

                                </div>

                            </div>

                        </td>


                        <td>
                            {{ $product['category'] ?? 'Other' }}
                        </td>


                        <td class="price">

                            ₱{{ number_format((float)($product['price'] ?? 0), 2) }}

                        </td>


                        <td class="stock">
                            In Stock
                        </td>


                        <td>

                            <div class="actions">

                                <button
                                    type="button"
                                    class="edit"
                                    onclick="editProduct('{{ addslashes($product['name'] ?? 'Product') }}')"
                                >
                                    Edit
                                </button>


                                <button
                                    type="button"
                                    class="delete"
                                    onclick="deleteProduct(this, '{{ addslashes($product['name'] ?? 'Product') }}')"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5">

                            <div class="empty">

                                📦

                                <br><br>

                                No products found.

                                <br><br>

                                Click <strong>+ Add Product</strong> to add one.

                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


</main>


<footer>

    <div>
        © 2026 BoomBuy Admin
    </div>

    <div>
        Product Management
    </div>

</footer>


<script>

const search =
    document.getElementById("search");

const categoryFilter =
    document.getElementById("categoryFilter");


function filterProducts() {

    const searchValue =
        search.value.toLowerCase().trim();

    const category =
        categoryFilter.value;

    const rows =
        document.querySelectorAll(
            "#productTable tr"
        );

    let visible = 0;

    rows.forEach(row => {

        const nameElement =
            row.querySelector(".product-name");

        const categoryElement =
            row.children[1];

        if (!nameElement || !categoryElement) {
            return;
        }

        const name =
            nameElement.textContent
                .toLowerCase();

        const rowCategory =
            categoryElement.textContent
                .trim();

        const matchesSearch =
            name.includes(searchValue);

        const matchesCategory =
            category === "all" ||
            rowCategory === category;

        if (
            matchesSearch &&
            matchesCategory
        ) {

            row.style.display = "";

            visible++;

        } else {

            row.style.display = "none";

        }

    });

}


search.addEventListener(
    "input",
    filterProducts
);


categoryFilter.addEventListener(
    "change",
    filterProducts
);


function deleteProduct(button, name) {

    alert(
        "Delete feature for " +
        name +
        " will be connected next."
    );

}


function editProduct(name) {

    alert(
        "Edit feature for " +
        name +
        " will be connected next."
    );

}

</script>


</body>

</html>