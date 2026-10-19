{{-- Seller → Promotions: flash sales and bundle deals (S8). Needs $products, $flashSales, $bundles. --}}

<div class="card" id="flash">
    <h2>Create a Flash Sale</h2>
    <p class="promo-note">A flash price for a limited number of units in one 2-hour slot. When the flash units run out, the price goes back to normal (or to your store sale).</p>

    <form method="POST" action="{{ route('seller.flash.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label for="flash_product">Product <small>(without options)</small></label>
                <select id="flash_product" name="product_id" required>
                    <option value="">Choose a product</option>
                    @foreach($products->filter(fn ($p) => !$p->variations()->exists()) as $product)
                        <option value="{{ $product->id }}" @selected((int) old('product_id') === $product->id)>{{ $product->name }} — ₱{{ number_format($product->regularUnitPrice(), 2) }} · {{ (int) $product->stock }} in stock</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="flash_date">Day</label>
                <input type="date" id="flash_date" name="date" value="{{ old('date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label for="flash_slot">Time slot</label>
                <select id="flash_slot" name="slot" required>
                    @foreach(\App\Models\FlashSale::SLOTS as $i => [$from, $to])
                        <option value="{{ $i }}" @selected((string) old('slot') === (string) $i)>{{ \Illuminate\Support\Carbon::createFromTime($from % 24)->format('g:i A') }} – {{ $to === 24 ? '12:00 AM' : \Illuminate\Support\Carbon::createFromTime($to)->format('g:i A') }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="flash_price">Flash price (₱)</label>
                <input type="number" id="flash_price" name="flash_price" step="0.01" min="1" value="{{ old('flash_price') }}" required>
            </div>
            <div class="form-group">
                <label for="flash_quantity">Flash quantity</label>
                <input type="number" id="flash_quantity" name="quantity" min="1" value="{{ old('quantity') }}" required>
            </div>
        </div>
        <button type="submit" class="save-btn">Save Flash Sale</button>
    </form>

    @if($flashSales->isNotEmpty())
        <h2 style="margin-top:22px;">My Flash Sales</h2>
        @foreach($flashSales as $flash)
            <div class="promo-row" data-flash="{{ $flash->id }}">
                <div class="promo-main">
                    <strong>{{ $flash->product->name ?? 'Deleted product' }} · ₱{{ number_format($flash->flash_price, 2) }}</strong>
                    <span class="status-badge {{ $flash->status() === 'Ongoing' ? 'status-active' : 'status-inactive' }}" data-flash-status="{{ $flash->id }}">{{ $flash->status() }}</span>
                    <small>{{ $flash->starts_at->format('M d, g:i A') }} – {{ $flash->ends_at->format('g:i A') }} · {{ $flash->remaining() }} of {{ $flash->quantity }} left</small>
                </div>
            </div>
        @endforeach
    @endif
</div>

<div class="card" id="bundles">
    <h2>Create a Bundle Deal</h2>
    <p class="promo-note">Bundle: any number of items (any options) of the products you pick get a % off. Buy X Get Y: buying X units of one product makes Y units of another free.</p>

    <form method="POST" action="{{ route('seller.bundles.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label for="bundle_name">Name</label>
                <input type="text" id="bundle_name" name="name" maxlength="80" value="{{ old('name') }}" placeholder="e.g. Tote trio" required>
            </div>
            <div class="form-group">
                <label for="bundle_type">Type</label>
                <select id="bundle_type" name="type">
                    <option value="bundle" @selected(old('type', 'bundle') === 'bundle')>Bundle (% off)</option>
                    <option value="bxgy" @selected(old('type') === 'bxgy')>Buy X Get Y free</option>
                </select>
            </div>
        </div>

        <div class="form-row" data-bundle-part="bundle">
            <div class="form-group">
                <label for="bundle_products">Products</label>
                <select id="bundle_products" name="product_ids[]" multiple size="3">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="min_quantity">Items needed</label>
                <input type="number" id="min_quantity" name="min_quantity" min="2" value="{{ old('min_quantity', 3) }}">
            </div>
            <div class="form-group">
                <label for="bundle_percent">% off</label>
                <input type="number" id="bundle_percent" name="percent" min="1" max="99" value="{{ old('percent') }}">
            </div>
        </div>

        <div class="form-row" data-bundle-part="bxgy">
            <div class="form-group">
                <label for="buy_product">Buy</label>
                <select id="buy_product" name="buy_product_id">
                    <option value="">Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <input type="number" name="buy_quantity" min="1" value="{{ old('buy_quantity', 2) }}" aria-label="How many to buy" style="margin-top:6px;">
            </div>
            <div class="form-group">
                <label for="get_product">Get free</label>
                <select id="get_product" name="get_product_id">
                    <option value="">Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <input type="number" name="get_quantity" min="1" value="{{ old('get_quantity', 1) }}" aria-label="How many free" style="margin-top:6px;">
            </div>
        </div>

        <button type="submit" class="save-btn">Save Deal</button>
    </form>

    @if($bundles->isNotEmpty())
        <h2 style="margin-top:22px;">My Deals</h2>
        @foreach($bundles as $bundle)
            <div class="promo-row" data-bundle="{{ $bundle->id }}">
                <div class="promo-main">
                    <strong>{{ $bundle->name }}</strong>
                    <span class="status-badge {{ $bundle->is_active ? 'status-active' : 'status-inactive' }}">{{ $bundle->is_active ? 'On' : 'Off' }}</span>
                    <small>{{ $bundle->summary() }}</small>
                </div>
                <div class="promo-actions">
                    <form method="POST" action="{{ route('seller.bundles.toggle', $bundle->id) }}">
                        @csrf
                        <button type="submit" class="mini-btn toggle">{{ $bundle->is_active ? 'Turn off' : 'Turn on' }}</button>
                    </form>
                </div>
            </div>
        @endforeach
    @endif
</div>

<script>
    // Show only the fields of the chosen deal type.
    (function () {
        var type = document.getElementById('bundle_type');
        function show() {
            document.querySelectorAll('[data-bundle-part]').forEach(function (part) { part.hidden = part.dataset.bundlePart !== type.value; });
        }
        type.addEventListener('change', show);
        show();
    })();
</script>
