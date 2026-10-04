{{--
    Live search for list pages: results update while you type, no page reload.

    Mark the GET search form:
        <form data-live-search data-live-target="#liveStats, #liveResults" ...>
    Every selector in data-live-target is swapped with the same element from
    the freshly rendered page (so counts, tabs and pagination stay in sync).
    Typing waits 300ms; selects apply right away; Enter still works.
    Without JavaScript the form simply submits as before.
--}}
@once
<script>
(function () {
    function setup(form) {
        var targets = (form.getAttribute('data-live-target') || '')
            .split(',')
            .map(function (s) { return s.trim(); })
            .filter(Boolean);

        if (!targets.length) return;

        var timer = null;
        var controller = null;

        function urlFor() {
            var params = new URLSearchParams(new FormData(form));

            // Keep the address bar tidy: drop empty values.
            Array.from(params.keys()).forEach(function (key) {
                if (params.get(key) === '') params.delete(key);
            });

            var query = params.toString();
            return form.getAttribute('action').split('?')[0] + (query ? '?' + query : '');
        }

        function run() {
            var url = urlFor();

            if (controller) controller.abort();
            controller = new AbortController();

            targets.forEach(function (sel) {
                document.querySelectorAll(sel).forEach(function (el) { el.classList.add('bb-live-loading'); });
            });

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: controller.signal
            })
                .then(function (r) {
                    if (!r.ok) throw new Error('search failed');
                    return r.text();
                })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');

                    targets.forEach(function (sel) {
                        var current = document.querySelector(sel);
                        var fresh = doc.querySelector(sel);

                        if (current && fresh) {
                            current.innerHTML = fresh.innerHTML;
                        }
                    });

                    history.replaceState(null, '', url);

                    // Pages with their own widgets inside the results
                    // (e.g. address dropdowns) set them up again on this.
                    document.dispatchEvent(new CustomEvent('bb:live-updated'));
                })
                .catch(function (err) {
                    // A newer keystroke cancelled this one — nothing to do.
                    if (err && err.name === 'AbortError') return;
                    form.submit();
                })
                .finally(function () {
                    targets.forEach(function (sel) {
                        document.querySelectorAll(sel).forEach(function (el) { el.classList.remove('bb-live-loading'); });
                    });
                });
        }

        form.addEventListener('input', function (event) {
            if (event.target.tagName !== 'INPUT') return;
            clearTimeout(timer);
            timer = setTimeout(run, 300);
        });

        form.addEventListener('change', function (event) {
            if (event.target.tagName !== 'SELECT') return;
            clearTimeout(timer);
            run();
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            clearTimeout(timer);
            run();
        });
    }

    function init() {
        document.querySelectorAll('form[data-live-search]').forEach(setup);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
<link rel="stylesheet" href="{{ vasset('css/partials/live-search.css') }}">
@endonce
