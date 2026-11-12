{{--
    Photos on Edit Product (design D, 2026-11-12):
    1. Shop cover box on top — "Change cover" opens a picker of every photo.
    2. Photos for each option: a row per option (grouped by color), photos
       with "Make first" / "Remove" in words, and an "Add photos" button.
    3. Photos for all options.

    Inputs (unchanged, read by SellerController::updateProduct):
    cover_photo, first_photo, remove_photos[], option_photos[id][], photos[].

    @include('partials.product-photos-field', ['product' => $product])
--}}
@php
    $images = $product->images()->get();
    $options = $product->variations()->orderBy('id')->get();
    $coverPhoto = $images->firstWhere('path', $product->image);
    $coverUrl = $coverPhoto ? $coverPhoto->url() : ($product->image ? asset('storage/' . ltrim($product->image, '/')) : null);

    $row = fn ($o) => [
        'label' => $o->hasSecondOption() ? $o->option2_type . ' ' . $o->option2_value : $o->variation_value,
        'full' => $o->shortLabel(),
        'photos' => $images->where('product_variation_id', $o->id)->values(),
        'max' => \App\Support\ProductPhotos::MAX_PER_OPTION,
        'input' => 'option_photos[' . $o->id . '][]',
        'id' => 'optionPhotos' . $o->id,
    ];

    // Color × Size: a box per color. One type only: one box.
    $twoParts = $options->contains(fn ($o) => $o->hasSecondOption());
    $groups = $twoParts
        ? $options->groupBy('variation_value')->map(function ($g, $value) use ($row) {
            $rows = $g->map($row)->values();
            // The buyer's page shows a size with no photos of its own the photos of
            // the same color's first size that has some (product-details) — say so.
            $lender = $rows->first(fn ($r) => $r['photos']->isNotEmpty());
            $rows = $rows->map(fn ($r) => $r + ['borrow' => ($lender && $r['photos']->isEmpty())
                ? ['label' => $lender['full'], 'url' => $lender['photos']->first()->url()]
                : null]);

            return ['title' => $value, 'rows' => $rows];
        })->values()
        : ($options->isNotEmpty() ? collect([['title' => $options->first()->variation_type, 'rows' => $options->map($row)]]) : collect());

    $general = [
        'label' => $options->isNotEmpty() ? 'Photos for all options' : 'Product photos',
        'full' => $options->isNotEmpty() ? 'all options' : 'this product',
        'photos' => $images->whereNull('product_variation_id')->values(),
        'max' => \App\Support\ProductPhotos::MAX_GENERAL,
        'input' => 'photos[]',
        'id' => 'photos',
    ];

    // Every photo, for the cover picker.
    $labelFor = $options->keyBy('id')->map(fn ($o) => $o->shortLabel());
    $allPhotos = $images->map(fn ($p) => [
        'photo' => $p,
        'label' => $p->product_variation_id ? ($labelFor[$p->product_variation_id] ?? 'Option') : 'All options',
    ]);
@endphp

<link rel="stylesheet" href="{{ vasset('css/partials/product-photos-field.css') }}">

<div class="form-group pf">
    <label>Photos</label>

    {{-- 1. SHOP COVER --}}
    <div class="pf-cover">
        <div class="pf-cover-img" data-pf-cover-img>
            @if($coverUrl)
                <img src="{{ $coverUrl }}" alt="Current shop cover">
            @else
                <i class="bi bi-image" aria-hidden="true"></i>
            @endif
        </div>
        <div class="pf-cover-text">
            <span class="pf-kicker">Shop cover</span>
            <b>This is the photo buyers see first</b>
            <span>On the Shop page, in search and in your product list — before they open the product.</span>
            <span class="pf-cover-pending" data-pf-cover-pending hidden><i class="bi bi-info-circle"></i> New cover picked — press <b>Save Changes</b> to keep it.</span>
            @if($allPhotos->isNotEmpty())
                <button type="button" class="pf-btn" data-pf-open-cover><i class="bi bi-images" aria-hidden="true"></i> Change cover</button>
            @else
                <span class="pf-muted">Add a photo below first — it becomes the cover.</span>
            @endif
        </div>
    </div>

    {{-- 2. EACH OPTION --}}
    @if($groups->isNotEmpty())
        <div class="pf-head">
            <b>Photos for each option</b>
            <span>The <b>first</b> photo shows when a buyer picks that option. Up to {{ \App\Support\ProductPhotos::MAX_PER_OPTION }} each.</span>
        </div>

        @foreach($groups as $group)
            <div class="pf-box">
                <div class="pf-box-title">{{ $group['title'] }}</div>
                @foreach($group['rows'] as $r)
                    @include('partials.product-photos-tile', ['r' => $r, 'product' => $product, 'isGeneral' => false])
                @endforeach
            </div>
        @endforeach
    @endif

    {{-- 3. ALL OPTIONS --}}
    <div class="pf-box pf-box-general">
        @include('partials.product-photos-tile', ['r' => $general, 'product' => $product, 'isGeneral' => true])
    </div>

    {{-- COVER PICKER (inside the form: its radios are what gets saved) --}}
    @if($allPhotos->isNotEmpty())
        <div class="pf-modal" data-pf-modal hidden>
            <div class="pf-dialog" role="dialog" aria-modal="true" aria-labelledby="pfCoverTitle">
                <div class="pf-dialog-head">
                    <b id="pfCoverTitle">Choose the shop cover</b>
                    <button type="button" class="pf-x" data-pf-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>
                <p class="pf-muted">Tap any of this product's photos.</p>
                <div class="pf-pick-grid">
                    @foreach($allPhotos as $item)
                        <label class="pf-pick">
                            <input type="radio" name="cover_photo" value="{{ $item['photo']->id }}" @checked($item['photo']->path === $product->image) data-pf-cover-radio data-src="{{ $item['photo']->url() }}">
                            <span class="pf-pick-img"><img src="{{ $item['photo']->url() }}" alt=""></span>
                            <span class="pf-pick-label">{{ $item['label'] }}</span>
                            <i class="bi bi-check-lg pf-pick-check" aria-hidden="true"></i>
                        </label>
                    @endforeach
                </div>
                <div class="pf-dialog-foot">
                    <button type="button" class="pf-btn" data-pf-close>Use this cover</button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
