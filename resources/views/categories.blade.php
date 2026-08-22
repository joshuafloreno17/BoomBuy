<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GizmoMart - Categories</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #111827;
        }

        .header {
            background: #111827;
            color: white;
            padding: 20px 60px;
        }

        .header h1 {
            font-size: 28px;
        }

        .header p {
            margin-top: 5px;
            color: #d1d5db;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 50px 30px;
        }

        .title {
            text-align: center;
            margin-bottom: 40px;
        }

        .title h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .title p {
            color: #6b7280;
        }

        .categories {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
        }

        .category {
            background: white;
            padding: 30px 20px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.08);
            transition: 0.3s;
        }

        .category:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .category-link {
            display: block;
            text-decoration: none;
            color: #111827;
            cursor: pointer;
        }

        .icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .category h3 {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .category p {
            font-size: 14px;
            color: #6b7280;
        }

        .category button {
            margin-top: 15px;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-size: 14px;
            cursor: pointer;
        }

        .category button:hover {
            background: #1d4ed8;
        }

        @media (max-width: 900px) {
            .categories {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {
            .categories {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 20px 25px;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>GizmoMart</h1>
        <p>Your trusted online gadget store</p>
    </div>

    <div class="container">

        <!-- PAGE TITLE -->
        <div class="title">
            <h2>Shop by Category</h2>
            <p>Find the gadgets and technology you need.</p>
        </div>

        <!-- CATEGORIES -->
        <div class="categories">

            <!-- SMARTPHONES -->
            <div class="category">
                <div class="icon">📱</div>
                <h3>Smartphones</h3>
                <p>Latest smartphones and mobile devices.</p>

                <button onclick="window.location.href='/smartphones'">
                    View Smartphones
                </button>
            </div>

            <!-- LAPTOPS -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">💻</div>
                    <h3>Laptops</h3>
                    <p>Laptops for school, work, and gaming.</p>
                </a>
            </div>

            <!-- MONITORS -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🖥️</div>
                    <h3>Monitors</h3>
                    <p>High-quality displays for work and entertainment.</p>
                </a>
            </div>

            <!-- GAMING -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🎮</div>
                    <h3>Gaming</h3>
                    <p>Gaming gadgets and accessories.</p>
                </a>
            </div>

            <!-- KEYBOARDS -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">⌨️</div>
                    <h3>Keyboards</h3>
                    <p>Mechanical and wireless keyboards.</p>
                </a>
            </div>

            <!-- MOUSE -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🖱️</div>
                    <h3>Mouse</h3>
                    <p>Comfortable and responsive computer mice.</p>
                </a>
            </div>

            <!-- AUDIO -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🎧</div>
                    <h3>Audio</h3>
                    <p>Headphones, earphones, and speakers.</p>
                </a>
            </div>

            <!-- CAMERAS -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">📷</div>
                    <h3>Cameras</h3>
                    <p>Cameras and photography equipment.</p>
                </a>
            </div>

            <!-- CHARGERS -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🔌</div>
                    <h3>Chargers</h3>
                    <p>Chargers, cables, and power accessories.</p>
                </a>
            </div>

            <!-- STORAGE -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">💾</div>
                    <h3>Storage</h3>
                    <p>SSD, HDD, memory cards, and USB drives.</p>
                </a>
            </div>

            <!-- PRINTERS -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🖨️</div>
                    <h3>Printers</h3>
                    <p>Printers and printing accessories.</p>
                </a>
            </div>

            <!-- ACCESSORIES -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">📱</div>
                    <h3>Accessories</h3>
                    <p>Cases, screen protectors, stands, and more.</p>
                </a>
            </div>

        </div>

    </div>

</body>
</html>