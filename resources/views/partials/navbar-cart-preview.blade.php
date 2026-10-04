{{--
    Contents of the cart hover panel in the buyer navbar. Rendered with the
    page and re-fetched from CartController::preview on hover, so items added
    with AJAX (shop page "Add to cart") show up without a reload.
--}}
<div class="bb-pop-head">
    <strong>Recently Added</strong>
</div>

@if(empty($previewLines))

    <div class="bb-pop-empty">
        <i class="bi bi-cart3"></i>
        Your cart is empty.
        <a href="{{ route('products') }}" class="bb-pop-btn is-primary" style="margin-top:12px;">Start Shopping</a>
    </div>

@else

    <div class="bb-pop-list">
        @foreach($previewLines as $line)
            <a href="{{ $line['url'] }}" class="bb-pop-item">
                @if($line['image'])
                    <img src="{{ $line['image'] }}" alt="" class="bb-pop-thumb" loading="lazy"
                         onerror="this.outerHTML='<span class=&quot;bb-pop-thumb&quot;><i class=&quot;bi {{ $line['icon'] }}&quot;></i></span>'">
                @else
                    <span class="bb-pop-thumb"><i class="bi {{ $line['icon'] }}"></i></span>
                @endif

                <span class="bb-pop-body">
                    <span class="bb-pop-title">{{ $line['name'] }}</span>
                    @if($line['variation'])
                        <span class="bb-pop-sub">{{ $line['variation'] }}</span>
                    @endif
                </span>

                <span class="bb-pop-price">
                    {{ $line['quantity'] }} × ₱{{ number_format($line['price'], 2) }}
                </span>
            </a>
        @endforeach
    </div>

    <div class="bb-pop-foot">
        <div class="bb-pop-summary">
            <span>{{ $previewMore > 0 ? '+' . $previewMore . ' more ' . \Illuminate\Support\Str::plural('item', $previewMore) : '' }}</span>
            <span>Subtotal <strong>₱{{ number_format($previewSubtotal, 2) }}</strong></span>
        </div>
        <div class="bb-pop-actions">
            <a href="{{ route('cart') }}" class="bb-pop-btn">View Cart</a>
            <a href="{{ route('checkout') }}" class="bb-pop-btn is-primary">Checkout</a>
        </div>
    </div>

@endif
