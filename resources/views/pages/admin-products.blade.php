<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Products — GizmoMart</title>

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

        .admin-label {
            color: #64748b;
            font-size: 13px;
        }

        .back {
            color: #1769e0;
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
            color: #3977d5;
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
            color: #718096;
            font-size: 14px;
            margin-top: 8px;
        }

        .add-btn {
            background: #1769e0;
            color: white;
            border: none;
            padding: 13px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .add-btn:hover {
            background: #0f55bd;
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
            background: #fff0f0;
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
            border: 1px solid #e1e9f6;
            border-radius: 14px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search {
            flex: 1;
            border: 1px solid #dce7fa;
            background: #f7faff;
            padding: 11px 14px;
            border-radius: 8px;
            outline: none;
        }

        .search:focus {
            border-color: #1769e0;
            background: white;
        }

        .category-filter {
            border: 1px solid #dce7fa;
            background: white;
            padding: 10px 14px;
            border-radius: 8px;
            color: #52627a;
        }

        .table-box {
            background: white;
            border: 1px solid #e1e9f6;
            border-radius: 15px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f7faff;
            color: #64748b;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            padding: 15px;
            border-bottom: 1px solid #e5edfa;
        }

        td {
            padding: 16px 15px;
            border-bottom: 1px solid #edf1f7;
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
            background: #eaf2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .product-name {
            font-weight: 700;
        }

        .product-category {
            color: #8995a8;
            font-size: 11px;
            margin-top: 4px;
        }

        .price {
            color: #1769e0;
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
            background: #eaf2ff;
            color: #1769e0;
        }

        .delete {
            background: #fff0f0;
            color: #dc2626;
        }

        .edit:hover {
            background: #dceaff;
        }

        .delete:hover {
            background: #ffe0e0;
        }

        .empty {
            display: none;
            text-align: center;
            padding: 50px;
            color: #718096;
        }

        footer {
            background: white;
            border-top: 1px solid #e1e9f6;
            padding: 30px 7%;
            display: flex;
            justify-content: space-between;
            color: #718096;
            font-size: 13px;
        }

        footer div:first-child {
            color: #1769e0;
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

    </style>

</head>


<body>


<nav class="navbar">

    <a href="/admin" class="logo">
        Gizmo<span>Mart</span>
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
                View and manage all products in GizmoMart.
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

                    <th>
                        Product
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Price
                    </th>

                    <th>
                        Stock
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody id="productTable">


                @foreach($products as $product)

                <tr>


                    <td>

                        <div class="product">

                            <div class="product-icon">
                                {{ $product['icon'] }}
                            </div>


                            <div>

                                <div class="product-name">
                                    {{ $product['name'] }}
                                </div>

                                <div class="product-category">
                                  {{ $product['slug'] ?? \Illuminate\Support\Str::slug($product['name']) }}
                                </div>

                            </div>

                        </div>

                    </td>


                    <td>
                        {{ $product['category'] }}
                    </td>


                    <td class="price">

                        ₱{{ number_format($product['price']) }}

                    </td>


                    <td class="stock">
                        In Stock
                    </td>


                    <td>

                        <div class="actions">

                            <button
                                class="edit"
                                onclick="editProduct('{{ $product['name'] }}')"
                            >
                                Edit
                            </button>


                            <button
                                class="delete"
                                onclick="deleteProduct(this, '{{ $product['name'] }}')"
                            >
                                Delete
                            </button>

                        </div>

                    </td>


                </tr>

                @endforeach


            </tbody>

        </table>


        <div
            class="empty"
            id="empty"
        >
            No products found.
        </div>


    </div>


</main>


<footer>

    <div>
        © 2026 GizmoMart Admin
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


const empty =
    document.getElementById("empty");


function filterProducts() {

    const searchValue =
        search.value
            .toLowerCase()
            .trim();


    const category =
        categoryFilter.value;


    const rows =
        Array.from(
            document.querySelectorAll(
                "#productTable tr"
            )
        );


    let visible = 0;


    rows.forEach(row => {


        const nameElement =
            row.querySelector(
                ".product-name"
            );


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


    empty.style.display =
        visible === 0
            ? "block"
            : "none";

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

    const confirmed =
        confirm(
            "Delete " + name + "?"
        );


    if (confirmed) {

        const row =
            button.closest("tr");

        row.remove();

        filterProducts();

    }

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