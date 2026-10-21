{{-- Phone tab bar: the bubble slides from the tab you tapped on the last page to
     this page's tab. Included right after the <nav class="bb-dock"> so it runs
     before the first paint. window.bbDock.reset() puts it back (after "More"). --}}
<script>
(function () {
    var dock = document.querySelector('.bb-dock');
    if (!dock) return;

    var KEY = 'bbDockFrom';
    var tabs = Array.prototype.slice.call(dock.querySelectorAll('[data-dock-tab]'));
    var here = -1;
    tabs.forEach(function (tab, i) { if (tab.classList.contains('active')) here = i; });

    function place(i) {
        if (i >= 0) dock.style.setProperty('--dock-i', i);
        dock.classList.toggle('has-bubble', i >= 0);
        tabs.forEach(function (tab, n) { tab.classList.toggle('is-lift', n === i); });
    }

    function jumpTo(i) {
        dock.classList.add('no-anim');
        place(i);
        void dock.offsetWidth;
        dock.classList.remove('no-anim');
    }

    var from = null;
    try {
        from = JSON.parse(sessionStorage.getItem(KEY) || 'null');
        sessionStorage.removeItem(KEY);
    } catch (e) { /* private mode: no slide, the bar still works */ }

    var cameFrom = from && from.dock === dock.dataset.dock && Date.now() - from.at < 15000 ? from.i : null;

    if (cameFrom !== null && cameFrom !== here && here >= 0) {
        // Start where the last page's bubble was, then slide (or pop in) on the next frame.
        jumpTo(cameFrom);
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { place(here); });
        });
    }

    tabs.forEach(function (tab, n) {
        tab.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey) return;

            // Remember where the bubble is now; the next page slides it over once it has loaded.
            var at = -1;
            tabs.forEach(function (t, k) { if (t.classList.contains('is-lift')) at = k; });

            if (tab.tagName === 'A') {
                try {
                    sessionStorage.setItem(KEY, JSON.stringify({ dock: dock.dataset.dock, i: at, at: Date.now() }));
                } catch (err) { /* ignore */ }
            } else {
                place(n); // "More" opens a sheet on this page
            }
        });
    });

    // Back/forward cache: show this page's tab again (e.g. if "More" was left open).
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) jumpTo(here);
    });

    window.bbDock = { reset: function () { place(here); } };
})();
</script>
