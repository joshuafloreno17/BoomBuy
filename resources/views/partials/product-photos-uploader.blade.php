{{--
    "Product Photos" on Add Product: one place for every photo of the product.
    Up to MAX_PHOTOS; the first one is the cover (Shop cards, main photo), the
    rest show as thumbnails. Sends them as photos[] in that order.

    @include('partials.product-photos-uploader')
--}}
@php $maxPhotos = \App\Support\ProductPhotos::MAX_GENERAL; @endphp

<style>
    .pu-grid { display: flex; flex-wrap: wrap; gap: 12px; }
    .pu-tile { position: relative; width: 112px; display: flex; flex-direction: column; gap: 6px; }
    .pu-thumb { width: 112px; height: 112px; border-radius: 14px; overflow: hidden; border: 1px solid #f0e2da; background: #fff8f3; }
    .pu-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pu-tile.is-cover .pu-thumb { border: 2px solid #e8420f; }
    .pu-badge { position: absolute; top: 7px; left: 7px; padding: 2px 8px; border-radius: 999px; background: #e8420f; color: #fff; font-size: 10.5px; font-weight: 800; letter-spacing: .03em; }
    .pu-cover-btn { padding: 4px 0; border: 0; background: none; color: #c2380f; font-family: inherit; font-size: 12px; font-weight: 700; cursor: pointer; text-align: left; }
    .pu-cover-btn:hover { text-decoration: underline; }
    .pu-remove { position: absolute; top: -8px; right: -8px; width: 26px; height: 26px; display: grid; place-items: center; padding: 0; border: 2px solid #fff; border-radius: 50%; background: #1b1a1f; color: #fff; font-size: 11px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,.18); }
    .pu-remove:hover { background: #dc2626; }
    .pu-add.pu-add { width: 112px; height: 112px; margin: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; border: 1.5px dashed #ecc9bd; border-radius: 14px; background: #fff8f3; color: #e8420f; font-size: 12.5px; font-weight: 700; text-align: center; cursor: pointer; }
    .pu-add:hover, .pu-add:focus-within { border-color: #e8420f; background: #fff1ea; }
    .pu-add i { font-size: 22px; }
    .pu-add small { color: #8a7f86; font-weight: 600; font-size: 11px; }
    .pu-add input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .pu-hint { margin: 8px 0 0; font-size: 12.5px; color: #8a7f86; }
</style>

<style>
    .cv-badge { position: absolute; top: 6px; left: 6px; z-index: 1; padding: 2px 8px; border-radius: 999px; background: #e8420f; color: #fff; font-size: 10.5px; font-weight: 800; letter-spacing: .03em; pointer-events: none; }
    .cv-set { position: absolute; left: 50%; bottom: 4px; transform: translateX(-50%); z-index: 1; padding: 2px 7px; white-space: nowrap; border: 0; border-radius: 999px; background: rgba(255,255,255,.92); color: #c2380f; font-family: inherit; font-size: 10.5px; font-weight: 800; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,.15); }
    .cv-set:hover { background: #e8420f; color: #fff; transform: translateX(-50%); }
    .cv-on .pu-thumb, .var-photo-tile.cv-on { outline: 2px solid #e8420f; outline-offset: 1px; }
</style>

{{-- The Shop cover pick, sent as "all:N" or "opt:ROW:N"; empty = picked automatically. --}}
<input type="hidden" name="cover_ref" id="coverRef" value="">

<script>
// Shop cover on Add Product: the seller's pick, else the first option's first
// photo, else the first photo for all options. Tiles carry data-uid/data-ref.
window.bbCover = (function () {
    var picked = '';
    var counter = 0;

    function uid(file) {
        if (!file.__bbUid) file.__bbUid = 'p' + (++counter);
        return file.__bbUid;
    }

    function tiles() {
        return Array.prototype.slice.call(document.querySelectorAll('.opt-card .var-photo-tile'))
            .concat(Array.prototype.slice.call(document.querySelectorAll('.pu-tile')));
    }

    function refresh() {
        var all = tiles();
        var chosen = all.find(function (t) { return t.dataset.uid === picked; });
        if (!chosen) picked = '';
        var cover = chosen || all[0];

        all.forEach(function (t) {
            var on = t === cover;
            t.classList.toggle('cv-on', on);
            t.querySelector('.cv-badge').hidden = !on;
            t.querySelector('.cv-set').hidden = on;
        });

        document.getElementById('coverRef').value = chosen ? chosen.dataset.ref : '';
    }

    function decorate(tile, file, ref) {
        tile.dataset.uid = uid(file);
        tile.dataset.ref = ref;
        tile.style.position = 'relative';

        var badge = document.createElement('span');
        badge.className = 'cv-badge';
        badge.textContent = 'Cover';
        badge.hidden = true;

        var set = document.createElement('button');
        set.type = 'button';
        set.className = 'cv-set';
        set.textContent = 'Set cover';
        set.title = 'Use this photo as the Shop thumbnail';
        set.addEventListener('click', function () { picked = tile.dataset.uid; refresh(); });

        tile.appendChild(badge);
        tile.appendChild(set);
    }

    return { decorate: decorate, refresh: refresh };
})();
</script>
<div class="form-group">
    <label for="photoPicker"><span id="photoTitle">Product Photos</span> <span style="font-weight:500; color:#8a7f86;">(up to {{ $maxPhotos }})</span></label>

    {{-- What gets submitted, kept in the order shown (first = cover). --}}
    <input type="file" name="photos[]" id="photosField" multiple hidden>

    <div class="pu-grid" id="photoGrid" data-max="{{ $maxPhotos }}">
        <label class="pu-add" id="photoAdd">
            <input type="file" id="photoPicker" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
            <i class="bi bi-camera-fill"></i>
            Add photos
            <small id="photoCount">0 / {{ $maxPhotos }}</small>
        </label>
    </div>

    <p class="pu-hint" id="photoHint">The first photo is the <b>cover</b> buyers see on the Shop page. JPG, PNG or WEBP, up to 5 MB each.</p>
</div>

<script>
(function () {
    var grid = document.getElementById('photoGrid');
    var addTile = document.getElementById('photoAdd');
    var picker = document.getElementById('photoPicker');
    var field = document.getElementById('photosField');
    var count = document.getElementById('photoCount');
    var max = parseInt(grid.dataset.max, 10) || 8;
    var files = [];
    var say = window.bbAlert || window.alert;

    function sync() {
        var dt = new DataTransfer();
        files.forEach(function (f) { dt.items.add(f); });
        field.files = dt.files;
    }

    function render() {
        grid.querySelectorAll('.pu-tile').forEach(function (t) { t.remove(); });

        files.forEach(function (file, i) {
            var tile = document.createElement('div');
            tile.className = 'pu-tile';

            var thumb = document.createElement('div');
            thumb.className = 'pu-thumb';
            var img = document.createElement('img');
            img.alt = '';
            img.src = URL.createObjectURL(file);
            thumb.appendChild(img);
            tile.appendChild(thumb);

            window.bbCover.decorate(tile, file, 'all:' + i);

            if (i > 0) {
                var cover = document.createElement('button');
                cover.type = 'button';
                cover.className = 'pu-cover-btn';
                cover.textContent = 'Move first';
                cover.addEventListener('click', function () {
                    files.unshift(files.splice(i, 1)[0]);
                    sync(); render();
                });
                tile.appendChild(cover);
            }

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'pu-remove';
            remove.title = 'Remove';
            remove.setAttribute('aria-label', 'Remove this photo');
            remove.innerHTML = '<i class="bi bi-x-lg"></i>';
            remove.addEventListener('click', function () {
                files.splice(i, 1);
                sync(); render();
            });
            tile.appendChild(remove);

            grid.insertBefore(tile, addTile);
        });

        addTile.hidden = files.length >= max;
        count.textContent = files.length + ' / ' + max;
        window.bbCover.refresh();
    }

    picker.addEventListener('change', function () {
        var picked = Array.prototype.slice.call(picker.files || []);
        picker.value = '';
        var skipped = 0;

        picked.forEach(function (file) {
            if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size > 5 * 1024 * 1024) { skipped++; return; }
            if (files.length < max) files.push(file); else skipped++;
        });

        if (skipped) say(skipped + ' photo' + (skipped === 1 ? ' was' : 's were') + ' not added. Use JPG, PNG or WEBP up to 5 MB, and up to ' + max + ' photos in all.');
        sync(); render();
    });

    // With option photos, the cover is the first option's first photo.
    function optionsHavePhotos() {
        return Array.prototype.some.call(document.querySelectorAll('.var-photos-field'), function (f) { return f.files && f.files.length > 0; });
    }
    document.addEventListener('bb:option-photos', window.bbCover.refresh);

    // With options, these are the photos every option shares.
    document.addEventListener('bb:options-changed', function () {
        var any = document.querySelectorAll('.opt-card').length > 0;
        document.getElementById('photoTitle').textContent = any ? 'Photos for all options' : 'Product Photos';
        document.getElementById('photoHint').innerHTML = any
            ? 'Shown whatever option the buyer picks: what comes in the box, the size, details. Use <b>Set cover</b> on any photo to choose the Shop thumbnail.'
            : 'The first photo is the <b>cover</b> buyers see on the Shop page. JPG, PNG or WEBP, up to 5 MB each.';
    });

    // A product needs at least one photo, here or under an option.
    field.form.addEventListener('submit', function (e) {
        if (files.length === 0 && !optionsHavePhotos()) {
            e.preventDefault();
            say('Please add at least one product photo, here or under an option.');
            addTile.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    render();
})();
</script>
