<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Promotions — BoomBuy Seller'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-vouchers.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-promotions.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="promotions" :user="$user" />

    <main class="main-content">

        <div class="container">

            <x-seller-page-head title="Promotions" subtitle="Store sales, flash sales and bundle deals. You fund these discounts; buyers see them on your products." />

            @if(session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="error-box">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{-- STORE SALES --}}
            <div class="card" id="sales">
                <h2>Create a Store Sale</h2>
                <p class="promo-note">One sale per product at a time. It starts and ends on its own at the times you set; you can change the discount or end it early.</p>

                <form method="POST" action="{{ route('seller.sales.store') }}">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sale_product">Product</label>
                            <select id="sale_product" name="product_id" required>
                                <option value="">Choose a product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->regularUnitPrice() }}" @selected((int) old('product_id') === $product->id)>
                                        {{ $product->name }} — ₱{{ number_format($product->regularUnitPrice(), 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="discount_type">Discount</label>
                            <select id="discount_type" name="discount_type">
                                <option value="percent" @selected(old('discount_type', 'percent') === 'percent')>% off</option>
                                <option value="fixed" @selected(old('discount_type') === 'fixed')>Fixed sale price (₱)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="discount_value">Value</label>
                            <input type="number" id="discount_value" name="discount_value" step="0.01" min="1" value="{{ old('discount_value') }}" placeholder="e.g. 20" required>
                            <small class="promo-preview" id="salePreview" aria-live="polite"></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="starts_at">Starts</label>
                            <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="ends_at">Ends</label>
                            <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>

                    <button type="submit" class="save-btn">Save Sale</button>
                </form>
            </div>

            <div class="card">
                <h2>My Store Sales</h2>

                @forelse($sales as $sale)
                    @php
                        $saleProduct = $sale->product;
                        $regular = $saleProduct ? $saleProduct->regularUnitPrice() : 0;
                        $status = $sale->status();
                    @endphp
                    <div class="promo-row" data-sale="{{ $sale->id }}">
                        <div class="promo-main">
                            <strong>{{ $saleProduct->name ?? 'Deleted product' }}</strong>
                            <span class="status-badge {{ $status === 'Ongoing' ? 'status-active' : 'status-inactive' }}" data-sale-status="{{ $sale->id }}">{{ $status }}</span>
                            <small>
                                {{ $sale->discount_type === 'percent' ? (int) $sale->discount_value . '% off' : 'Sale price' }}:
                                ₱{{ number_format($regular, 2) }} → <b data-sale-price="{{ $sale->id }}">₱{{ number_format($sale->priceFor($regular), 2) }}</b>
                                @if($saleProduct && $saleProduct->variations->isNotEmpty())
                                    (every option {{ (int) $sale->discount_value }}% off)
                                @endif
                            </small>
                            <small>{{ $sale->starts_at->format('M d, Y g:i A') }} – {{ $sale->stopsAt()->format('M d, Y g:i A') }}</small>
                        </div>

                        @if($status !== 'Ended')
                            <div class="promo-actions">
                                <form method="POST" action="{{ route('seller.sales.update', $sale->id) }}" class="promo-edit">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="discount_value" step="0.01" min="1" value="{{ $sale->discount_type === 'percent' ? (int) $sale->discount_value : $sale->discount_value }}" aria-label="New discount for {{ $saleProduct->name ?? 'this sale' }}">
                                    <button type="submit" class="mini-btn toggle">Save</button>
                                </form>
                                <form method="POST" action="{{ route('seller.sales.end', $sale->id) }}" data-confirm="End this sale now? Buyers see the regular price at once." data-confirm-ok="End Now" data-confirm-danger>
                                    @csrf
                                    <button type="submit" class="mini-btn delete">End Now</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="empty-message">No store sales yet.</p>
                @endforelse
            </div>

            @includeIf('partials.seller.flash-bundles', ['products' => $products, 'flashSales' => $flashSales, 'bundles' => $bundles])

        </div>

    </main>

</div>

<script>
    // Preview the exact sale price while typing.
    (function () {
        var product = document.getElementById('sale_product');
        var type = document.getElementById('discount_type');
        var value = document.getElementById('discount_value');
        var out = document.getElementById('salePreview');
        function show() {
            var option = product.options[product.selectedIndex];
            var regular = option ? parseFloat(option.dataset.price || '0') : 0;
            var v = parseFloat(value.value);
            if (!regular || isNaN(v)) { out.textContent = ''; return; }
            var price = type.value === 'percent' ? Math.round(regular * (100 - v)) / 100 : v;
            out.textContent = 'Sale price: ₱' + price.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        [product, type, value].forEach(function (el) { el.addEventListener('input', show); el.addEventListener('change', show); });
    })();
</script>

    @include('partials.pwa-register')

</body>
</html>
