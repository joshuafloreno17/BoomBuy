{{--
    Brand, condition, shipping weight and size on Add / Edit Product.
    @include('partials.seller.product-listing-fields', ['product' => $product ?? null])
--}}
@php($p = $product ?? null)

<div class="form-row-2">
    <div class="form-group">
        <label for="brand">Brand <small class="opt">(optional)</small></label>
        <input type="text" id="brand" name="brand" maxlength="60" value="{{ old('brand', $p->brand ?? '') }}" placeholder="e.g. No brand">
        @error('brand')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label for="condition">Condition</label>
        <select id="condition" name="condition">
            @foreach(['New', 'Used'] as $condition)
                <option value="{{ $condition }}" @selected(old('condition', $p->condition ?? 'New') === $condition)>{{ $condition }}</option>
            @endforeach
        </select>
        @error('condition')<span class="field-error">{{ $message }}</span>@enderror
    </div>
</div>

<div class="form-group">
    <label for="weight_kg">Shipping weight (kg)</label>
    <input type="number" id="weight_kg" name="weight_kg" min="0.01" max="{{ \App\Support\ProductRules::WEIGHT_MAX }}" step="0.01"
           value="{{ old('weight_kg', $p->weight_kg ?? '') }}" placeholder="e.g. 0.6" @required(!$p || !$p->is_draft)>
    <small class="hint">Packed weight. Shipping is charged per whole kg, so 0.01 kg is charged as 1 kg.</small>
    @error('weight_kg')<span class="field-error">{{ $message }}</span>@enderror
</div>

<div class="form-group">
    <label for="purchase_limit">Limit per buyer <small class="opt">(optional)</small></label>
    <input type="number" id="purchase_limit" name="purchase_limit" min="1" step="1"
           value="{{ old('purchase_limit', $p->purchase_limit ?? '') }}" placeholder="No limit">
    <small class="hint">The most units one buyer can buy in total. Leave blank for no limit.</small>
    @error('purchase_limit')<span class="field-error">{{ $message }}</span>@enderror
</div>

<div class="form-group">
    <label>Package size (cm) <small class="opt">(optional)</small></label>
    <div class="dims">
        <input type="number" name="length_cm" min="0.01" step="0.01" value="{{ old('length_cm', $p->length_cm ?? '') }}" placeholder="Length" aria-label="Length in cm">
        <span aria-hidden="true">×</span>
        <input type="number" name="width_cm" min="0.01" step="0.01" value="{{ old('width_cm', $p->width_cm ?? '') }}" placeholder="Width" aria-label="Width in cm">
        <span aria-hidden="true">×</span>
        <input type="number" name="height_cm" min="0.01" step="0.01" value="{{ old('height_cm', $p->height_cm ?? '') }}" placeholder="Height" aria-label="Height in cm">
    </div>
    @error('dimensions')<span class="field-error">{{ $message }}</span>@enderror
</div>

@once
<style>
    .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-group .opt { font-weight: 500; color: #857ca0; }
    .form-group .hint { display: block; margin-top: 6px; font-size: 11px; color: #857ca0; }
    .field-error { display: block; margin-top: 6px; font-size: 12px; font-weight: 700; color: #be123c; }
    .dims { display: flex; align-items: center; gap: 6px; }
    .dims input { min-width: 0; flex: 1; }
    .dims span { color: #857ca0; }
    @media (max-width: 560px) { .form-row-2 { grid-template-columns: 1fr; } }
</style>
@endonce
