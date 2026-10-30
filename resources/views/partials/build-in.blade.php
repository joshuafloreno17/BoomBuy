{{--
    The first page after signing in "builds itself in": the violet cover (the
    login orb that just filled the screen) fades away and the page's cards
    come in one by one, row by row, quickly. Only once — the login flashes
    `bb_build_in`, so a refresh or the next page is normal again.
    Included by partials.head; skipped for "reduce motion".

    The content stays hidden under the cover until the cards are picked, so
    nothing shows, vanishes and comes back. If the script fails, it shows
    anyway after 1.5 s.
--}}
@if (session('bb_build_in'))
<script>
    if (!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) {
        document.documentElement.classList.add('bb-build');
    }
</script>
<style>
    html.bb-build body::before {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 99999;
        pointer-events: none;
        background: radial-gradient(circle at 50% 45%, #8b6cf0 0%, #5533c4 55%, #24125e 100%);
        transition: opacity .3s ease, visibility 0s .3s;
    }

    html.bb-build.bb-go body::before {
        opacity: 0;
        visibility: hidden;
    }

    html.bb-build:not(.bb-go) main,
    html.bb-build:not(.bb-go) [class*="sidebar"] a {
        opacity: 0;
        animation: bbSafety 0s 1.5s forwards;
    }

    .bb-rise {
        animation: bbRise .4s cubic-bezier(.2, .8, .2, 1) both;
    }

    .bb-slide {
        animation: bbSlide .3s cubic-bezier(.2, .8, .2, 1) both;
    }

    @keyframes bbSafety {
        to { opacity: 1; }
    }

    @keyframes bbRise {
        from { opacity: 0; transform: translateY(16px) scale(.98); }
        to { opacity: 1; transform: none; }
    }

    @keyframes bbSlide {
        from { opacity: 0; transform: translateX(-12px); }
        to { opacity: 1; transform: none; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.documentElement;
        if (!root.classList.contains('bb-build')) return;

        var main = document.querySelector('main');
        var view = window.innerHeight;
        var blocks = [];

        function shown(el) {
            return !el.matches('script, style, template, [hidden]') && el.getClientRects().length > 0;
        }

        // A card: a box with its own background or border and round corners,
        // big enough not to be a button or a badge. Works on every dashboard
        // without listing their class names. Measured once, in one pass.
        function isCard(el) {
            if (el.matches('button, input, select, textarea, label, img, svg, canvas, video, iframe')) return false;
            var r = el.getBoundingClientRect();
            if (r.width < 100 || r.height < 60) return false;
            var s = getComputedStyle(el);
            var filled = s.backgroundColor !== 'rgba(0, 0, 0, 0)' && s.backgroundColor !== 'transparent'
                || s.backgroundImage !== 'none';
            var edged = parseFloat(s.borderTopWidth) > 0 || s.boxShadow !== 'none';
            return (filled || edged) && parseFloat(s.borderTopLeftRadius) >= 8;
        }

        var cards = main ? Array.prototype.filter.call(main.querySelectorAll('*'), function (el) {
            return shown(el) && isCard(el);
        }) : [];

        function hasCard(el) {
            return cards.some(function (card) { return card !== el && el.contains(card); });
        }

        // The outermost cards come in one by one; a wrapper with no card in it
        // (a heading, a text row) comes in as one piece; other wrappers are
        // opened up to find their cards.
        function walk(el, depth) {
            Array.prototype.forEach.call(el.children, function (child) {
                if (!shown(child)) return;
                if (cards.indexOf(child) !== -1 || depth >= 5 || !hasCard(child)) {
                    blocks.push(child);
                } else {
                    walk(child, depth + 1);
                }
            });
        }

        // The class goes once its own animation is over, so hover lifts work again.
        function play(el, cls, delay) {
            el.classList.add(cls);
            el.style.animationDelay = delay + 's';
            el.addEventListener('animationend', function done(e) {
                if (e.target !== el) return;
                el.classList.remove(cls);
                el.style.animationDelay = '';
                el.removeEventListener('animationend', done);
            });
        }

        if (main) walk(main, 0);

        // Row by row, left to right — the order the eye reads the page.
        var placed = blocks.map(function (el) {
            var r = el.getBoundingClientRect();
            return { el: el, top: Math.round(r.top / 24), left: r.left, onScreen: r.top < view };
        }).sort(function (a, b) {
            return a.top - b.top || a.left - b.left;
        });

        var n = 0;
        placed.forEach(function (p) {
            // Below the fold: no waiting, it is out of sight anyway.
            if (!p.onScreen) return;
            play(p.el, 'bb-rise', 0.08 + Math.min(n++, 18) * 0.05);
        });

        document.querySelectorAll('[class*="sidebar"] a').forEach(function (link, i) {
            if (shown(link)) play(link, 'bb-slide', 0.04 + Math.min(i, 10) * 0.03);
        });

        root.classList.add('bb-go');
    });
</script>
@endif
