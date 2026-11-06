{{--
    The first page after signing in crossfades in from the login page's "Boom!".
    Only once: the login flashes `bb_build_in`, so a refresh or the next page is
    normal again. Included by partials.head; skipped for "reduce motion".

    Browsers without page-to-page view transitions get a short fade instead.
    Nothing here measures or touches the page's elements, so it costs the page
    no work while it is loading.
--}}
@if (session('bb_build_in'))
<script>
    if (!('PageRevealEvent' in window) && !(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) {
        document.documentElement.classList.add('bb-build');
    }
</script>
<style>
    @media (prefers-reduced-motion: no-preference) {
        @view-transition {
            navigation: auto;
        }

        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation-duration: .3s;
        }
    }

    html.bb-build main {
        animation: bbFade .25s ease-out backwards;
    }

    @keyframes bbFade {
        from { opacity: 0; }
    }
</style>
@endif