(function () {
    var root = document.currentScript.previousElementSibling;
    var modal = root.querySelector('[data-pf-modal]');
    var startCover = (root.querySelector('[data-pf-cover-radio]:checked') || {}).value;

    function open() { if (modal) { modal.hidden = false; document.body.style.overflow = 'hidden'; } }
    function close() {
        if (!modal) return;
        modal.hidden = true;
        document.body.style.overflow = '';
        var picked = root.querySelector('[data-pf-cover-radio]:checked');
        if (!picked) return;
        // Show the picked photo in the cover box and on its photo in the list.
        root.querySelector('[data-pf-cover-img]').innerHTML = '<img alt="New shop cover">';
        root.querySelector('[data-pf-cover-img] img').src = picked.dataset.src;
        root.querySelector('[data-pf-cover-pending]').hidden = picked.value === startCover;
        root.querySelectorAll('[data-pf-photo]').forEach(function (el) {
            el.classList.toggle('is-cover', el.dataset.pfPhoto === picked.value);
        });
    }

    root.addEventListener('click', function (e) {
        if (e.target.closest('[data-pf-open-cover]')) { e.preventDefault(); open(); }
        if (e.target.closest('[data-pf-close]') || e.target === modal) { e.preventDefault(); close(); }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal && !modal.hidden) close(); });

    // "Make first" is a radio; tapping the picked one again takes it back.
    var lastFirst = null;
    root.addEventListener('click', function (e) {
        var t = e.target;
        if (!t.matches || !t.matches('input[name="first_photo"]')) return;
        if (t === lastFirst) { t.checked = false; lastFirst = null; }
        else { lastFirst = t; }
    });

    root.addEventListener('change', function (e) {
        var t = e.target;
        if (t.matches('[data-pf-remove]')) {
            t.closest('[data-pf-photo]').classList.toggle('is-removed', t.checked);
        }
        if (t.matches('input[name="first_photo"]')) {
            root.querySelectorAll('input[name="first_photo"]').forEach(function (r) {
                r.closest('.pf-first').classList.toggle('is-on', r.checked);
            });
        }
        // New files: count on the button, previews in the row.
        if (t.matches('[data-pf-add]')) {
            var row = t.closest('.pf-row');
            var strip = row.querySelector('.pf-strip');
            var btn = t.closest('.pf-add');
            strip.querySelectorAll('.pf-new').forEach(function (n) { n.remove(); });
            var files = Array.prototype.slice.call(t.files || []);
            var room = Number(btn.dataset.room);
            if (files.length > room) {
                var msg = 'You can add ' + room + ' more ' + (room === 1 ? 'photo' : 'photos') + ' here. Pick fewer.';
                if (typeof window.bbAlert === 'function') window.bbAlert(msg); else window.alert(msg);
                t.value = '';
                files = [];
            }
            btn.querySelector('[data-pf-add-text]').textContent = files.length ? files.length + ' to upload' : 'Add photos';
            btn.classList.toggle('has-files', files.length > 0);
            var empty = strip.querySelector('.pf-empty');
            if (empty) empty.hidden = files.length > 0;
            files.forEach(function (f) {
                var el = document.createElement('div');
                el.className = 'pf-photo pf-new';
                el.innerHTML = '<div class="pf-img"><img alt=""></div><span class="pf-tag">New</span>';
                el.querySelector('img').src = URL.createObjectURL(f);
                strip.appendChild(el);
            });
        }
    });
})();
</script>
