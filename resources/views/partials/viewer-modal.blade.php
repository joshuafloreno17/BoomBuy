{{--
    Shared pop-up viewer: photos, documents, quick views and small forms open
    over the page instead of in a new tab / another page. Add it to a page with
    @include('partials.viewer-modal') and mark the links:

      <a href="photo-or-file-url" data-viewer>…</a>              photo / PDF (type found from the file)
      <a href="…" data-viewer="order-12">…</a>                   same value = one group, ‹ › between them

    Optional card under the picture (all plain text):
      data-viewer-badge, data-viewer-title, data-viewer-meta, data-viewer-text,
      data-viewer-action (url) + data-viewer-action-label   → button at the bottom

    Other kinds (data-viewer-kind):
      card    no file — just the card; data-viewer-src (photo) or data-viewer-icon (bi-*) on top
      page    loads the link's page and shows only data-viewer-fragment="CSS selector"
      inline  moves data-viewer-target="#id" (an element already on the page) into the pop-up

    Without JavaScript, or with Ctrl/⌘-click, the link still opens normally.
    The phone's back button closes the pop-up instead of leaving the page.
--}}
@once
<link rel="stylesheet" href="{{ vasset('css/partials/viewer-modal.css') }}">

<div class="bb-viewer-overlay" id="bbViewerOverlay" aria-hidden="true">
    <div class="bb-viewer-box" role="dialog" aria-modal="true" aria-label="Viewer">
        <button type="button" class="bb-viewer-close" data-viewer-close aria-label="Close"><i class="bi bi-x-lg"></i></button>

        <div class="bb-viewer-media" id="bbViewerMedia" hidden>
            <div class="bb-viewer-stage" id="bbViewerStage"></div>
            <button type="button" class="bb-viewer-nav is-prev" id="bbViewerPrev" aria-label="Previous" hidden><i class="bi bi-chevron-left"></i></button>
            <button type="button" class="bb-viewer-nav is-next" id="bbViewerNext" aria-label="Next" hidden><i class="bi bi-chevron-right"></i></button>
            <span class="bb-viewer-count" id="bbViewerCount"></span>
        </div>

        <div class="bb-viewer-body" id="bbViewerBody" hidden></div>

        <div class="bb-viewer-card" id="bbViewerCard" hidden>
            <span class="bb-viewer-badge" id="bbViewerBadge"></span>
            <h3 class="bb-viewer-title" id="bbViewerTitle"></h3>
            <div class="bb-viewer-meta" id="bbViewerMeta"></div>
            <div class="bb-viewer-text" id="bbViewerText"></div>
            <div class="bb-viewer-actions" id="bbViewerActions"></div>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.bbViewer) {
        return;
    }

    var overlay = document.getElementById('bbViewerOverlay'); // not "bbViewer": an element id becomes window.bbViewer
    var box = overlay.querySelector('.bb-viewer-box');

    // Partials can include this inside hidden or transformed parts of a page — live at the top instead.
    document.body.appendChild(overlay);
    var media = document.getElementById('bbViewerMedia');
    var stage = document.getElementById('bbViewerStage');
    var prevBtn = document.getElementById('bbViewerPrev');
    var nextBtn = document.getElementById('bbViewerNext');
    var count = document.getElementById('bbViewerCount');
    var body = document.getElementById('bbViewerBody');
    var card = document.getElementById('bbViewerCard');
    var actions = document.getElementById('bbViewerActions');

    var items = [];
    var index = 0;
    var isOpen = false;
    var pushedState = false;
    var previousFocus = null;
    var inlineHome = null;   // { el, marker } while an inline element sits in the pop-up
    var loadToken = 0;       // ignores late answers after moving on

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text) node.textContent = text;
        return node;
    }

    function linkOf(item) {
        return item.getAttribute('data-viewer-src') || item.getAttribute('href') || '';
    }

    function message(icon, text, url) {
        var wrap = el('div', 'bb-viewer-state');
        wrap.appendChild(el('i', 'bi ' + icon));
        wrap.appendChild(document.createTextNode(text + ' '));
        if (url) {
            var a = el('a', '', 'Open it instead');
            a.href = url;
            wrap.appendChild(a);
        }
        return wrap;
    }

    function restoreInline() {
        if (inlineHome) {
            inlineHome.marker.parentNode.insertBefore(inlineHome.el, inlineHome.marker);
            inlineHome.el.hidden = inlineHome.wasHidden;
            inlineHome.marker.remove();
            inlineHome = null;
        }
    }

    function reset() {
        restoreInline();
        stage.innerHTML = '';
        body.innerHTML = '';
        body.classList.remove('is-flush');
        media.hidden = true;
        body.hidden = true;
        card.hidden = true;
        actions.innerHTML = '';
        box.classList.remove('is-wide', 'is-media-only');
    }

    function addAction(url, label, ghost) {
        var a = el('a', ghost ? 'is-ghost' : '');
        a.href = url;
        if (ghost) a.appendChild(el('i', 'bi bi-box-arrow-up-right'));
        a.appendChild(document.createTextNode(label));
        actions.appendChild(a);
    }

    function fillCard(item) {
        var any = false;

        ['badge', 'title', 'meta', 'text'].forEach(function (key) {
            var value = item.getAttribute('data-viewer-' + key) || '';
            document.getElementById('bbViewer' + key.charAt(0).toUpperCase() + key.slice(1)).textContent = value;
            if (value) any = true;
        });

        var action = item.getAttribute('data-viewer-action');
        if (action) {
            addAction(action, item.getAttribute('data-viewer-action-label') || 'Open');
            any = true;
        }

        card.hidden = !any;
        box.classList.toggle('is-media-only', !any && !media.hidden);
    }

    function showImage(url, token) {
        stage.innerHTML = '';
        stage.appendChild(message('bi-hourglass-split', 'Loading…'));

        var img = new Image();
        img.alt = '';
        img.onload = function () {
            if (token !== loadToken) return;
            stage.innerHTML = '';
            stage.appendChild(img);
        };
        img.onerror = function () {
            if (token !== loadToken) return;
            stage.innerHTML = '';
            stage.appendChild(message('bi-image', 'This photo could not be loaded.', url));
        };
        img.src = url;
    }

    function showPdf(url) {
        var frame = el('iframe');
        frame.src = url;
        frame.title = 'Document';
        stage.innerHTML = '';
        stage.appendChild(frame);
        box.classList.add('is-wide');
        // Some phones (the app's WebView) can't draw PDFs inside a page.
        addAction(url, 'Open the file', true);
        card.hidden = false;
        box.classList.remove('is-media-only');
    }

    // A photo or a document: ask the server what the file is, then show it.
    function showFile(item, token) {
        var url = linkOf(item);
        media.hidden = false;

        if (item.getAttribute('data-viewer-type') === 'image') {
            showImage(url, token);
            return;
        }

        stage.innerHTML = '';
        stage.appendChild(message('bi-hourglass-split', 'Loading…'));

        fetch(url, { method: 'HEAD', credentials: 'same-origin' }).then(function (res) {
            if (token !== loadToken) return;

            if (!res.ok) {
                stage.innerHTML = '';
                stage.appendChild(message('bi-file-earmark-x', res.status === 403 ? 'You can’t view this file.' : 'This file is no longer available.'));
                return;
            }

            var type = (res.headers.get('Content-Type') || '').toLowerCase();

            if (type.indexOf('pdf') !== -1) {
                showPdf(url);
            } else if (type.indexOf('image/') === 0 || type === '') {
                showImage(url, token);
            } else {
                stage.innerHTML = '';
                stage.appendChild(message('bi-file-earmark', 'This file can’t be previewed here.', url));
            }
        }).catch(function () {
            if (token === loadToken) showImage(url, token);
        });
    }

    function showCardOnly(item) {
        var src = item.getAttribute('data-viewer-src');
        var icon = item.getAttribute('data-viewer-icon');

        if (src) {
            media.hidden = false;
            showImage(src, loadToken);
        } else if (icon) {
            media.hidden = false;
            stage.innerHTML = '';
            var holder = el('div', 'bb-viewer-icon');
            holder.appendChild(el('i', 'bi ' + icon));
            stage.appendChild(holder);
        }
    }

    // Another page's content (e.g. one email): load it and show only the part we need.
    function showPage(item, token) {
        var url = item.getAttribute('href');
        var selector = item.getAttribute('data-viewer-fragment');

        body.hidden = false;
        body.classList.add('is-flush');
        box.classList.add('is-wide');
        body.appendChild(message('bi-hourglass-split', 'Loading…'));
        body.firstChild.style.color = 'var(--muted, #645a80)';

        fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (res) {
            if (!res.ok) throw new Error(res.status);
            return res.text();
        }).then(function (html) {
            if (token !== loadToken) return;
            var part = new DOMParser().parseFromString(html, 'text/html').querySelector(selector);
            if (!part) throw new Error('missing part');
            body.innerHTML = '';
            body.appendChild(document.importNode(part, true));
        }).catch(function () {
            if (token === loadToken) window.location.href = url;
        });
    }

    function showInline(item) {
        var target = document.querySelector(item.getAttribute('data-viewer-target'));
        if (!target) return;

        var marker = document.createComment('bb-viewer');
        target.parentNode.insertBefore(marker, target);
        inlineHome = { el: target, marker: marker, wasHidden: target.hidden };

        target.hidden = false;
        body.hidden = false;
        body.appendChild(target);

        setTimeout(function () {
            var field = target.querySelector('input:not([type=hidden]), select, textarea');
            if (field) field.focus();
        }, 30);
    }

    function render() {
        var item = items[index];
        var kind = item.getAttribute('data-viewer-kind') || 'file';
        var token = ++loadToken;

        reset();

        if (kind === 'page') {
            showPage(item, token);
        } else if (kind === 'inline') {
            showInline(item);
        } else if (kind === 'card') {
            showCardOnly(item);
        } else {
            showFile(item, token);
        }

        if (item.hasAttribute('data-viewer-wide')) box.classList.add('is-wide');

        var many = items.length > 1;
        prevBtn.hidden = !many;
        nextBtn.hidden = !many;
        count.textContent = many ? (index + 1) + ' / ' + items.length : '';
        if (many) media.hidden = false;

        fillCard(item);

        item.dispatchEvent(new CustomEvent('bbviewer:open', { bubbles: true, detail: { body: body } }));
    }

    function open(item) {
        var group = item.getAttribute('data-viewer');

        items = group
            ? Array.prototype.filter.call(document.querySelectorAll('[data-viewer]'), function (other) {
                return other.getAttribute('data-viewer') === group;
            })
            : [item];
        index = Math.max(0, items.indexOf(item));

        if (!isOpen) {
            previousFocus = document.activeElement;
            isOpen = true;
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('bb-viewer-locked');

            // The phone's back button closes the pop-up instead of leaving the page.
            try {
                history.pushState({ bbViewer: true }, '');
                pushedState = true;
            } catch (e) {
                pushedState = false;
            }
        }

        render();
        overlay.querySelector('.bb-viewer-close').focus();
    }

    function close(fromHistory) {
        if (!isOpen) return;

        isOpen = false;
        loadToken++;
        reset();
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('bb-viewer-locked');

        if (pushedState) {
            pushedState = false;
            if (!fromHistory) history.back();
        }

        if (previousFocus && previousFocus.focus) previousFocus.focus();
    }

    function step(by) {
        if (items.length < 2) return;
        index = (index + by + items.length) % items.length;
        render();
    }

    prevBtn.addEventListener('click', function () { step(-1); });
    nextBtn.addEventListener('click', function () { step(1); });

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay || e.target.closest('[data-viewer-close]')) {
            e.preventDefault();
            close();
        }
    });

    // Capture + stop: Esc closes only this pop-up, not one open underneath it.
    document.addEventListener('keydown', function (e) {
        if (!isOpen) return;
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(); }
        if (e.key === 'ArrowLeft' && !media.hidden && body.hidden) step(-1);
        if (e.key === 'ArrowRight' && !media.hidden && body.hidden) step(1);
    }, true);

    window.addEventListener('popstate', function () {
        if (isOpen) close(true);
    });

    // Works for links added later too (e.g. new chat photos). Capture phase: some
    // pop-ups on the page (e.g. Order Tracking) stop clicks from bubbling up.
    document.addEventListener('click', function (e) {
        var item = e.target.closest ? e.target.closest('[data-viewer]') : null;

        if (!item || overlay.contains(item)) return;
        if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

        e.preventDefault();
        e.stopPropagation();
        open(item);
    }, true);

    window.bbViewer = { open: open, close: function () { close(); } };
})();
</script>
@endonce
