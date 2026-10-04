{{--
    The page title on every seller page: "Seller Panel", breadcrumb, title,
    one line of explanation, and room for buttons on the right.

    <x-seller-page-head title="Orders" subtitle="…" :crumbs="['My Products' => route('seller.dashboard')]">
        <a href="…" class="…">Print</a>   ← optional actions
    </x-seller-page-head>
--}}
@props(['title', 'subtitle' => null, 'crumbs' => []])

@once
    <link rel="stylesheet" href="{{ vasset('css/partials/seller-page-head.css') }}">
@endonce

<header class="sp-head">
    <div class="sp-head-text">
        <div class="sp-head-eyebrow">
            <span>Seller Panel</span>
            @foreach($crumbs as $crumbLabel => $crumbUrl)
                <span class="sep">/</span>
                <a href="{{ $crumbUrl }}">{{ $crumbLabel }}</a>
            @endforeach
        </div>
        <h1>{{ $title }}</h1>
        @if($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if(trim($slot) !== '')
        <div class="sp-head-actions">{{ $slot }}</div>
    @endif
</header>
