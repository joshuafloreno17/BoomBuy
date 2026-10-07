<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Variations — {{ $product->name }} — BoomBuy Seller</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-variations.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="dashboard" :user="$user" />

    <main class="main-content">

        <div class="container">

            <x-seller-page-head
                :title="'Variations — ' . $product->name"
                subtitle="Each option has its own stock. Buyers pick one on the product page."
                :crumbs="['My Products' => route('seller.dashboard'), $product->name => route('seller.products.edit', $product->id)]"
            />

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

            {{-- EXISTING OPTIONS: restock / reprice right in the table, one Save for all. --}}
            <div class="card">
                <div class="card-head">
                    <h2>Options <small>{{ $variations->count() }}</small></h2>
                    @if($variations->isNotEmpty())
                        <span class="card-note">Total stock: <strong>{{ number_format($variations->sum('stock')) }}</strong></span>
                    @endif
                </div>

                @if($variations->isEmpty())
                    <p class="empty-message">No variations yet — this product is sold as a single option.</p>
                @else
                    <form method="POST" action="{{ route('seller.products.variations.update', $product->id) }}" enctype="multipart/form-data" id="variationsForm">
                        @csrf
                        @method('PUT')

                        <div class="var-table">
                            <div class="var-tr var-th">
                                <span>Photo</span>
                                <span>Option</span>
                                <span>Extra price (₱)</span>
                                <span>Stock</span>
                                <span></span>
                            </div>

                            @foreach($variations as $variation)
                                <div class="var-tr">
                                    <label class="var-photo" title="Change photo">
                                        @if($variation->image)
                                            <img src="{{ asset('storage/' . ltrim($variation->image, '/')) }}" alt="{{ $variation->variation_value }}">
                                        @else
                                            <i class="bi bi-camera"></i>
                                        @endif
                                        <input type="file" name="images[{{ $variation->id }}]" accept="image/*" aria-label="Change photo for {{ $variation->shortLabel() }}">
                                    </label>

                                    <div class="var-name">
                                        <strong>{{ $variation->shortLabel() }}</strong>
                                        <small>{{ $variation->variation_type }}{{ $variation->hasSecondOption() ? ' × ' . $variation->option2_type : '' }}</small>
                                    </div>

                                    <label class="var-cell">
                                        <span>Extra price (₱)</span>
                                        <input type="number" step="0.01" name="variations[{{ $variation->id }}][price_adjustment]" value="{{ old('variations.' . $variation->id . '.price_adjustment', (float) $variation->price_adjustment) }}" data-original="{{ (float) $variation->price_adjustment }}">
                                    </label>

                                    <label class="var-cell">
                                        <span>Stock</span>
                                        <input type="number" min="0" name="variations[{{ $variation->id }}][stock]" value="{{ old('variations.' . $variation->id . '.stock', $variation->stock) }}" data-original="{{ $variation->stock }}" class="{{ $variation->stock <= 0 ? 'is-out' : '' }}">
                                    </label>

                                    <button type="submit" form="delete-variation-{{ $variation->id }}" class="delete-btn" aria-label="Remove {{ $variation->shortLabel() }}">
                                        <i class="bi bi-trash3"></i><span>Remove</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <div class="var-save">
                            <span class="var-dirty" id="varDirty" hidden><i class="bi bi-dot"></i> Unsaved changes</span>
                            <button type="submit" class="save-btn"><i class="bi bi-check2"></i> Save changes</button>
                        </div>
                    </form>

                    {{-- Delete forms live outside the save form (forms can't nest). --}}
                    @foreach($variations as $variation)
                        <form method="POST" action="{{ route('seller.products.variations.delete', [$product->id, $variation->id]) }}" id="delete-variation-{{ $variation->id }}" data-confirm="Remove {{ $variation->shortLabel() }}?" data-confirm-ok="Remove" data-confirm-danger hidden>
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                @endif
            </div>

            {{-- ADD ONE --}}
            <div class="card">
                <h2>Add an option</h2>

                <form method="POST" action="{{ route('seller.products.variations.store', $product->id) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-row">

                        <div class="form-group">
                            <label for="variation_type">Type</label>
                            <input type="text" id="variation_type" name="variation_type" placeholder="e.g. Color" value="{{ old('variation_type', $variations->first()->variation_type ?? '') }}" autocomplete="off" required>
                        </div>

                        <div class="form-group">
                            <label for="variation_value">Value</label>
                            <input type="text" id="variation_value" name="variation_value" placeholder="e.g. Red" value="{{ old('variation_value') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="option2_type">Second type <small>· optional, e.g. Size</small></label>
                            <input type="text" id="option2_type" name="option2_type" placeholder="e.g. Size" value="{{ old('option2_type', $variations->first()->option2_type ?? '') }}" autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label for="option2_value">Second value</label>
                            <input type="text" id="option2_value" name="option2_value" placeholder="e.g. M" value="{{ old('option2_value') }}">
                        </div>

                        <div class="form-group">
                            <label for="price_adjustment">Extra price (₱) <small>· 0 = same</small></label>
                            <input type="number" step="0.01" id="price_adjustment" name="price_adjustment" placeholder="0" value="{{ old('price_adjustment', 0) }}">
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" min="0" id="stock" name="stock" placeholder="0" value="{{ old('stock') }}" required>
                        </div>

                    </div>

                    <div class="form-group">
                        <label for="image">Photo (optional)</label>
                        @include('partials.file-picker', [
                            'id' => 'image',
                            'name' => 'image',
                            'accept' => 'image/*',
                            'label' => 'Add a photo of this option',
                            'hint' => 'Buyers see it when they pick this option · up to 4 MB',
                        ])
                    </div>

                    <button type="submit" class="save-btn"><i class="bi bi-plus-lg"></i> Add option</button>

                </form>
            </div>

        </div>

    </main>

</div>

<script>
    // Mark what changed, so it's clear there is something to save.
    (function () {
        var form = document.getElementById('variationsForm');
        if (!form) return;
        var note = document.getElementById('varDirty');

        function refresh() {
            var dirty = false;
            form.querySelectorAll('input[data-original]').forEach(function (input) {
                var changed = String(Number(input.value)) !== String(Number(input.dataset.original));
                input.classList.toggle('is-changed', changed);
                input.classList.toggle('is-out', input.name.endsWith('[stock]') && Number(input.value) <= 0);
                dirty = dirty || changed;
            });
            form.querySelectorAll('.var-photo input').forEach(function (input) {
                var picked = input.files && input.files.length > 0;
                input.closest('.var-photo').classList.toggle('is-changed', picked);
                dirty = dirty || picked;
            });
            note.hidden = !dirty;
        }

        form.addEventListener('input', refresh);
        form.addEventListener('change', refresh);
    })();
</script>

    @include('partials.pwa-register')

</body>
</html>
