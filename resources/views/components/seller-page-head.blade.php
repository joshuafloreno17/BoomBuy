{{--
    The page title on every seller page: "Seller Panel", breadcrumb, title,
    one line of explanation, and room for buttons on the right.

    <x-seller-page-head title="Orders" subtitle="…" :crumbs="['My Products' => route('seller.dashboard')]">
        <a href="…" class="…">Print</a>   ← optional actions
    </x-seller-page-head>

    On phones (≤760px, seller-mobile.css) the same header turns into the dark
    band with a back button. `back` overrides where it goes (false = no button,
    for the dock tabs); `group` overrides the small label above the title.
--}}
@props(['title', 'subtitle' => null, 'crumbs' => [], 'back' => null, 'group' => null])

@once
    <link rel="stylesheet" href="{{ vasset('css/partials/seller-page-head.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/seller-mobile.css') }}">
    <script>
        // Phones: a create form marked data-sm-sheet opens as a full-screen sheet from its
        // bottom-bar button, and opens by itself when it came back with errors.
        (function () {
            function sheet(name) { return document.querySelector('[data-sm-sheet="' + name + '"]'); }
            function setOpen(el, on) {
                if (!el) return;
                el.classList.toggle('is-open', on);
                document.body.classList.toggle('sm-sheet-open', on);
                if (on) {
                    el.scrollTop = 0;
                    var first = el.querySelector('input:not([type=hidden]), select, textarea');
                    if (first && window.matchMedia('(min-width: 761px)').matches === false) first.focus({ preventScroll: true });
                }
            }
            document.addEventListener('click', function (e) {
                var open = e.target.closest('[data-sm-sheet-open]');
                if (open) { e.preventDefault(); setOpen(sheet(open.getAttribute('data-sm-sheet-open')), true); return; }
                var close = e.target.closest('[data-sm-sheet-close]');
                if (close) { e.preventDefault(); setOpen(close.closest('[data-sm-sheet]'), false); return; }
                // "Show all" under a list that phones cut short (data-sm-limit)
                var more = e.target.closest('[data-sm-more]');
                if (more) {
                    var box = more.closest('[data-sm-limit]');
                    var all = box && box.classList.toggle('is-all');
                    more.textContent = more.getAttribute(all ? 'data-less' : 'data-more');
                }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                var el = document.querySelector('[data-sm-sheet].is-open');
                if (el) setOpen(el, false);
            });
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-sm-sheet]').forEach(function (el) {
                    if (el.querySelector('.field-error, .error-text, .invalid-feedback, .is-invalid')) setOpen(el, true);
                });
            });
        })();
    </script>
@endonce

@php
    $isTab = request()->routeIs('seller.orders', 'seller.products');

    $phoneGroup = $group ?? match (true) {
        request()->routeIs('seller.products*', 'seller.variations*') => 'Products',
        request()->routeIs('seller.order*') => 'Orders',
        request()->routeIs('seller.reports', 'seller.earnings*', 'seller.reviews*') => 'Grow',
        request()->routeIs('seller.notifications*', 'seller.profile*') => 'Account',
        default => 'Shop',
    };

    $phoneBack = $back ?? ($isTab ? false : match (true) {
        request()->routeIs('seller.products.*', 'seller.variations*') => route('seller.products'),
        request()->routeIs('seller.order.*') => route('seller.orders'),
        default => route('seller.dashboard'),
    });
@endphp

<header class="sp-head {{ $isTab ? 'is-tab' : '' }}">
    @if($phoneBack)
        <a href="{{ $phoneBack }}" class="sp-back sm-only" aria-label="Back"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
    @endif

    <div class="sp-head-text">
        <div class="sp-head-eyebrow">
            <span>Seller Panel</span>
            @foreach($crumbs as $crumbLabel => $crumbUrl)
                <span class="sep">/</span>
                <a href="{{ $crumbUrl }}">{{ $crumbLabel }}</a>
            @endforeach
        </div>
        <span class="sp-kicker sm-only">{{ $phoneGroup }}</span>
        <h1>{{ $title }}</h1>
        @if($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if(trim($slot) !== '')
        <div class="sp-head-actions">{{ $slot }}</div>
    @endif
</header>
