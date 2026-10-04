<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'My Wishlist — BoomBuy'])

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ vasset('css/views/buyer-wishlist.css') }}">
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'wishlist'])

    <div class="container">

        @include('partials.page-head', [
            'title' => 'My Wishlist',
            'note' => count($products) . ' ' . \Illuminate\Support\Str::plural('item', count($products)),
        ])

        @if (session('success'))
            <div class="success-box">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="error-box">{{ session('error') }}</div>
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
                            <span><i class="bi bi-box-seam-fill"></i></span>

                            <a href="{{ route('product.details', $product->id) }}" class="card-media-link" tabindex="-1" aria-hidden="true"></a>

                        </div>

                        <div class="category">
                            {{ $product->category ?? 'Other' }}
                        </div>

                        <a href="{{ route('product.details', $product->id) }}" class="product-name">
                            {{ $product->name }}
                        </a>

                        <div class="price">
                            ₱{{ number_format($product->price, 2) }}
                        </div>

                        <div class="card-actions">

                            @if(!$product->on_sale)

                                <span class="out-of-stock-label">Unavailable</span>

                            @elseif($product->has_options)

                                {{-- Color/size has to be picked on the product page. --}}
                                <a href="{{ route('product.details', $product->id) }}" class="add-cart-btn" style="display:inline-block; text-align:center; text-decoration:none;">
                                    Choose Options
                                </a>

                            @elseif($product->stock > 0)

                                <form action="{{ route('cart.add', $product->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="add-cart-btn">
                                        Add to Cart
                                    </button>
                                </form>

                            @else

                                <span class="out-of-stock-label">Out of Stock</span>

                            @endif

                            <a href="{{ route('product.details', $product->id) }}" class="view-btn">
                                View
                            </a>

                        </div>

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
            var token = document.querySelector('meta[name="csrf-token"]')
                ? document.querySelector('meta[name="csrf-token"]').content
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

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>
</html>
