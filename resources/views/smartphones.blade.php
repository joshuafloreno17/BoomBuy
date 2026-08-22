<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GizmoMart - Smartphones</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
        }

        .header {
            background: #111827;
            color: white;
            padding: 25px 60px;
        }

        .header h1 {
            margin-bottom: 5px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 45px 30px;
        }

        .back {
            display: inline-block;
            margin-bottom: 25px;
            text-decoration: none;
            color: #2563eb;
            font-weight: bold;
        }

        h2 {
            margin-bottom: 30px;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .product {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 18px rgba(0,0,0,0.08);
        }

        .product-icon {
            font-size: 70px;
            margin-bottom: 15px;
        }

        .product h3 {
            margin-bottom: 10px;
        }

        .price {
            color: #2563eb;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
        }

        @media (max-width: 800px) {
            .products {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>GizmoMart</h1>
        <p>Smartphones</p>
    </div>

    <div class="container">

        <a href="/categories" class="back">← Back to Categories</a>

        <h2>Smartphones</h2>

        <div class="products">

            <div class="product">
                <div class="product-icon">📱</div>
                <h3>GizmoPhone X</h3>
                <div class="price">₱15,999</div>
                <a href="#" class="btn">View Product</a>
            </div>

            <div class="product">
                <div class="product-icon">📱</div>
                <h3>GizmoPhone Pro</h3>
                <div class="price">₱22,999</div>
                <a href="#" class="btn">View Product</a>
            </div>

            <div class="product">
                <div class="product-icon">📱</div>
                <h3>GizmoPhone Lite</h3>
                <div class="price">₱9,999</div>
                <a href="#" class="btn">View Product</a>
            </div>

        </div>

    </div>

</body>
</html>