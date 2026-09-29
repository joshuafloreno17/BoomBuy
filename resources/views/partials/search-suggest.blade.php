{{--
    Live search dropdown for any <form data-search-suggest> containing an
    <input name="search">. Used by the buyer navbar and the guest landing
    page. Suggestions come from ShopController::searchSuggestions; recent
    searches are kept per browser in localStorage.
--}}
<style>
    [data-search-suggest] {
        position: relative;
        transition: width 0.2s ease, max-width 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    /* While suggestions are open the search box widens a little, gets a
       focus ring, and the dropdown below it matches its width exactly. */
    [data-search-suggest].is-searching {
        background: #fff;
        border-color: var(--accent, #e8420f);
        box-shadow: 0 0 0 3px rgba(232, 66, 15, 0.12);
    }

    .nav-search.is-searching {
        width: min(380px, 36vw);
    }

    .bb-nav-search.is-searching {
        max-width: 600px;
    }

    @media (max-width: 1100px) {
        .bb-nav-search.is-searching {
            max-width: 420px;
        }
    }

    /* Soft dim over the page (the navbar stays above it). */
    .bb-suggest-backdrop {
        position: fixed;
        inset: 0;
        z-index: 95;

        background: rgba(23, 32, 51, 0.18);

        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .bb-suggest-backdrop.is-open {
        opacity: 1;
        pointer-events: auto;
    }

    .bb-suggest {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        left: -1px;
        right: -1px;
        z-index: 4000;

        background: var(--paper, #fff);
        border: 1px solid var(--line, #f7e5e0);
        border-radius: 14px;
        box-shadow: 0 18px 40px -12px rgba(23, 32, 51, 0.22);

        padding: 8px;
        max-height: 70vh;
        overflow-y: auto;

        text-align: left;
        font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
    }

    .bb-suggest.is-open {
        display: block;
    }

    .bb-suggest-section + .bb-suggest-section {
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid var(--line, #f7e5e0);
    }

    .bb-suggest-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;

        padding: 6px 10px 4px;

        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: var(--muted-2, #a99088);
    }

    .bb-suggest-heading button {
        border: none;
        background: none;
        cursor: pointer;

        font: inherit;
        letter-spacing: 0;
        text-transform: none;
        font-weight: 700;
        color: var(--accent, #e8420f);
    }

    .bb-suggest-item {
        display: flex;
        align-items: center;
        gap: 10px;

        width: 100%;
        padding: 6px 10px;

        border: none;
        border-radius: 10px;
        background: none;
        cursor: pointer;

        font: inherit;
        font-size: 13px;
        color: var(--ink, #172033);
        text-align: left;
        text-decoration: none;
    }

    .bb-suggest-item:hover,
    .bb-suggest-item.is-active {
        background: #fff4ef;
    }

    .bb-suggest-item > i {
        color: var(--muted-2, #a99088);
        font-size: 13px;
        width: 16px;
        text-align: center;
        flex-shrink: 0;
    }

    .bb-suggest-label {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bb-suggest-name {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bb-suggest-label mark {
        background: none;
        color: var(--accent, #e8420f);
        font-weight: 800;
    }

    .bb-suggest-remove {
        border: none;
        background: none;
        cursor: pointer;

        padding: 2px 6px;
        border-radius: 6px;

        color: var(--muted-2, #a99088);
        font-size: 14px;
        line-height: 1;
    }

    .bb-suggest-remove:hover {
        background: #f3ede9;
        color: var(--ink, #172033);
    }

    .bb-suggest-thumb {
        width: 34px;
        height: 34px;
        flex-shrink: 0;

        border-radius: 8px;
        background: #ffefea;
        object-fit: cover;

        display: flex;
        align-items: center;
        justify-content: center;

        color: var(--accent, #e8420f);
        font-size: 15px;
    }

    .bb-suggest-chip i {
        margin-right: 4px;
        color: var(--accent, #e8420f);
        font-size: 11px;
    }

    .bb-suggest-meta {
        display: block;
        font-size: 11px;
        color: var(--muted, #8d6c62);
        margin-top: 1px;
    }

    .bb-suggest-price {
        font-weight: 800;
        color: var(--accent, #e8420f);
        font-size: 12.5px;
        white-space: nowrap;
    }

    .bb-suggest-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 4px 8px 8px;
    }

    .bb-suggest-chip {
        display: inline-flex;
        align-items: center;
        padding: 5px 11px;

        border: 1px solid var(--line, #f7e5e0);
        border-radius: 999px;
        background: #fffaf8;

        font-size: 12px;
        font-weight: 600;
        color: var(--ink, #172033);
        text-decoration: none;
    }

    .bb-suggest-chip:hover,
    .bb-suggest-chip.is-active {
        border-color: var(--accent, #e8420f);
        color: var(--accent, #e8420f);
        background: #fff4ef;
    }

    .bb-suggest-empty {
        padding: 14px 12px 8px;
        text-align: center;
        font-size: 13px;
        color: var(--ink, #172033);
    }

    .bb-suggest-empty i {
        display: block;
        font-size: 22px;
        color: var(--muted-2, #a99088);
        margin-bottom: 6px;
    }

    .bb-suggest-empty small {
        display: block;
        margin-top: 3px;
        color: var(--muted, #8d6c62);
        font-size: 11.5px;
    }

    .bb-suggest-all {
        font-weight: 700;
        color: var(--accent, #e8420f);
    }

    .bb-suggest-loading {
        padding: 12px;
        font-size: 12px;
        color: var(--muted, #8d6c62);
        text-align: center;
    }

    @media (max-width: 600px) {
        .bb-suggest {
            min-width: 0;
        }
    }
</style>

<script>
(function () {
    // Pages including this partial more than once only need it wired once.
    if (window.__bbSearchSuggest) {
        return;
    }
    window.__bbSearchSuggest = true;

    var ENDPOINT = @json(route('search.suggestions'));
    var RESULTS_URL = @json(route('products'));
    var RECENT_KEY = 'bb_recent_searches';
    var RECENT_MAX = 5;

    /* ---------- recent searches (per browser) ---------- */

    function loadRecent() {
        try {
            var list = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
            return Array.isArray(list) ? list.filter(function (s) { return typeof s === 'string'; }) : [];
        } catch (e) {
            return [];
        }
    }

    function saveRecent(list) {
        try {
            localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, RECENT_MAX)));
        } catch (e) { /* private mode etc. — recent searches are optional */ }
    }

    function rememberSearch(term) {
        term = (term || '').trim();
        if (!term) return;

        var list = loadRecent().filter(function (s) { return s.toLowerCase() !== term.toLowerCase(); });
        list.unshift(term);
        saveRecent(list);
    }

    function forgetSearch(term) {
        saveRecent(loadRecent().filter(function (s) { return s !== term; }));
    }

    /* ---------- small DOM helpers (textContent only — never innerHTML with data) ---------- */

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function icon(name) {
        var i = document.createElement('i');
        i.className = 'bi ' + name;
        return i;
    }

    // Wraps each typed word inside the label with <mark>, built node by node.
    function highlighted(text, query) {
        var span = el('span', 'bb-suggest-name');
        var words = query.toLowerCase().split(/\s+/).filter(Boolean);

        if (!words.length) {
            span.textContent = text;
            return span;
        }

        var lower = text.toLowerCase();
        var marks = new Array(text.length).fill(false);

        // Highlight where a word starts with what was typed ("i" → iPhone,
        // not the i in "Feeding"); only if there's no such match, the first
        // place it appears inside a word.
        words.forEach(function (word) {
            var hits = [], from = 0, at;
            while ((at = lower.indexOf(word, from)) !== -1) {
                if (at === 0 || /[^a-z0-9]/i.test(lower.charAt(at - 1))) hits.push(at);
                from = at + 1;
            }
            if (!hits.length && (at = lower.indexOf(word)) !== -1) hits.push(at);

            hits.forEach(function (start) {
                for (var k = start; k < start + word.length; k++) marks[k] = true;
            });
        });

        var i = 0;
        while (i < text.length) {
            var j = i;
            while (j < text.length && marks[j] === marks[i]) j++;
            var chunk = text.slice(i, j);
            span.appendChild(marks[i] ? el('mark', null, chunk) : document.createTextNode(chunk));
            i = j;
        }

        return span;
    }

    /* ---------- shared data ---------- */

    var popularCache = null;

    function fetchSuggestions(q, category) {
        var url = ENDPOINT + '?q=' + encodeURIComponent(q) +
            (category ? '&category=' + encodeURIComponent(category) : '');

        return fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        });
    }

    function popular() {
        if (popularCache) return Promise.resolve(popularCache);

        return fetchSuggestions('').then(function (data) {
            popularCache = data.categories || [];
            return popularCache;
        });
    }

    /* ---------- one shared dim layer behind the dropdown ---------- */

    var backdrop = null;

    function showBackdrop(show) {
        if (!backdrop) {
            backdrop = el('div', 'bb-suggest-backdrop');
            document.body.appendChild(backdrop);
        }
        backdrop.classList.toggle('is-open', show);
    }

    function thumbFor(product) {
        var box = el('span', 'bb-suggest-thumb');
        box.appendChild(icon(product.icon || 'bi-box-seam-fill'));
        return box;
    }

    /* ---------- one dropdown per search form ---------- */

    function attach(form) {
        var input = form.querySelector('input[name="search"]');
        if (!input) return;

        input.setAttribute('autocomplete', 'off');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');

        var panel = el('div', 'bb-suggest');
        panel.setAttribute('role', 'listbox');
        form.appendChild(panel);

        var timer = null;
        var requestId = 0;
        var activeIndex = -1;

        function options() {
            return Array.prototype.slice.call(panel.querySelectorAll('[data-suggest-option]'));
        }

        function setActive(index) {
            var list = options();
            list.forEach(function (node) { node.classList.remove('is-active'); });
            activeIndex = list.length ? (index + list.length) % list.length : -1;
            if (activeIndex >= 0) {
                list[activeIndex].classList.add('is-active');
                list[activeIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        function open() {
            panel.classList.add('is-open');
            form.classList.add('is-searching');
            input.setAttribute('aria-expanded', 'true');
            showBackdrop(true);
        }

        function close() {
            panel.classList.remove('is-open');
            form.classList.remove('is-searching');
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
            showBackdrop(false);
        }

        // Inside a category on the shop page the navbar search stays in it
        // (a hidden category field); elsewhere it searches everything.
        var categoryField = form.querySelector('[name="category"]');

        function category() {
            return categoryField ? categoryField.value : '';
        }

        function categoryLabel() {
            return categoryField ? (categoryField.getAttribute('data-label') || '') : '';
        }

        function resultsUrl(term) {
            var params = new URLSearchParams();
            if (category()) params.set('category', category());
            if (term) params.set('search', term);
            return RESULTS_URL + '?' + params.toString();
        }

        function goSearch(term) {
            term = (term || '').trim();
            if (!term) return;
            rememberSearch(term);
            window.location.href = resultsUrl(term);
        }

        function productSection(title, products, q) {
            var section = el('div', 'bb-suggest-section');
            section.appendChild(el('div', 'bb-suggest-heading', title));

            products.forEach(function (product) {
                var row = el('a', 'bb-suggest-item');
                row.href = product.url;
                row.setAttribute('data-suggest-option', '');
                if (q) {
                    row.addEventListener('click', function () { rememberSearch(q); });
                }

                if (product.image) {
                    var img = el('img', 'bb-suggest-thumb');
                    img.src = product.image;
                    img.alt = '';
                    img.loading = 'lazy';
                    img.addEventListener('error', function () {
                        img.replaceWith(thumbFor(product));
                    });
                    row.appendChild(img);
                } else {
                    // No photo: the product's category icon instead of a generic box.
                    row.appendChild(thumbFor(product));
                }

                var text = el('span', 'bb-suggest-label');
                text.appendChild(q ? highlighted(product.name, q) : document.createTextNode(product.name));
                if (product.category) {
                    text.appendChild(el('span', 'bb-suggest-meta', product.category));
                }
                row.appendChild(text);
                row.appendChild(el('span', 'bb-suggest-price', product.price));

                section.appendChild(row);
            });

            return section;
        }

        function browseCategoryRow() {
            var section = el('div', 'bb-suggest-section');
            var row = el('a', 'bb-suggest-item');
            row.href = resultsUrl('');
            row.setAttribute('data-suggest-option', '');
            row.appendChild(icon('bi-grid'));
            var label = el('span', 'bb-suggest-label');
            label.appendChild(document.createTextNode('Browse all in '));
            label.appendChild(el('span', 'bb-suggest-all', categoryLabel()));
            row.appendChild(label);
            section.appendChild(row);
            return section;
        }

        function categorySection(title, categories) {
            var section = el('div', 'bb-suggest-section');
            section.appendChild(el('div', 'bb-suggest-heading', title));

            var chips = el('div', 'bb-suggest-chips');
            categories.forEach(function (cat) {
                var chip = el('a', 'bb-suggest-chip');
                chip.appendChild(icon(cat.icon || 'bi-grid'));
                chip.appendChild(document.createTextNode(cat.label));
                chip.href = cat.url;
                chip.setAttribute('data-suggest-option', '');
                chips.appendChild(chip);
            });

            section.appendChild(chips);
            return section;
        }

        function renderIdle() {
            panel.textContent = '';
            var recent = loadRecent();

            if (recent.length) {
                var section = el('div', 'bb-suggest-section');
                var heading = el('div', 'bb-suggest-heading', 'Recent searches');
                var clear = el('button', null, 'Clear');
                clear.type = 'button';
                clear.addEventListener('mousedown', function (e) { e.preventDefault(); });
                clear.addEventListener('click', function () {
                    saveRecent([]);
                    renderIdle();
                    input.focus();
                });
                heading.appendChild(clear);
                section.appendChild(heading);

                recent.forEach(function (term) {
                    var row = el('div', 'bb-suggest-item');
                    row.setAttribute('data-suggest-option', '');
                    row.appendChild(icon('bi-clock-history'));
                    row.appendChild(el('span', 'bb-suggest-label', term));

                    var remove = el('button', 'bb-suggest-remove', '×');
                    remove.type = 'button';
                    remove.setAttribute('aria-label', 'Remove ' + term);
                    remove.addEventListener('mousedown', function (e) { e.preventDefault(); });
                    remove.addEventListener('click', function (e) {
                        e.stopPropagation();
                        forgetSearch(term);
                        renderIdle();
                        input.focus();
                    });
                    row.appendChild(remove);

                    row.addEventListener('click', function () { goSearch(term); });
                    section.appendChild(row);
                });

                panel.appendChild(section);
            }

            var popularSlot = el('div');
            panel.appendChild(popularSlot);

            var chosen = category();

            if (chosen) {
                // A category is already picked: show what's new in it, not other categories.
                var label = categoryLabel();

                fetchSuggestions('', chosen).then(function (data) {
                    if (input.value.trim() !== '' || category() !== chosen) return;
                    var products = data.products || [];
                    var slot = el('div');
                    if (products.length) {
                        slot.appendChild(productSection('New in ' + label, products, ''));
                    } else {
                        slot.appendChild(el('div', 'bb-suggest-loading', 'No products in ' + label + ' yet.'));
                    }
                    slot.appendChild(browseCategoryRow());
                    popularSlot.replaceWith(slot);
                }).catch(function () { /* offline — recent searches still work */ });
            } else {
                popular().then(function (categories) {
                    if (input.value.trim() !== '' || category() || !categories.length) return;
                    popularSlot.replaceWith(categorySection('Popular categories', categories));
                }).catch(function () { /* offline — recent searches still work */ });
            }

            open();
        }

        function renderResults(q, data) {
            panel.textContent = '';
            activeIndex = -1;

            var products = data.products || [];
            var categories = data.categories || [];

            if (!products.length && !categories.length) {
                var empty = el('div', 'bb-suggest-empty');
                empty.appendChild(icon('bi-search'));
                empty.appendChild(document.createTextNode(
                    'No results for “' + q + '”' + (category() ? ' in ' + categoryLabel() : '')
                ));
                empty.appendChild(el('small', null, category()
                    ? 'Try another word, or switch to All categories.'
                    : 'Check your spelling or try a different word.'));
                panel.appendChild(empty);

                if (!category()) {
                    popular().then(function (cats) {
                        if (input.value.trim() !== q || !cats.length) return;
                        panel.appendChild(categorySection('Browse categories instead', cats));
                    }).catch(function () {});
                }

                open();
                return;
            }

            if (products.length) {
                panel.appendChild(productSection(
                    category() ? 'Products in ' + categoryLabel() : 'Products',
                    products,
                    q
                ));
            }

            if (categories.length) {
                panel.appendChild(categorySection('Categories', categories));
            }

            var all = el('div', 'bb-suggest-section');
            var allRow = el('button', 'bb-suggest-item');
            allRow.type = 'button';
            allRow.setAttribute('data-suggest-option', '');
            allRow.appendChild(icon('bi-search'));
            var allLabel = el('span', 'bb-suggest-label');
            allLabel.appendChild(document.createTextNode('See all results for '));
            allLabel.appendChild(el('span', 'bb-suggest-all', '“' + q + '”'));
            if (category()) {
                allLabel.appendChild(document.createTextNode(' in ' + categoryLabel()));
            }
            allRow.appendChild(allLabel);
            allRow.addEventListener('click', function () { goSearch(q); });
            all.appendChild(allRow);
            panel.appendChild(all);

            open();
        }

        function update() {
            var q = input.value.trim();

            clearTimeout(timer);

            if (!q) {
                renderIdle();
                return;
            }

            // Wait for a short pause in typing before asking the server.
            timer = setTimeout(function () {
                var mine = ++requestId;

                var chosen = category();

                fetchSuggestions(q, chosen).then(function (data) {
                    // Ignore answers to older keystrokes (or another category) that arrive late.
                    if (mine === requestId && input.value.trim() === q && category() === chosen) {
                        renderResults(q, data);
                    }
                }).catch(function () {
                    if (mine !== requestId) return;
                    panel.textContent = '';
                    panel.appendChild(el('div', 'bb-suggest-loading', 'Suggestions are unavailable right now — press Enter to search.'));
                    open();
                });
            }, 180);
        }

        input.addEventListener('focus', update);
        input.addEventListener('input', update);


        input.addEventListener('keydown', function (e) {
            if (!panel.classList.contains('is-open')) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(activeIndex + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(activeIndex - 1);
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                options()[activeIndex].click();
            } else if (e.key === 'Escape') {
                close();
            }
        });

        // Remember what was searched when the form is submitted normally.
        form.addEventListener('submit', function (e) {
            var term = input.value.trim();

            // Nothing typed but a category chosen: open that whole category.
            if (!term && category()) {
                return;
            }

            if (!term) {
                e.preventDefault();
                input.focus();
                return;
            }
            rememberSearch(term);
        });

        document.addEventListener('mousedown', function (e) {
            if (!form.contains(e.target) && panel.classList.contains('is-open')) close();
        });

        // Tabbing out of the search box closes it too.
        form.addEventListener('focusout', function (e) {
            if (e.relatedTarget && !form.contains(e.relatedTarget)) close();
        });

        // Keep the typed term in the box on the results page.
        var current = new URLSearchParams(window.location.search).get('search');
        if (current && !input.value) {
            input.value = current;
        }
    }

    function init() {
        document.querySelectorAll('form[data-search-suggest]').forEach(attach);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
