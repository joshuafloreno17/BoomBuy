{{--
    Instant feedback on every page (included by partials.head), so a tap
    never looks ignored while the next page is on its way (~0.5 s through the
    tunnel):

    1. A submitted form's button shows a spinner and "Saving…" (or a better
       word from its label) and can't be pressed again.
    2. A thin violet bar runs along the top while the next page loads.

    Left alone: forms and links a page already handles itself (their submit
    or click is cancelled), links that open a new tab or download, and
    buttons that are already disabled. Opt out with data-no-busy (form or
    button) or data-no-progress (link). A button may name its own wording
    with data-busy-text.
--}}
<style>
    #bb-progress {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 100000;
        width: 0;
        height: 3px;
        background: linear-gradient(90deg, #6d4ad1, #9b82f0);
        box-shadow: 0 0 10px rgba(109, 74, 209, 0.6);
        opacity: 0;
        pointer-events: none;
        transition: width 2.4s cubic-bezier(.1, .7, .3, 1), opacity .2s ease;
    }

    #bb-progress.is-on {
        width: 88%;
        opacity: 1;
    }

    .bb-busy {
        cursor: progress !important;
        opacity: .88;
    }

    .bb-busy-spin {
        display: inline-block;
        width: 1em;
        height: 1em;
        margin-right: .5em;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        vertical-align: -.15em;
        animation: bbBusySpin .7s linear infinite;
    }

    @keyframes bbBusySpin {
        to { transform: rotate(360deg); }
    }
</style>
<script>
    (function () {
        if (window.bbFeedback) return;

        var RESET_AFTER = 10000; // a download or a new tab: the page stays

        // ---------- The top bar ----------

        var bar = null;
        var barTimer = null;

        function progressStart() {
            if (!bar) {
                bar = document.createElement('div');
                bar.id = 'bb-progress';
                document.body.appendChild(bar);
                void bar.offsetWidth;
            }
            bar.classList.add('is-on');
            clearTimeout(barTimer);
            barTimer = setTimeout(progressStop, RESET_AFTER);
        }

        function progressStop() {
            if (bar) bar.classList.remove('is-on');
        }

        // ---------- Busy buttons ----------

        var WORDS = [
            [/delete|remove|cancel order|reject/i, 'Deleting…'],
            [/place order|checkout|pay/i, 'Placing order…'],
            [/send|submit|message|reply/i, 'Sending…'],
            [/upload/i, 'Uploading…'],
            [/log ?in|sign ?in/i, 'Signing in…'],
            [/search|filter|apply/i, 'Loading…'],
            [/save|update|add|create|confirm|accept|approve|book/i, 'Saving…']
        ];

        function busyText(button) {
            if (button.dataset.busyText) return button.dataset.busyText;
            var label = (button.textContent || button.value || '').trim();
            for (var i = 0; i < WORDS.length; i++) {
                if (WORDS[i][0].test(label)) return WORDS[i][1];
            }
            return 'Please wait…';
        }

        function setBusy(button) {
            if (!button || button.disabled || button.classList.contains('bb-busy') || button.closest('[data-no-busy]')) return;

            button.dataset.bbWas = button.tagName === 'INPUT' ? button.value : button.innerHTML;
            button.style.minWidth = button.offsetWidth + 'px';
            button.classList.add('bb-busy');
            button.setAttribute('aria-busy', 'true');

            if (button.tagName === 'INPUT') {
                button.value = busyText(button);
            } else {
                var text = busyText(button);
                button.innerHTML = '<span class="bb-busy-spin" aria-hidden="true"></span>';
                button.appendChild(document.createTextNode(text));
            }

            // Disabled after the browser has read the form, so a button's own
            // name/value still goes along.
            button.disabled = true;
            setTimeout(function () { clearBusy(button); }, RESET_AFTER);
        }

        function clearBusy(button) {
            if (!button.classList.contains('bb-busy')) return;
            if (button.tagName === 'INPUT') button.value = button.dataset.bbWas;
            else button.innerHTML = button.dataset.bbWas;
            button.classList.remove('bb-busy');
            button.removeAttribute('aria-busy');
            button.style.minWidth = '';
            button.disabled = false;
        }

        // A form actually being sent (pages that handle their own submit
        // cancel the event before it reaches here).
        document.addEventListener('submit', function (event) {
            var form = event.target;
            var submitter = event.submitter;

            setTimeout(function () {
                if (event.defaultPrevented || form.matches('[data-no-busy]')) return;

                if (form.target !== '_blank') progressStart();
                setBusy(submitter || form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]'));
            }, 0);
        });

        // A link to another page of the site.
        document.addEventListener('click', function (event) {
            var link = event.target.closest && event.target.closest('a[href]');
            if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-no-progress')) return;

            var url = new URL(link.href, location.href);
            if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) return;
            if (url.pathname === location.pathname && url.search === location.search && url.hash) return;

            setTimeout(function () {
                if (!event.defaultPrevented) progressStart();
            }, 0);
        });

        // Back / Forward from the browser's cache: nothing should look busy.
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            progressStop();
            document.querySelectorAll('.bb-busy').forEach(clearBusy);
        });

        window.bbFeedback = { start: progressStart, stop: progressStop, busy: setBusy, clear: clearBusy };
    })();
</script>
