<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Promo Banners — BoomBuy Admin'])

    <link rel="stylesheet" href="{{ vasset('css/admin-sidebar.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/admin-settings.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/pages/admin-banners.css') }}">
</head>

<body>

@php
    $f = $editing;
    $val = fn ($field, $default = '') => old($field, $f ? $f->{$field} : $default);
    $linkType = old('link_type', $f->link_type ?? 'products');
    $linkValue = $f->link_value ?? null;
    $statusClass = ['Live' => 'is-live', 'Scheduled' => 'is-scheduled', 'Ended' => 'is-ended', 'Off' => 'is-off'];
    $linkLabel = function ($b) use ($products, $shops) {
        return match ($b->link_type) {
            'deals' => 'On sale',
            'category' => \App\Support\Categories::LIST[$b->link_value] ?? 'Category',
            'product' => 'Product: ' . ($products->firstWhere('id', (int) $b->link_value)->name ?? '#' . $b->link_value),
            'shop' => 'Shop: ' . ($shops->get((int) $b->link_value)['name'] ?? '#' . $b->link_value),
            default => 'All products',
        };
    };
    $dates = function ($b) {
        if (!$b->starts_at && !$b->ends_at) return 'No end date';
        $from = $b->starts_at ? $b->starts_at->format('M j') : 'Now';
        $to = $b->ends_at ? $b->ends_at->format('M j, Y') : 'no end';
        return $from . ' – ' . $to;
    };
@endphp

