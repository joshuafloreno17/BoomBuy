<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Options — {{ $product->name }} — BoomBuy Seller</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="stylesheet" href="{{ vasset('css/seller-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/seller-variations.css') }}">
</head>

<body>

<div class="layout">

    <x-layout.seller-sidebar active="products" :user="$user" />

    <main class="main-content">

        <div class="container">

            <x-seller-page-head
                :title="'Options — ' . $product->name"
                subtitle="Each option has its own stock. Buyers pick one on the product page."
                :crumbs="['My Products' => route('seller.products'), $product->name => route('seller.products.edit', $product->id)]"
                :back="route('seller.products.edit', $product->id)"
            >
                <a href="{{ route('seller.products.edit', $product->id) }}" class="var-back-btn"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Edit Product</a>
            </x-seller-page-head>

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
                    <div class="card-head-right">
                        @if($variations->isNotEmpty())
                            <span class="card-note">Total stock: <strong>{{ number_format($variations->sum('stock')) }}</strong></span>
                        @endif
                        {{-- The add form is below the whole table; this jumps there. --}}
                        <a href="#add-option" class="var-add-jump" data-add-jump><i class="bi bi-plus-lg" aria-hidden="true"></i> {{ $variations->contains(fn ($v) => $v->hasSecondOption()) ? 'Add color / size' : 'Add option' }}</a>
                    </div>
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
                                @php $isNew = (int) session('added_id') === $variation->id || in_array($variation->id, (array) session('added_ids', []), true); $isFirstNew = $isNew && !($newShown ?? false); if ($isNew) $newShown = true; @endphp
                                <div class="var-tr {{ $isNew ? 'is-new' : '' }}" @if($isFirstNew) id="newOption" @endif>
                                    <label class="var-photo" title="Change photo">
                                        @if($variation->image)
                                            <img src="{{ asset('storage/' . ltrim($variation->image, '/')) }}" alt="{{ $variation->variation_value }}">
                                        @else
                                            <i class="bi bi-camera"></i>
                                        @endif
                                        <input type="file" name="images[{{ $variation->id }}]" accept="image/*" aria-label="Change photo for {{ $variation->shortLabel() }}">
                                    </label>

                                    <div class="var-name">
                                        {{-- Renaming changes the product page only; old orders keep the name they were bought with. --}}
                                        <span class="var-rename">
                                            <input type="text" name="variations[{{ $variation->id }}][value]" value="{{ old('variations.' . $variation->id . '.value', $variation->variation_value) }}" maxlength="50" aria-label="{{ $variation->variation_type }} name" data-original="{{ $variation->variation_value }}" required>
                                            @if($variation->hasSecondOption())
                                                <input type="text" name="variations[{{ $variation->id }}][value2]" value="{{ old('variations.' . $variation->id . '.value2', $variation->option2_value) }}" maxlength="50" aria-label="{{ $variation->option2_type }} name" data-original="{{ $variation->option2_value }}" required>
                                            @endif
                                        </span>
                                        <small>{{ $variation->variation_type }}{{ $variation->hasSecondOption() ? ' × ' . $variation->option2_type : '' }}</small>
                                        <input type="text" class="var-sku" name="variations[{{ $variation->id }}][sku]" value="{{ old('variations.' . $variation->id . '.sku', $variation->sku) }}" maxlength="40" placeholder="SKU (optional)" aria-label="SKU of {{ $variation->shortLabel() }}" data-original="{{ $variation->sku }}">
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

            {{-- PHONES, TABLETS, LAPTOPS: storage for every color at once --}}
            @if(\App\Support\DeviceSpecs::appliesTo($product->category))
                @php
                    $hasStorage = $variations->contains(fn ($v) => \App\Support\DeviceSpecs::isStorage($v->option2_type) || \App\Support\DeviceSpecs::isStorage($v->variation_type));
                    $colorCount = $variations->reject(fn ($v) => \App\Support\DeviceSpecs::isStorage($v->variation_type))->pluck('variation_value')->unique()->count();
                @endphp
                <div class="card storage-card">
                    <h2><i class="bi bi-device-ssd"></i> Storage (GB)</h2>
                    <p class="card-sub">
                        @if($colorCount)
                            Tick the storage sizes this phone, tablet or laptop comes in. Each of your {{ $colorCount }} {{ \Illuminate\Support\Str::plural('color', $colorCount) }} gets every size you tick, priced at the color's price plus the extra below.
                            @unless($hasStorage) Your current colors keep their stock and photos — they become the first size you tick. @endunless
                        @else
                            Tick the storage sizes buyers can choose from, with the extra price for each.
                        @endif
                    </p>

                    <form method="POST" action="{{ route('seller.products.variations.storage', $product->id) }}">
                        @csrf
                        <div class="sb-sizes">
                            @foreach(\App\Support\DeviceSpecs::STORAGE_SIZES as $size)
                                @php $taken = $variations->contains(fn ($v) => $v->option2_value === $size || (\App\Support\DeviceSpecs::isStorage($v->variation_type) && $v->variation_value === $size)); @endphp
                                <label class="sb-size {{ $taken ? 'is-taken' : '' }}">
                                    <input type="checkbox" name="storage[{{ $size }}][on]" value="1" @checked(old('storage.' . $size . '.on'))>
                                    <b>{{ $size }}</b>
                                    @if($taken)
                                        <small>added</small>
                                    @endif
                                    <span>+₱</span>
                                    <input type="number" step="0.01" name="storage[{{ $size }}][extra]" value="{{ old('storage.' . $size . '.extra', 0) }}" aria-label="Extra price for {{ $size }}">
                                </label>
                            @endforeach
                        </div>
                        <div class="form-row">
                            <div class="form-group" style="max-width:220px;">
                                <label for="storage_stock">Stock for each new option</label>
                                <input type="number" min="0" id="storage_stock" name="stock" value="{{ old('stock', 0) }}">
                            </div>
                        </div>
                        <button type="submit" class="save-btn"><i class="bi bi-plus-lg"></i> Add storage</button>
                    </form>
                </div>
            @endif

            {{-- ADD A NEW COLOR (Color × Size): every size in one go --}}
            @php
                $twoPartFirst = $variations->first(fn ($v) => $v->hasSecondOption());
                $knownSizes = $variations->pluck('option2_value')->filter()->unique()->values();
            @endphp
            @if($twoPartFirst && \App\Support\DeviceSpecs::appliesTo($product->category) === false)
                @php $t1 = strtolower($twoPartFirst->variation_type); $t2 = strtolower($twoPartFirst->option2_type); @endphp
                <div class="card add-color" id="add-color">
                    <h2><i class="bi bi-palette" aria-hidden="true"></i> Add a new {{ $t1 }}</h2>
                    <p class="add-hint">Type the {{ $t1 }}, then the stock for each {{ $t2 }}. Leave a {{ $t2 }} blank to skip it. One photo is enough — every {{ $t2 }} of this {{ $t1 }} uses it.</p>

                    @if(session('added_color'))
                        <div class="success-box" id="addResult"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> {{ session('success') }} Add a photo for another {{ $t2 }} only if it looks different.</div>
                    @elseif(old('value') !== null && $errors->any())
                        <div class="error-box" id="addResult">
                            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('seller.products.variations.color', $product->id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group ac-name">
                            <label for="ac_value">New {{ $t1 }}</label>
                            <input type="text" id="ac_value" name="value" maxlength="50" placeholder="e.g. Pink" value="{{ old('value') }}" required>
                        </div>

                        <div class="ac-sizes" role="group" aria-label="Stock per {{ $t2 }}">
                            <div class="ac-tr ac-th"><span>{{ $twoPartFirst->option2_type }}</span><span>Stock</span><span>Extra price (₱)</span></div>
                            @foreach($knownSizes as $size)
                                <div class="ac-tr">
                                    <b>{{ $size }}</b>
                                    <input type="number" min="0" max="99999" name="sizes[{{ $size }}][stock]" value="{{ old('sizes.' . $size . '.stock') }}" placeholder="e.g. 10" aria-label="Stock for {{ $size }}">
                                    <input type="number" step="0.01" name="sizes[{{ $size }}][extra]" value="{{ old('sizes.' . $size . '.extra', 0) }}" aria-label="Extra price for {{ $size }}">
                                </div>
                            @endforeach
                        </div>

                        <div class="form-group">
                            <label for="ac_image">Photo of this {{ $t1 }} <small>· optional</small></label>
                            @include('partials.file-picker', [
                                'id' => 'ac_image',
                                'name' => 'image',
                                'accept' => 'image/*',
                                'label' => 'Add one photo',
                                'hint' => 'Shown for every ' . $t2 . ' of this ' . $t1 . ' · up to 4 MB',
                            ])
                        </div>

                        <button type="submit" class="save-btn"><i class="bi bi-plus-lg"></i> Add {{ $t1 }} in all {{ \Illuminate\Support\Str::plural($t2) }}</button>
                    </form>
                </div>
            @endif

            {{-- ADD ONE (with "Add a new color" above, this one is for a single missing size) --}}
            @php
                $oneSizeMode = $twoPartFirst && \App\Support\DeviceSpecs::appliesTo($product->category) === false;
                $addTitle = $oneSizeMode ? 'Add one ' . strtolower($twoPartFirst->option2_type) : 'Add an option';
            @endphp
            <div class="card sm-sheet" data-sm-sheet="create" aria-label="{{ $addTitle }}" id="add-option">
                <div class="sm-sheet-head sm-only"><strong>{{ $addTitle }}</strong><button type="button" class="sm-sheet-x" data-sm-sheet-close aria-label="Close"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
                <h2>{{ $addTitle }}</h2>

                {{-- This form sits at the bottom, so say what happened right here too. --}}
                @if(session('added'))
                    <div class="success-box" id="addResult"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> {{ session('added') }} was added. Add another one below.</div>
                @elseif(old('variation_value') !== null && (session('error') || $errors->any()))
                    <div class="error-box field-error" id="addResult">
                        @if(session('error'))<div>{{ session('error') }}</div>@endif
                        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('seller.products.variations.store', $product->id) }}" enctype="multipart/form-data">
                    @csrf

                    @php
                        // Once a product has options, every new one uses the same types
                        // (the server enforces it), so show them as fixed labels.
                        $firstOption = $variations->first();
                        $type1 = $firstOption->variation_type ?? null;
                        $type2 = $firstOption ? ($firstOption->option2_type ?: null) : null;
                        $examples = [
                            'color' => ['Pink', 'Red'], 'size' => ['S', 'M'], 'storage' => ['128GB', '256GB'],
                        ];
                        $example1 = $examples[strtolower((string) $type1)][0] ?? 'Red';
                        $example2 = $examples[strtolower((string) $type2)][0] ?? 'M';
                    @endphp

                    @if($oneSizeMode)
                        <p class="add-hint">
                            For one missing {{ strtolower($type2) }} of a {{ strtolower($type1) }} you already have — e.g. {{ $variations->first()->variation_value }} · XXL. For a new {{ strtolower($type1) }} in many {{ \Illuminate\Support\Str::plural(strtolower($type2)) }}, use <a href="#add-color" data-add-jump>Add a new {{ strtolower($type1) }}</a> above.
                        </p>
                    @elseif($type1)
                        <p class="add-hint">
                            Type the new {{ strtolower($type1) }}{{ $type2 ? ' and ' . strtolower($type2) : '' }}, then its stock.
                            @if($type2) Add one {{ strtolower($type2) }} at a time — e.g. {{ $example1 }} · {{ $example2 }}, then {{ $example1 }} · {{ $examples[strtolower($type2)][1] ?? 'the next one' }}. @endif
                        </p>
                    @endif

                    <div class="form-row">

                        @if($type1)
                            <input type="hidden" name="variation_type" value="{{ $type1 }}">
                            <div class="form-group">
                                <label for="variation_value">{{ $oneSizeMode ? $type1 : 'New ' . strtolower($type1) }}</label>
                                <input type="text" id="variation_value" name="variation_value" placeholder="e.g. {{ $oneSizeMode ? $variations->first()->variation_value : $example1 }}" @if($oneSizeMode) list="knownColors" @endif value="{{ old('variation_value') }}" required>
                            </div>

                            @if($type2)
                                <input type="hidden" name="option2_type" value="{{ $type2 }}">
                                <div class="form-group">
                                    <label for="option2_value">{{ $type2 }}</label>
                                    <input type="text" id="option2_value" name="option2_value" placeholder="e.g. {{ $oneSizeMode ? 'XXL' : $example2 }}" value="{{ old('option2_value') }}" required>
                                </div>
                            @endif

                            @if($oneSizeMode)
                                {{-- Suggest the colors already on the product. --}}
                                <datalist id="knownColors">
                                    @foreach($variations->pluck('variation_value')->unique() as $known)
                                        <option value="{{ $known }}"></option>
                                    @endforeach
                                </datalist>
                            @endif
                        @else
                            {{-- First option: the seller picks the types. --}}
                            <div class="form-group">
                                <label for="variation_type">Type</label>
                                <input type="text" id="variation_type" name="variation_type" placeholder="e.g. Color" value="{{ old('variation_type') }}" autocomplete="off" required>
                            </div>

                            <div class="form-group">
                                <label for="variation_value">Value</label>
                                <input type="text" id="variation_value" name="variation_value" placeholder="e.g. Red" value="{{ old('variation_value') }}" required>
                            </div>

                            <div class="form-group">
                                <label for="option2_type">Second type <small>· optional, e.g. Size</small></label>
                                <input type="text" id="option2_type" name="option2_type" placeholder="e.g. Size" value="{{ old('option2_type') }}" autocomplete="off">
                            </div>

                            <div class="form-group">
                                <label for="option2_value">Second value</label>
                                <input type="text" id="option2_value" name="option2_value" placeholder="e.g. M" value="{{ old('option2_value') }}">
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="price_adjustment">Extra price (₱) <small>· 0 = same</small></label>
                            <input type="number" step="0.01" id="price_adjustment" name="price_adjustment" placeholder="0" value="{{ old('price_adjustment', 0) }}">
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" min="0" max="99999" id="stock" name="stock" placeholder="e.g. 10" value="{{ old('stock') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="sku">SKU <small>· optional</small></label>
                            <input type="text" id="sku" name="sku" maxlength="40" placeholder="e.g. MCH-TOTE-BRN-S" value="{{ old('sku') }}">
                        </div>

                    </div>

                    <div class="form-group">
                        <label for="image">Photo (optional)</label>
                        @include('partials.file-picker', [
                            'id' => 'image',
                            'name' => 'image',
                            'accept' => 'image/*',
                            'label' => 'Add a photo of this option',
                            'hint' => $oneSizeMode
                                ? 'Only if it looks different — otherwise it uses this ' . strtolower($type1) . '\'s photo · up to 4 MB'
                                : 'Buyers see it when they pick this option · up to 4 MB',
                        ])
                    </div>

                    <button type="submit" class="save-btn"><i class="bi bi-plus-lg"></i> {{ $oneSizeMode ? 'Add ' . strtolower($type2) : 'Add option' }}</button>

                </form>
            </div>

            <div class="sm-bar sm-only">
                <button type="button" class="{{ $variations->isEmpty() ? 'sm-bar-main' : 'sm-bar-ghost' }}" data-sm-sheet-open="create"><i class="bi bi-plus-lg" aria-hidden="true"></i> {{ $addTitle }}</button>
                @if($variations->isNotEmpty())
                    <button type="submit" form="variationsForm" class="sm-bar-main"><i class="bi bi-check2" aria-hidden="true"></i> Save</button>
                @endif
            </div>

        </div>

    </main>

</div>

<script>
    // "Add option": bring the add form (or what just happened) into view.
    (function () {
        var isPhone = window.matchMedia('(max-width: 760px)').matches;
        var colorCard = document.getElementById('add-color');
        var card = colorCard || document.getElementById('add-option');

        function showForm() {
            if (!card) return;
            if (isPhone && !colorCard) {
                // Phones: the form is a full-screen sheet behind the bottom-bar button.
                var open = document.querySelector('[data-sm-sheet-open="create"]');
                if (open) open.click();
                return;
            }
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.remove('is-flash');
            void card.offsetWidth;
            card.classList.add('is-flash');
            var first = card.querySelector('#ac_value, #variation_value, #variation_type');
            if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 400);
        }

        document.querySelectorAll('[data-add-jump]').forEach(function (a) {
            a.addEventListener('click', function (e) { e.preventDefault(); showForm(); });
        });

        // After a save, the page reloads at the top.
        var result = document.getElementById('addResult');
        var added = document.getElementById('newOption');
        if (added && !isPhone) {
            added.scrollIntoView({ block: 'center' });           // the new row lights up
        } else if (result && !isPhone) {
            result.scrollIntoView({ block: 'center' });
        } else if (location.hash === '#add-option') {
            showForm();                                           // came from Edit Product
        }
    })();

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
