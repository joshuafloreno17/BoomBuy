{{--
    Shop product cards — rendered with the page and returned by
    ShopController::products() for "Load more" (?partial=1).
    Needs $products (see products()) and $showCategory.
--}}
@foreach($products as $product)
    <article class="sp-card">
        <a href="{{ route('product.details', $product['slug']) }}" class="sp-media" tabindex="-1" aria-hidden="true">
            <i class="bi {{ $product['icon'] }}"></i>
            @if($product['image'])
                <img src="{{ $product['image'] }}" alt="" loading="lazy" onerror="this.remove()">
            @endif
            @if($product['free_shipping'] ?? false)
                <span class="sp-free"><i class="bi bi-truck"></i> Free shipping</span>
            @endif
        </a>

        <button
            type="button"
            class="sp-heart"
            data-wishlist="{{ $product['id'] }}"
            aria-pressed="{{ $product['in_wishlist'] ? 'true' : 'false' }}"
            aria-label="{{ $product['in_wishlist'] ? 'Remove from wishlist' : 'Add to wishlist' }}"
        >
            <i class="bi {{ $product['in_wishlist'] ? 'bi-heart-fill' : 'bi-heart' }}"></i>
        </button>

        <div class="sp-body">
            @if($showCategory)
                <span class="sp-cat">{{ $product['category'] }}</span>
            @endif

            <a href="{{ route('product.details', $product['slug']) }}" class="sp-name">{{ $product['name'] }}</a>

            @if(!empty($product['shop_name']))
                <a href="{{ $product['shop_url'] }}" class="sp-shop"><i class="bi bi-shop"></i> {{ $product['shop_name'] }}</a>
            @endif

            {{-- Rating · sold · stock (stock only when it's worth knowing). --}}
            <span class="sp-meta">
                @if($product['rating'])
                    <i class="bi bi-star-fill"></i> {{ $product['rating'] }} ({{ $product['reviews'] }})
                @else
                    New
                @endif
                @if(($product['sold'] ?? 0) > 0)
                    · {{ $product['sold'] >= 1000 ? number_format($product['sold'] / 1000, 1) . 'k' : $product['sold'] }} sold
                @endif
                @if($product['stock'] <= 0)
                    · <span class="is-low">Sold out</span>
                @elseif($product['stock'] <= 10)
                    · <span class="is-low">Only {{ $product['stock'] }} left</span>
                @elseif(($product['sold'] ?? 0) === 0)
                    · {{ $product['stock'] }} in stock
                @endif
            </span>

            <span class="sp-price">₱{{ number_format($product['price']) }}</span>

            <div class="sp-actions">
                @if($product['stock'] <= 0)
                    <button type="button" class="sp-btn sp-btn-full" disabled>Sold out</button>
                @elseif($product['has_variations'])
                    {{-- Opens the quick "choose a color/size" popup first. --}}
                    <button
                        type="button"
                        class="sp-btn sp-btn-outline"
                        data-pick="cart"
                        data-product="{{ json_encode([
                            'id' => $product['id'],
                            'name' => $product['name'],
                            'type' => $product['variation_type'],
                            'variations' => $product['variations'],
                            'url' => route('product.details', $product['slug']),
                        ]) }}"
                        aria-label="Add {{ $product['name'] }} to cart"
                    >
                        <i class="bi bi-cart-plus"></i><span class="sp-btn-label">Add to cart</span>
                    </button>
                    <button type="button" class="sp-btn sp-btn-buy" data-pick="buy" aria-label="Buy {{ $product['name'] }} now">Buy now</button>
                @else
                    <form action="{{ route('cart.add', $product['id']) }}" method="POST" data-add-cart data-name="{{ $product['name'] }}" data-price="₱{{ number_format($product['price']) }}">
                        @csrf
                        <button type="submit" class="sp-btn sp-btn-outline" aria-label="Add {{ $product['name'] }} to cart">
                            <i class="bi bi-cart-plus"></i><span class="sp-btn-label">Add to cart</span>
                        </button>
                    </form>
                    <form action="{{ route('buy.now', $product['id']) }}" method="POST">
                        @csrf
                        <button type="submit" class="sp-btn sp-btn-buy" aria-label="Buy {{ $product['name'] }} now">Buy now</button>
                    </form>
                @endif
            </div>
        </div>
    </article>
@endforeach
