<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Wishlist — BoomBuy</title>

    @include('partials.pwa-head')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fff7f4;
            color: #172033;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
        }

        .container {
            width: calc(100% - 40px);
            max-width: 1200px;

            margin: 45px auto 80px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 30px;
        }

        .page-header p {
            color: #977970;
            font-size: 13px;
            margin-top: 6px;
        }

        .success-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .products {
            width: 100%;

            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .product-card {
            min-width: 0;

            background: white;

            border: 1px solid #f7e5e0;
            border-radius: 14px;

            padding: 18px;

            transition: 0.2s;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(232, 66, 15, 0.08);
        }

        .product-icon {
            position: relative;

            width: 100%;
            height: 130px;

            border-radius: 10px;

            background: #ffefea;

            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 55px;

            margin-bottom: 15px;
        }

        .product-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-wishlist {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 2;

            width: 30px;
            height: 30px;

            border: none;
            border-radius: 50%;

            background: rgba(255, 255, 255, 0.9);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 14px;

            cursor: pointer;

            box-shadow: 0 4px 10px rgba(72, 45, 35, 0.12);

            transition: 0.2s ease;
        }

        .remove-wishlist:hover {
            background: #fff;
            transform: scale(1.08);
        }

        .remove-wishlist svg {
            width: 15px;
            height: 15px;
        }

        .category {
            color: #9a7b72;
            font-size: 10px;
            font-weight: 600;

            margin-bottom: 4px;
        }

        .product-name {
            color: #2e211d;
            font-size: 14px;
            font-weight: 700;

            margin-bottom: 8px;

            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .price {
            color: #e8420f;
            font-size: 15px;
            font-weight: 800;

            margin-bottom: 12px;
        }

        .view-btn {
            display: block;

            width: 100%;
            text-align: center;

            background: #e8420f;
            color: white;

            padding: 10px;
            border-radius: 9px;

            font-size: 12px;
            font-weight: 700;

            transition: 0.2s;
        }

        .view-btn:hover {
            background: #c43408;
        }

        .empty-state {
            background: white;
            border: 1px solid #f7e5e0;
            border-radius: 16px;

            padding: 60px 30px;

            text-align: center;
        }

        .empty-icon {
            margin-bottom: 14px;
        }

        .empty-icon svg {
            width: 48px;
            height: 48px;
        }

        .empty-state h2 {
            font-family: 'Baloo 2', sans-serif;
            font-size: 20px;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #977970;
            font-size: 13px;
            margin-bottom: 22px;
        }

        .shop-btn {
            display: inline-block;

            background: #e8420f;
            color: white;

            padding: 12px 22px;
            border-radius: 10px;

            font-size: 13px;
            font-weight: 700;

            transition: 0.2s;
        }

        .shop-btn:hover {
            background: #c43408;
        }

        @media (max-width: 1100px) {
            .products {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .products {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .container {
                width: calc(100% - 24px);
                margin-top: 25px;
            }
        }

        @media (max-width: 450px) {
            .products {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'wishlist'])

    <div class="container">

        <div class="page-header">
            <h1>My Wishlist</h1>
            <p>Products you've saved for later.</p>
        </div>

        @if (session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif

        @if ($products->count() > 0)

            <div class="products">

                @foreach($products as $product)

                    <div class="product-card" id="wishlist-card-{{ $product->id }}">

                        <div class="product-icon">

                            <button
                                type="button"
                                class="remove-wishlist"
                                data-product-id="{{ $product->id }}"
                                aria-label="Remove from wishlist"
                                onclick="removeFromWishlist(this)"
                            ><svg viewBox="0 0 24 24" fill="#e8420f" stroke="#e8420f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></button>

                            @if($product->image)
                                <img
                                    src="{{ str_starts_with($product->image, 'http') ? $product->image : asset('storage/' . ltrim($product->image, '/')) }}"
                                    alt="{{ $product->name }}"
                                    style="display:none;"
                                    onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                                    onerror="this.style.display='none';"
                                >
                            @endif
                            <span>📦</span>

                        </div>

                        <div class="category">
                            {{ $product->category ?? 'Other' }}
                        </div>

                        <div class="product-name">
                            {{ $product->name }}
                        </div>

                        <div class="price">
                            ₱{{ number_format($product->price, 2) }}
                        </div>

                        <a href="{{ route('product.details', $product->id) }}" class="view-btn">
                            View Product
                        </a>

                    </div>

                @endforeach

            </div>

        @else

            <div class="empty-state">
                <div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="#f4b3a3" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div>
                <h2>Your wishlist is empty</h2>
                <p>Save products you like by tapping the heart icon while browsing.</p>
                <a href="{{ route('products') }}" class="shop-btn">Start Shopping</a>
            </div>

        @endif

    </div>

    <script>
        function removeFromWishlist(btn) {
            var productId = btn.dataset.productId;
            var token = document.querySelector('input[name="_token"]')
                ? document.querySelector('input[name="_token"]').value
                : '';

            var form = new FormData();
            form.append('_token', token);

            fetch('/wishlist/toggle/' + productId, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: form
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function () {
                    var card = document.getElementById('wishlist-card-' + productId);
                    if (card) card.remove();

                    if (document.querySelectorAll('.product-card').length === 0) {
                        location.reload();
                    }
                });
        }
    </script>

    @include('partials.pwa-register')

</body>
</html>
