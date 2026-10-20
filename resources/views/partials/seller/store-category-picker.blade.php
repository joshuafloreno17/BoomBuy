{{--
    The store categories a product is in (up to StoreCategory::MAX_PER_PRODUCT),
    separate from the platform category above it.

    @include('partials.seller.store-category-picker', ['selected' => [ids]])
--}}
@php
    $storeCategories = \App\Models\StoreCategory::ordered((int) session('user.id'));
    $selected = collect(old('store_categories', $selected ?? []))->map(fn ($id) => (int) $id)->all();
    $max = \App\Models\StoreCategory::MAX_PER_PRODUCT;
@endphp

<div class="store-cat-picker" data-store-cat-picker data-max="{{ $max }}">
    <input type="hidden" name="store_categories_sent" value="1">
    <span class="store-cat-label">Store categories <small>(optional, up to {{ $max }})</small></span>

    @if($storeCategories->isEmpty())
        <p class="store-cat-empty">
            No store categories yet. <a href="{{ route('seller.store-categories') }}">Create some</a> to group products on your store page.
        </p>
    @else
        <div class="store-cat-options">
            @foreach($storeCategories as $storeCategory)
                <label @class(['store-cat-option', 'is-sub' => $storeCategory->parent_id])>
                    <input type="checkbox" name="store_categories[]" value="{{ $storeCategory->id }}" @checked(in_array($storeCategory->id, $selected, true))>
                    <span>{{ $storeCategory->fullName() }}</span>
                </label>
            @endforeach
        </div>
        <small class="store-cat-hint" data-store-cat-hint>Products in no store category show as Uncategorized.</small>
    @endif

    @error('store_categories')<span class="store-cat-error">{{ $message }}</span>@enderror
</div>

@once
<style>
    .store-cat-picker { margin-top: 16px; }
    .store-cat-label { display: block; font-weight: 700; font-size: 13px; margin-bottom: 8px; color: var(--theme-ink); }
    .store-cat-label small { font-weight: 500; color: #857ca0; }
    .store-cat-options { display: flex; flex-wrap: wrap; gap: 8px; }
    .store-cat-option { display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; border: 1px solid #ddd5f2; border-radius: 999px; font-size: 13px; cursor: pointer; background: #fff; }
    .store-cat-option:has(input:checked) { border-color: var(--theme-primary); background: #f3efff; }
    .store-cat-option:has(input:disabled) { opacity: .5; cursor: not-allowed; }
    .store-cat-option input { accent-color: var(--theme-primary); }
    .store-cat-hint, .store-cat-empty { display: block; margin-top: 8px; font-size: 12px; color: #574e70; }
    .store-cat-hint.is-limit { color: #be123c; font-weight: 700; }
    .store-cat-error { display: block; margin-top: 6px; font-size: 12px; font-weight: 700; color: #be123c; }
</style>
<script>
    // At most N boxes: once N are ticked, the rest are disabled.
    document.addEventListener('change', function (event) {
        var picker = event.target.closest('[data-store-cat-picker]');
        if (!picker) return;
        var max = parseInt(picker.dataset.max, 10);
        var boxes = picker.querySelectorAll('input[type=checkbox]');
        var ticked = picker.querySelectorAll('input[type=checkbox]:checked').length;
        boxes.forEach(function (box) { box.disabled = !box.checked && ticked >= max; });
        var hint = picker.querySelector('[data-store-cat-hint]');
        if (hint) {
            hint.classList.toggle('is-limit', ticked >= max);
            hint.textContent = ticked >= max ? 'A product can be in at most ' + max + ' store categories.' : 'Products in no store category show as Uncategorized.';
        }
    });
</script>
@endonce
