<link rel="stylesheet" href="{{ vasset('css/partials/confirm-modal.css') }}">

<div class="bb-confirm-overlay" id="bbConfirmOverlay">
    <div class="bb-confirm-box" id="bbConfirmBox" role="dialog" aria-modal="true" aria-labelledby="bbConfirmMessage">
        <div class="bb-confirm-icon">
            <i class="bi bi-question-circle-fill" id="bbConfirmIcon"></i>
        </div>
        <div class="bb-confirm-title" id="bbConfirmTitle"></div>
        <div class="bb-confirm-message" id="bbConfirmMessage"></div>
        <div class="bb-confirm-actions">
            <button type="button" class="bb-confirm-cancel" id="bbConfirmCancel">Cancel</button>
            <button type="button" class="bb-confirm-ok" id="bbConfirmOk">Yes</button>
        </div>
    </div>
</div>

<script>
(function () {
    // Pages that include this partial more than once only need it wired up once.
    if (window.bbConfirm) {
        return;
    }

    var ICONS = {
        confirm: 'bi-question-circle-fill',
        danger: 'bi-exclamation-triangle-fill',
        alert: 'bi-info-circle-fill'
    };

    /*
     * Shared dialog. opts:
     *   title, okText, cancelText, danger (bool), alert (bool = no Cancel),
     *   onConfirm, onCancel
     */
    function openDialog(message, opts) {
        opts = opts || {};

        var overlay = document.getElementById('bbConfirmOverlay');
        var box = document.getElementById('bbConfirmBox');
        var icon = document.getElementById('bbConfirmIcon');
        var titleEl = document.getElementById('bbConfirmTitle');
        var msgEl = document.getElementById('bbConfirmMessage');
        var okBtn = document.getElementById('bbConfirmOk');
        var cancelBtn = document.getElementById('bbConfirmCancel');

        // Should never happen, but never silently block the action.
        if (!overlay || !msgEl || !okBtn || !cancelBtn) {
            if (opts.alert) {
                window.alert(message);
                if (opts.onConfirm) opts.onConfirm();
            } else if (window.confirm(message)) {
                if (opts.onConfirm) opts.onConfirm();
            } else if (opts.onCancel) {
                opts.onCancel();
            }
            return;
        }

        var variant = opts.alert ? 'alert' : (opts.danger ? 'danger' : 'confirm');

        box.classList.toggle('is-danger', variant === 'danger');
        box.classList.toggle('is-alert', variant === 'alert');
        icon.className = 'bi ' + ICONS[variant];

        titleEl.textContent = opts.title || '';
        msgEl.textContent = message || '';
        okBtn.textContent = opts.okText || (opts.alert ? 'OK' : 'Yes');
        cancelBtn.textContent = opts.cancelText || 'Cancel';
        cancelBtn.hidden = !!opts.alert;

        var previousFocus = document.activeElement;

        overlay.classList.add('is-open');
        okBtn.focus();

        function close() {
            overlay.classList.remove('is-open');
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
            overlay.removeEventListener('click', onOverlayClick);
            document.removeEventListener('keydown', onKey, true);

            if (previousFocus && previousFocus.focus) {
                previousFocus.focus();
            }
        }

        function onOk() {
            close();
            if (opts.onConfirm) opts.onConfirm();
        }

        function onCancel() {
            close();
            // An alert only has one outcome — dismissing it still means "OK".
            if (opts.alert) {
                if (opts.onConfirm) opts.onConfirm();
            } else if (opts.onCancel) {
                opts.onCancel();
            }
        }

        function onOverlayClick(e) {
            if (e.target === overlay) {
                onCancel();
            }
        }

        function onKey(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                onCancel();
            }
        }

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
        overlay.addEventListener('click', onOverlayClick);
        document.addEventListener('keydown', onKey, true);
    }

    // Submit a form after the user confirmed, keeping the clicked button's
    // name/value and any other submit handlers (validation etc.) intact.
    function submitConfirmed(form, submitter) {
        form.__bbConfirmed = true;

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
        } else {
            form.submit();
        }

        // requestSubmit dispatches synchronously; if the browser blocked it
        // (e.g. a required field is empty) don't leave the bypass armed.
        form.__bbConfirmed = false;
    }

    window.bbConfirm = function (message, onConfirm, opts) {
        opts = opts || {};
        opts.onConfirm = onConfirm;
        openDialog(message, opts);
    };

    window.bbAlert = function (message, opts) {
        opts = opts || {};
        opts.alert = true;
        openDialog(message, opts);
    };

    // For inline use: onsubmit="return bbConfirmSubmit(event, this, 'Sure?')"
    window.bbConfirmSubmit = function (event, form, message, opts) {
        if (form.__bbConfirmed) {
            return true;
        }

        event.preventDefault();

        opts = opts || {};
        var submitter = event.submitter || null;

        window.bbConfirm(message, function () {
            submitConfirmed(form, submitter);
        }, opts);

        return false;
    };

    /*
     * Declarative use — no JS needed on the page:
     *   <form data-confirm="Delete this?" data-confirm-danger> ... </form>
     *   <button type="submit" data-confirm="Cancel this order?"> ... </button>
     * Optional: data-confirm-title, data-confirm-ok, data-confirm-cancel,
     *           data-confirm-danger
     */
    document.addEventListener('submit', function (e) {
        var form = e.target;

        if (!(form instanceof HTMLFormElement) || form.__bbConfirmed) {
            return;
        }

        var submitter = e.submitter || null;
        var source = submitter && submitter.hasAttribute('data-confirm')
            ? submitter
            : (form.hasAttribute('data-confirm') ? form : null);

        if (!source) {
            return;
        }

        e.preventDefault();
        e.stopImmediatePropagation();

        window.bbConfirm(source.getAttribute('data-confirm'), function () {
            submitConfirmed(form, submitter);
        }, {
            title: source.getAttribute('data-confirm-title'),
            okText: source.getAttribute('data-confirm-ok'),
            cancelText: source.getAttribute('data-confirm-cancel'),
            danger: source.hasAttribute('data-confirm-danger')
        });
    }, true);
})();
</script>
