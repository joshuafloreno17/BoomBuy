{{--
    What every page's <head> starts with: charset, viewport, title, the PWA
    tags and the design tokens. Pages add their own stylesheets after it.
    Usage: @include('partials.head', ['title' => 'Cart — BoomBuy'])
--}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? 'BoomBuy' }}</title>
@include('partials.pwa-head')
@include('partials.design-tokens')
