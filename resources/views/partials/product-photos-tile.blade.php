{{-- One row of photos on Edit Product (see product-photos-field). --}}
@php
    $room = $r['max'] - $r['photos']->count();
    $isGeneral = $isGeneral ?? false;
@endphp

<div class="pf-row {{ $isGeneral ? 'is-general' : '' }}">
    <div class="pf-row-label">
        <b>{{ $r['label'] }}</b>
        <span>{{ $r['photos']->count() }} of {{ $r['max'] }}</span>
        @if($isGeneral)
            <small>Size chart, box contents, close-ups — shown whatever option the buyer picks.</small>
        @endif
    </div>

    <div class="pf-strip">
        @if($r['photos']->isEmpty() && !empty($r['borrow']))
            <span class="pf-empty pf-borrow">
                <img src="{{ $r['borrow']['url'] }}" alt="">
                <span>Uses <b>{{ $r['borrow']['label'] }}</b> photos<small>Add photos only if this one looks different.</small></span>
            </span>
        @elseif($r['photos']->isEmpty())
            <span class="pf-empty">No photos yet</span>
        @endif

        @foreach($r['photos'] as $photo)
            <div class="pf-photo {{ $photo->path === $product->image ? 'is-cover' : '' }}" data-pf-photo="{{ $photo->id }}">
                <div class="pf-img">
                    <img src="{{ $photo->url() }}" alt="Photo {{ $loop->iteration }} of {{ $r['full'] }}">
                    <span class="pf-badge">Cover</span>
                    <span class="pf-gone">Will be removed<br>on save</span>
                </div>
                <button type="button" class="pf-link pf-cover-link" data-pf-open-cover>Change cover</button>
                @if($loop->first)
                    <span class="pf-tag">First</span>
                @else
                    <label class="pf-link pf-first">
                        <input type="radio" name="first_photo" value="{{ $photo->id }}">
                        <span class="off">Make first</span><span class="on">✓ Will be first<small>tap again to undo</small></span>
                    </label>
                @endif
                <label class="pf-link pf-remove">
                    <input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}" data-pf-remove>
                    <span class="off">Remove</span><span class="on">Undo remove</span>
                </label>
            </div>
        @endforeach
    </div>

    @if($room > 0)
        <label class="pf-add" data-room="{{ $room }}">
            <input type="file" id="{{ $r['id'] }}" name="{{ $r['input'] }}" multiple
                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-pf-add
                   aria-label="Add photos for {{ $r['full'] }}">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span data-pf-add-text>Add photos</span>
        </label>
    @else
        <span class="pf-full">Full — remove one to add another</span>
    @endif
</div>
