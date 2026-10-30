{{--
    What every page's <head> starts with: charset, viewport, title, the PWA
    tags and the design tokens. Pages add their own stylesheets after it.
    Usage: @include('partials.head', ['title' => 'Cart — BoomBuy'])
    'fonts' => true loads Figtree here, in parallel, instead of the slower
    @import inside a stylesheet (login and sign-up pages).
--}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? 'BoomBuy' }}</title>
@if (!empty($fonts))
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800;900&display=swap">
@endif
@include('partials.pwa-head')
@include('partials.design-tokens')
@include('partials.build-in')
@include('partials.instant-feedback')
