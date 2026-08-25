<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BoomBuy - Categories</title>

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
            max-width: 1300px;
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
            min-height: 40px;
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

        @media (max-width: 1000px) {
            .categories {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 750px) {
            .categories {
                grid-template-columns: repeat(2, 1fr);
            }

            .header {
                padding: 20px 30px;
            }
        }

        @media (max-width: 500px) {
            .categories {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 20px 25px;
            }

            .container {
                padding: 35px 20px;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="header">
        <h1>BoomBuy</h1>
        <p>Your marketplace for everything</p>
    </div>

    <div class="container">

        <!-- PAGE TITLE -->
        <div class="title">
            <h2>Shop by Category</h2>
            <p>Explore thousands of products from different categories.</p>
        </div>

        <!-- CATEGORIES -->
        <div class="categories">

            <!-- ELECTRONICS -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">📱</div>
                    <h3>Electronics & Gadgets</h3>
                    <p>Phones, laptops, cameras, audio and electronic accessories.</p>
                </a>
            </div>

            <!-- WOMEN -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">👗</div>
                    <h3>Women's Apparel</h3>
                    <p>Dresses, tops, activewear, shoes and fashion accessories.</p>
                </a>
            </div>

            <!-- MEN -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">👕</div>
                    <h3>Men's Apparel</h3>
                    <p>Shirts, pants, jackets, shoes, accessories and grooming.</p>
                </a>
            </div>

            <!-- KIDS -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🧸</div>
                    <h3>Kids & Baby</h3>
                    <p>Baby essentials, toys, clothes, games and nursery products.</p>
                </a>
            </div>

            <!-- HOME -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🏠</div>
                    <h3>Home & Garden</h3>
                    <p>Kitchen appliances, furniture, decor and gardening supplies.</p>
                </a>
            </div>

            <!-- SPORTS -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">⚽</div>
                    <h3>Sports & Outdoors</h3>
                    <p>Fitness equipment, camping gear, bikes and sports apparel.</p>
                </a>
            </div>

            <!-- BEAUTY -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">💄</div>
                    <h3>Health & Beauty</h3>
                    <p>Skincare, makeup, haircare, grooming and personal care.</p>
                </a>
            </div>

            <!-- BOOKS -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">📚</div>
                    <h3>Books & Media</h3>
                    <p>Books, magazines, music, movies, games and educational media.</p>
                </a>
            </div>

            <!-- FOOD -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🍔</div>
                    <h3>Food & Gourmet</h3>
                    <p>Snacks, beverages, baking supplies, specialty and organic foods.</p>
                </a>
            </div>

            <!-- AUTOMOTIVE -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🚗</div>
                    <h3>Automotive & Motorcycle</h3>
                    <p>Vehicle parts, accessories, tools, tires and protective gear.</p>
                </a>
            </div>

            <!-- FURNITURE -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">🪑</div>
                    <h3>Furniture & Office Equipment</h3>
                    <p>Desks, chairs, cabinets, workstations and office equipment.</p>
                </a>
            </div>

            <!-- JEWELRY -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">💎</div>
                    <h3>Jewelry & Watches</h3>
                    <p>Necklaces, rings, earrings, bracelets and watches.</p>
                </a>
            </div>

            <!-- OFFICE -->
            <div class="category">
                <a href="#" class="category-link">
                    <div class="icon">✏️</div>
                    <h3>Office & School Supplies</h3>
                    <p>Notebooks, pens, backpacks, printers and craft materials.</p>
                </a>
            </div>

        </div>

    </div>

</body>
</html>