<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Vouchers — BoomBuy Seller'])

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-vouchers.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-promotions.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="vouchers" :user="$user" />

    <main class="main-content">

        <div class="container">

            <x-seller-page-head title="Vouchers & Discounts" subtitle="Vouchers buyers claim or type at checkout for your products. You cover the discount." />

            @if(session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="error-box">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @elseif(session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            <div class="card">
                <h2>Create Voucher</h2>

                <form method="POST" action="{{ route('seller.vouchers.store') }}">
                    @csrf

                    <div class="form-row">
                        <div class="form-group">
                            <label for="code">Voucher Code</label>
                            <input type="text" id="code" name="code" placeholder="e.g. MCH50" minlength="4" maxlength="12" pattern="[A-Za-z0-9]{4,12}" style="text-transform:uppercase;" value="{{ old('code') }}" required>
                            <small class="promo-note" style="margin:6px 0 0;">4–12 letters and numbers.</small>
                        </div>

                        <div class="form-group">
                            <label for="discount_type">Discount Type</label>
                            <select id="discount_type" name="discount_type" required>
                                <option value="fixed" @selected(old('discount_type', 'fixed') === 'fixed')>Fixed Amount (₱)</option>
                                <option value="percentage" @selected(old('discount_type') === 'percentage')>Percentage (%)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="discount_value">Discount</label>
                            <input type="number" step="0.01" min="0.01" id="discount_value" name="discount_value" placeholder="e.g. 50" value="{{ old('discount_value') }}" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_discount">Cap for % (₱, optional)</label>
                            <input type="number" step="0.01" min="0.01" id="max_discount" name="max_discount" placeholder="e.g. 100" value="{{ old('max_discount') }}">
                        </div>

                        <div class="form-group">
                            <label for="min_order_amount">Minimum Spend (₱)</label>
                            <input type="number" step="0.01" min="0" id="min_order_amount" name="min_order_amount" value="{{ old('min_order_amount', 0) }}">
                        </div>

                        <div class="form-group">
                            <label for="max_uses">Total Usage Limit</label>
                            <input type="number" min="1" id="max_uses" name="max_uses" value="{{ old('max_uses') }}" placeholder="e.g. 100">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="per_buyer_limit">Uses per Buyer</label>
                            <input type="number" min="1" id="per_buyer_limit" name="per_buyer_limit" value="{{ old('per_buyer_limit', 1) }}">
                        </div>

                        <div class="form-group">
                            <label for="starts_at">Starts</label>
                            <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', now()->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="form-group">
                            <label for="ends_at">Ends</label>
                            <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', now()->endOfMonth()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="display">Display</label>
                            <select id="display" name="display">
                                <option value="claimable" @selected(old('display', 'claimable') === 'claimable')>Claimable — shown on your store and products</option>
                                <option value="code_only" @selected(old('display') === 'code_only')>Code only — hidden, buyers type the code</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="applies_to">Applies to</label>
                            <select id="applies_to" name="applies_to">
                                <option value="all" @selected(old('applies_to', 'all') === 'all')>All my products</option>
                                <option value="selected" @selected(old('applies_to') === 'selected')>Selected products only</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="product_ids">Selected products</label>
                            <select id="product_ids" name="product_ids[]" multiple size="3">
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(in_array($product->id, (array) old('product_ids', [])))>{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="save-btn">Create Voucher</button>

                </form>
            </div>

            <div class="card">
                <h2>My Vouchers</h2>

                @forelse($vouchers as $voucher)
                    @php
                        $status = $voucher->status();
                        $locked = $voucher->claims_count > 0 || (int) $voucher->used_count > 0;
                    @endphp
                    <div class="promo-row" data-voucher="{{ $voucher->code }}">
                        <div class="promo-main">
                            <strong><span class="voucher-code">{{ $voucher->code }}</span> · {{ $voucher->summary() }}</strong>
                            <span class="status-badge {{ $status === 'Ongoing' ? 'status-active' : 'status-inactive' }}" data-voucher-status="{{ $voucher->code }}">{{ $status }}</span>
                            <small>
                                {{ $voucher->display === 'code_only' ? 'Code only' : 'Claimable' }} ·
                                {{ $voucher->applies_to === 'selected' ? 'Selected products: ' . $voucher->products->pluck('name')->implode(', ') : 'All products' }}
                            </small>
                            <small data-voucher-usage="{{ $voucher->code }}">Used {{ (int) $voucher->used_count }}{{ $voucher->max_uses ? ' of ' . $voucher->max_uses : '' }} · {{ $voucher->per_buyer_limit ? $voucher->per_buyer_limit . ' per buyer' : 'no limit per buyer' }} · {{ $voucher->claims_count }} claimed</small>
                            <small>
                                {{ $voucher->starts_at ? $voucher->starts_at->format('M d, Y g:i A') : 'Now' }} –
                                {{ $voucher->ends_at ? $voucher->ends_at->format('M d, Y g:i A') : ($voucher->expires_at ? $voucher->expires_at->format('M d, Y') : 'No end') }}
                            </small>
                        </div>

                        @if($status !== 'Ended')
                            <div class="promo-actions">
                                <form method="POST" action="{{ route('seller.vouchers.update', $voucher->id) }}" class="promo-edit">
                                    @csrf
                                    @method('PUT')
                                    @unless($locked)
                                        <input type="number" step="0.01" name="discount_value" value="{{ (float) $voucher->discount_value }}" aria-label="Discount of {{ $voucher->code }}">
                                        <input type="number" step="0.01" name="min_order_amount" value="{{ (float) $voucher->min_order_amount }}" aria-label="Minimum spend of {{ $voucher->code }}">
                                    @endunless
                                    <input type="datetime-local" name="ends_at" value="{{ $voucher->ends_at?->format('Y-m-d\TH:i') }}" aria-label="End of {{ $voucher->code }}">
                                    <input type="number" min="1" name="max_uses" value="{{ $voucher->max_uses }}" aria-label="Usage limit of {{ $voucher->code }}">
                                    <button type="submit" class="mini-btn toggle">Save</button>
                                </form>
                                @if($locked)
                                    <small class="promo-locked" title="Buyers have claimed it">Amount and minimum spend locked</small>
                                @endif
                                <form method="POST" action="{{ route('seller.vouchers.end', $voucher->id) }}" data-confirm="End voucher {{ $voucher->code }} now? It leaves buyers' wallets; orders already placed keep their discount." data-confirm-ok="End" data-confirm-danger>
                                    @csrf
                                    <button type="submit" class="mini-btn delete">End</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="empty-message">No vouchers created yet.</p>
                @endforelse
            </div>

        </div>

    </main>

</div>

    @include('partials.pwa-register')

</body>
</html>
