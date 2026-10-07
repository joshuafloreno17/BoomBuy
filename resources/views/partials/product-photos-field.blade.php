{{--
    Photos on Edit Product, in groups: each option's own photos, then the
    photos for all options. The cover and each option's button photo are
    picked automatically (App\Support\ProductPhotos::sync).

    @include('partials.product-photos-field', ['product' => $product])
--}}
@php
    $images = $product->images()->get();
    $options = $product->variations()->orderBy('id')->get();
    $groups = $options->map(fn ($o) => [
        'title' => 'Photos for ' . $o->shortLabel(),
        'note' => 'The first one shows on the “' . $o->shortLabel() . '” button.',
        'photos' => $images->where('product_variation_id', $o->id),
        'room' => \App\Support\ProductPhotos::MAX_PER_OPTION - $images->where('product_variation_id', $o->id)->count(),
        'max' => \App\Support\ProductPhotos::MAX_PER_OPTION,
        'input' => 'option_photos[' . $o->id . '][]',
        'id' => 'optionPhotos' . $o->id,
    ])->push([
        'title' => $options->isNotEmpty() ? 'Photos for all options' : 'Product Photos',
        'note' => $options->isNotEmpty() ? 'Shown whatever option the buyer picks: box contents, size chart, details.' : 'The first one is the cover buyers see on the Shop page.',
        'photos' => $images->whereNull('product_variation_id'),
        'room' => \App\Support\ProductPhotos::MAX_GENERAL - $images->whereNull('product_variation_id')->count(),
        'max' => \App\Support\ProductPhotos::MAX_GENERAL,
        'input' => 'photos[]',
        'id' => 'photos',
    ]);
@endphp

@once
<style>
    .pg-group { margin-bottom: 18px; }
    .pg-title { font-weight: 700; font-size: 13.5px; }
    .pg-title small { font-weight: 500; color: #8a7f86; }
    .pg-note { margin: 2px 0 8px; font-size: 12.5px; color: #8a7f86; }
    .pp-grid { display: flex; flex-wrap: wrap; gap: 10px; margin: 4px 0 10px; }
    .pp-item { position: relative; width: 96px; display: flex; flex-direction: column; gap: 6px; }
    .pp-thumb { width: 96px; height: 96px; border-radius: 12px; overflow: hidden; border: 1px solid #f0e2da; background: #fff8f3; }
    .pp-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pp-item.is-cover .pp-thumb { border: 2px solid #e8420f; }
    .pp-badge { position: absolute; top: 6px; left: 6px; padding: 2px 7px; border-radius: 999px; background: #e8420f; color: #fff; font-size: 10.5px; font-weight: 800; letter-spacing: .03em; }
    .pp-opt.pp-opt { display: flex; align-items: center; gap: 6px; margin: 0; font-size: 12px; font-weight: 600; color: #6b6570; cursor: pointer; }
    .pp-opt input { width: 15px; height: 15px; margin: 0; accent-color: #e8420f; }
    .pp-item.is-removed .pp-thumb { opacity: .35; }
    .pp-hint { margin: 6px 0 0; font-size: 12.5px; color: #8a7f86; }
</style>
@endonce

<div class="form-group">
    <label>Photos</label>
    <p class="pp-hint" style="margin:0 0 12px">
        Pick any photo as the <b>Shop cover</b> (the thumbnail on the Shop page). Without a pick it is {{ $options->isNotEmpty() ? 'the first option\'s first photo.' : 'the first photo.' }}
        Tick “Remove” to take a photo off when you save.
    </p>

    @foreach($groups as $group)
        <div class="pg-group">
            <div class="pg-title">{{ $group['title'] }} <small>· {{ $group['photos']->count() }}/{{ $group['max'] }}</small></div>
            <p class="pg-note">{{ $group['note'] }}</p>

            @if($group['photos']->isNotEmpty())
                <div class="pp-grid">
                    @foreach($group['photos'] as $photo)
                        @php $isCover = $photo->path === $product->image; @endphp
                        <div class="pp-item {{ $isCover ? 'is-cover' : '' }}" data-pp-item>
                            <div class="pp-thumb"><img src="{{ $photo->url() }}" alt=""></div>
                            @if($isCover)<span class="pp-badge">Cover</span>@endif
                            <label class="pp-opt"><input type="radio" name="cover_photo" value="{{ $photo->id }}" @checked($isCover)> Shop cover</label>
                            @unless($loop->first)
                                <label class="pp-opt"><input type="radio" name="first_photo" value="{{ $photo->id }}"> Move first</label>
                            @endunless
                            <label class="pp-opt"><input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}" data-pp-remove> Remove</label>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($group['room'] > 0)
                @include('partials.file-picker', [
                    'id' => $group['id'],
                    'name' => $group['input'],
                    'multiple' => true,
                    'accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp',
                    'label' => 'Add photos',
                    'hint' => 'Up to ' . $group['room'] . ' more · JPG, PNG or WEBP, up to 5 MB each',
                ])
            @else
                <p class="pp-hint">This group is full. Remove one to add another.</p>
            @endif
        </div>
    @endforeach
</div>

@once
<script>
(function () {
    document.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('[data-pp-remove]')) {
            e.target.closest('[data-pp-item]').classList.toggle('is-removed', e.target.checked);
        }
    });
})();
</script>
@endonce
