{{--
    The page title used across the buyer pages: breadcrumb, big title and a short note.

    @include('partials.page-head', [
        'title' => 'My Orders',
        'note' => '3 orders',                       // optional
        'crumbs' => ['Cart' => route('cart')],      // optional, between Home and this page
        'showCrumbs' => true,                       // optional, false hides the breadcrumb
    ])
--}}
@once
    <link rel="stylesheet" href="{{ vasset('css/partials/page-head.css') }}">
@endonce

<header class="bb-page-head">
    @if($showCrumbs ?? true)
        <nav class="bb-page-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('buyer.dashboard') }}">Home</a> /
            @foreach(($crumbs ?? []) as $crumbLabel => $crumbUrl)
                <a href="{{ $crumbUrl }}">{{ $crumbLabel }}</a> /
            @endforeach
            <span>{{ $title }}</span>
        </nav>
    @endif

    <div class="bb-page-title">
        <h1>{{ $title }}</h1>
        @if(!empty($note))
            <span class="bb-page-note">{{ $note }}</span>
        @endif
    </div>
</header>
