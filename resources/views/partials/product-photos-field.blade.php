{{--
    "More photos" on the seller's Add / Edit Product forms: extra photos shown
    as thumbnails on the product page (the cover is the main Product Image).

    @include('partials.product-photos-field', ['product' => $product ?? null])
--}}
@php
    $maxExtra = \App\Services\ProductService::MAX_PHOTOS - 1;
    $existing = $product ? $product->images : collect();
    $slotsLeft = $maxExtra - $existing->count();
@endphp

@once
<style>
    .pp-grid { display: flex; flex-wrap: wrap; gap: 10px; margin: 4px 0 10px; }
    .pp-item { position: relative; width: 96px; display: flex; flex-direction: column; gap: 6px; }
    .pp-thumb { width: 96px; height: 96px; border-radius: 12px; overflow: hidden; border: 1px solid #f0e2da; background: #fff8f3; }
    .pp-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pp-badge { position: absolute; top: 6px; left: 6px; padding: 2px 7px; border-radius: 999px; background: #e8420f; color: #fff; font-size: 10.5px; font-weight: 800; letter-spacing: .03em; }
    .pp-opt.pp-opt { display: flex; align-items: center; gap: 6px; margin: 0; font-size: 12px; font-weight: 600; color: #6b6570; cursor: pointer; }
    .pp-opt input { width: 15px; height: 15px; margin: 0; accent-color: #e8420f; }
    .pp-item.is-removed .pp-thumb { opacity: .35; }
    .pp-hint { margin: 6px 0 0; font-size: 12.5px; color: #8a7f86; }
</style>
@endonce

<div class="form-group">
    <label for="photos">
        More Photos <span style="font-weight:500; color:#8a7f86;">(optional · up to {{ $maxExtra }})</span>
    </label>

    @if($existing->isNotEmpty())
        <div class="pp-grid">
            <div class="pp-item">
                <div class="pp-thumb"><img src="{{ asset('storage/' . ltrim($product->image, '/')) }}" alt="Cover photo"></div>
                <span class="pp-badge">Cover</span>
                <label class="pp-opt"><input type="radio" name="cover_photo" value="0" checked> Keep as cover</label>
            </div>
            @foreach($existing as $photo)
                <div class="pp-item" data-pp-item>
                    <div class="pp-thumb"><img src="{{ $photo->url() }}" alt="Product photo {{ $loop->iteration + 1 }}"></div>
                    <label class="pp-opt"><input type="radio" name="cover_photo" value="{{ $photo->id }}"> Make cover</label>
                    <label class="pp-opt"><input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}" data-pp-remove> Remove</label>
                </div>
            @endforeach
        </div>
    @endif

    @if($slotsLeft > 0)
        @include('partials.file-picker', [
            'id' => 'photos',
            'name' => 'photos[]',
            'multiple' => true,
            'accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp',
            'label' => 'Add more photos',
            'hint' => 'Other angles, close-ups, what\'s in the box · pick up to ' . $slotsLeft . ' at once',
        ])
        <div class="pp-grid" id="photoPreviews" data-max="{{ $slotsLeft }}"></div>
    @else
        <p class="pp-hint">This product has all {{ $maxExtra + 1 }} photos. Remove one to add another.</p>
    @endif

    <p class="pp-hint">Buyers see these as thumbnails under the main photo. Hovering a thumbnail shows it big.</p>
</div>

@once
<script>
(function () {
    var input = document.getElementById('photos');
    var box = document.getElementById('photoPreviews');

    document.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('[data-pp-remove]')) {
            e.target.closest('[data-pp-item]').classList.toggle('is-removed', e.target.checked);
        }
    });

    if (!input || !box) return;

    input.addEventListener('change', function () {
        var max = parseInt(box.dataset.max, 10) || 0;
        var files = Array.prototype.slice.call(input.files || []);
        box.innerHTML = '';

        if (files.length > max) {
            (window.bbAlert || window.alert)('You can add up to ' + max + ' more photo' + (max === 1 ? '' : 's') + ' to this product.');
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        files.forEach(function (file) {
            var item = document.createElement('div');
            item.className = 'pp-item';
            var thumb = document.createElement('div');
            thumb.className = 'pp-thumb';
            var img = document.createElement('img');
            img.alt = '';
            img.src = URL.createObjectURL(file);
            thumb.appendChild(img);
            item.appendChild(thumb);
            box.appendChild(item);
        });
    });
})();
</script>
@endonce