<div class="layout">

    <x-layout.admin-sidebar active="banners" />

    <main class="main">

    <div class="container bn-container">

        <div class="header">
            <h1>Promo Banners</h1>
            <p>What buyers see at the top of the landing page and their home: the big rotating banner and the two tiles beside it. A spot with no live banner shows automatic slides (products with photos, free shipping, deals).</p>
        </div>

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

        <div class="card">
            <h2><i class="bi bi-eye-fill"></i> Live preview</h2>
            <p class="desc">Exactly what the buyer home shows right now.</p>
            <div class="bn-preview">
                @include('partials.promo-hero', ['layout' => 'wide', 'withAnnouncement' => true])
            </div>
        </div>

        <div class="bn-grid">

            <div class="card">
                <h2><i class="bi bi-images"></i> Banners</h2>
                <p class="desc">In carousel order. Only one banner shows in each side tile: the first live one.</p>

                <div class="bn-list">
                    @forelse($banners as $b)
                        @php $status = $b->status(); @endphp
                        <div class="bn-item {{ $f && $f->id === $b->id ? 'is-editing' : '' }}">
                            <div class="bn-thumb ph-{{ $b->style }}">
                                @if($b->imageUrl())
                                    <img src="{{ $b->imageUrl() }}" alt="" onerror="this.remove()">
                                @else
                                    <span @class(['bn-thumb-title' => !$b->big_text])>{{ $b->big_text ?: \Illuminate\Support\Str::limit($b->title, 24) }}</span>
                                @endif
                            </div>
                            <div class="bn-info">
                                <strong>{{ $b->title }}</strong>
                                <small>{{ \App\Models\PromoBanner::PLACEMENTS[$b->placement] ?? $b->placement }} · {{ $linkLabel($b) }} · {{ $dates($b) }}</small>
                                <span class="bn-pill {{ $statusClass[$status] }}">{{ $status }}</span>
                            </div>
                            <div class="bn-actions">
                                <a href="{{ route('admin.banners', ['edit' => $b->id]) }}#banner-form" class="mini-btn toggle">Edit</a>
                                <form method="POST" action="{{ route('admin.banners.toggle', $b->id) }}">
                                    @csrf
                                    <button type="submit" class="mini-btn toggle">{{ $b->is_active ? 'Turn off' : 'Turn on' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.banners.delete', $b->id) }}" data-confirm="Delete the banner &quot;{{ $b->title }}&quot;?" data-confirm-ok="Delete" data-confirm-danger>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mini-btn delete">Delete</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="empty-note">No banners yet. The hero is showing automatic slides.</div>
                    @endforelse
                </div>
            </div>

            <div class="card" id="banner-form">
                <h2><i class="bi {{ $f ? 'bi-pencil-square' : 'bi-plus-circle-fill' }}"></i> {{ $f ? 'Edit banner' : 'Add a banner' }}</h2>
                <p class="desc">{{ $f ? 'Changes show on the site as soon as you save.' : 'It shows on the site on the start date (or right away when empty).' }}</p>

                <form method="POST" action="{{ $f ? route('admin.banners.update', $f->id) : route('admin.banners.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="bn-two">
                        <div class="form-group">
                            <label for="placement">Where</label>
                            <select id="placement" name="placement">
                                @foreach(\App\Models\PromoBanner::PLACEMENTS as $key => $label)
                                    <option value="{{ $key }}" @selected($val('placement', 'main') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="style">Color</label>
                            <select id="style" name="style">
                                @foreach(\App\Models\PromoBanner::STYLES as $key => $label)
                                    <option value="{{ $key }}" @selected($val('style', 'violet') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="eyebrow">Small line above the title <span class="bn-opt">optional</span></label>
                        <input type="text" id="eyebrow" name="eyebrow" maxlength="60" value="{{ $val('eyebrow') }}" placeholder="e.g. Nov 1 – 11 · Mega Sale">
                    </div>

                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" maxlength="80" value="{{ $val('title') }}" placeholder="e.g. Pinakamalaking sale ng taon." required>
                    </div>

                    <div class="form-group">
                        <label for="subtitle">Text <span class="bn-opt">optional</span></label>
                        <textarea id="subtitle" name="subtitle" maxlength="200" placeholder="e.g. Up to 50% off sa Electronics, Fashion at Home &amp; Living.">{{ $val('subtitle') }}</textarea>
                    </div>

                    <div class="bn-two">
                        <div class="form-group">
                            <label for="big_text">Big text <span class="bn-opt">optional</span></label>
                            <input type="text" id="big_text" name="big_text" maxlength="12" value="{{ $val('big_text') }}" placeholder="e.g. 11.11 or -40%">
                        </div>
                        <div class="form-group">
                            <label for="button_label">Button text <span class="bn-opt">optional</span></label>
                            <input type="text" id="button_label" name="button_label" maxlength="30" value="{{ $val('button_label') }}" placeholder="Shop now">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="link_type">Button goes to</label>
                        <select id="link_type" name="link_type" data-bn-link>
                            @foreach(\App\Models\PromoBanner::LINK_TYPES as $key => $label)
                                <option value="{{ $key }}" @selected($linkType === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" data-bn-for="category">
                        <label for="link_category">Category</label>
                        <select id="link_category" name="link_category">
                            <option value="">Choose a category</option>
                            @foreach(\App\Support\Categories::LIST as $slug => $label)
                                <option value="{{ $slug }}" @selected(old('link_category', $linkType === 'category' ? $linkValue : null) === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" data-bn-for="product">
                        <label for="link_product">Product</label>
                        <select id="link_product" name="link_product">
                            <option value="">Choose a product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" @selected((string) old('link_product', $linkType === 'product' ? $linkValue : null) === (string) $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" data-bn-for="shop">
                        <label for="link_shop">Shop</label>
                        <select id="link_shop" name="link_shop">
                            <option value="">Choose a shop</option>
                            @foreach($shops as $sellerId => $shop)
                                <option value="{{ $sellerId }}" @selected((string) old('link_shop', $linkType === 'shop' ? $linkValue : null) === (string) $sellerId)>{{ $shop['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="image">Picture <span class="bn-opt">optional · shows on the right, e.g. a product photo</span></label>
                        @include('partials.file-picker', [
                            'id' => 'image',
                            'name' => 'image',
                            'label' => 'Choose a picture',
                            'accept' => '.jpg,.jpeg,.png,.webp',
                            'hint' => 'JPG, PNG or WEBP · up to 4 MB',
                        ])
                        @if($f && $f->imageUrl())
                            <label class="bn-current">
                                <img src="{{ $f->imageUrl() }}" alt="Current picture">
                                <span><input type="checkbox" name="remove_image" value="1"> Remove the current picture</span>
                            </label>
                        @endif
                    </div>

                    <div class="bn-two">
                        <div class="form-group">
                            <label for="starts_at">Starts <span class="bn-opt">empty = now</span></label>
                            <input type="date" id="starts_at" name="starts_at" value="{{ old('starts_at', $f && $f->starts_at ? $f->starts_at->toDateString() : '') }}">
                        </div>
                        <div class="form-group">
                            <label for="ends_at">Ends <span class="bn-opt">empty = no end</span></label>
                            <input type="date" id="ends_at" name="ends_at" value="{{ old('ends_at', $f && $f->ends_at ? $f->ends_at->toDateString() : '') }}">
                        </div>
                    </div>

                    <div class="form-group bn-order">
                        <label for="sort_order">Order in the carousel <span class="bn-opt">lower shows first</span></label>
                        <input type="number" id="sort_order" name="sort_order" min="0" max="999" value="{{ $val('sort_order', 0) }}">
                    </div>

                    <div class="bn-buttons">
                        <button type="submit" class="save-btn">{{ $f ? 'Save changes' : 'Save banner' }}</button>
                        @if($f)
                            <a href="{{ route('admin.banners') }}" class="bn-cancel">Cancel</a>
                        @endif
                    </div>
                </form>
            </div>

        </div>

    </div>

    </main>

</div>

<script>
    // Show only the picker that matches "Button goes to".
    (function () {
        var select = document.querySelector('[data-bn-link]');
        if (!select) return;
        function sync() {
            document.querySelectorAll('[data-bn-for]').forEach(function (group) {
                group.hidden = group.getAttribute('data-bn-for') !== select.value;
            });
        }
        select.addEventListener('change', sync);
        sync();
    })();
</script>

    @include('partials.pwa-register')

</body>
</html>
