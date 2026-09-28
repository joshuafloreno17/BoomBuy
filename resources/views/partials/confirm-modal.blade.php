<style>
    .bb-confirm-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(23, 32, 51, 0.5);
        z-index: 5000;

        align-items: center;
        justify-content: center;

        padding: 20px;
    }

    .bb-confirm-overlay.is-open {
        display: flex;
    }

    .bb-confirm-box {
        background: var(--paper, #fff);
        border-radius: 18px;
        padding: 28px;
        max-width: 360px;
        width: 100%;

        box-shadow: 0 30px 60px -20px rgba(23, 32, 51, 0.35);

        text-align: center;

        animation: bbConfirmPop 0.2s cubic-bezier(.3,1.4,.5,1);
    }

    @keyframes bbConfirmPop {
        from { transform: scale(0.94); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .bb-confirm-icon {
        width: 46px;
        height: 46px;
        margin: 0 auto 14px;

        border-radius: 50%;
        background: var(--teal-bg, #e3f6f4);
        color: var(--teal-dark, #0a6f66);

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 20px;
    }

    .bb-confirm-box.is-danger .bb-confirm-icon {
        background: #fdecea;
        color: #c62828;
    }

    .bb-confirm-box.is-alert .bb-confirm-icon {
        background: #fff4d6;
        color: #b7860a;
    }

    .bb-confirm-title {
        font-family: var(--font-display, 'Baloo 2', sans-serif);
        font-size: 18px;
        font-weight: 800;
        color: var(--ink, #172033);
        line-height: 1.3;

        margin-bottom: 6px;
    }

    .bb-confirm-title:empty {
        display: none;
    }

    .bb-confirm-message {
        font-family: var(--font-display, 'Baloo 2', sans-serif);
        font-size: 16px;
        font-weight: 700;
        color: var(--ink, #172033);
        line-height: 1.4;
        white-space: pre-line;
        overflow-wrap: anywhere;

        margin-bottom: 22px;
    }

    /* With a title, the message becomes supporting text */
    .bb-confirm-title:not(:empty) + .bb-confirm-message {
        font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
        font-size: 13.5px;
        font-weight: 500;
        color: var(--muted, #8d6c62);
    }

    .bb-confirm-actions {
        display: flex;
        gap: 10px;
    }

    .bb-confirm-actions button {
        flex: 1;

        padding: 11px 14px;
        border: none;
        border-radius: 10px;

        font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
        font-size: 13px;
        font-weight: 700;

        cursor: pointer;
        transition: 0.2s ease;
    }

    .bb-confirm-actions button[hidden] {
        display: none;
    }

    .bb-confirm-cancel {
        background: #f3ede9;
        color: var(--ink, #172033);
    }

    .bb-confirm-cancel:hover {
        background: #e9e0da;
    }

    .bb-confirm-ok {
        background: var(--accent, #e8420f);
        color: #fff;
    }

    .bb-confirm-ok:hover {
        background: var(--accent-dark, #c43408);
    }

    .bb-confirm-box.is-danger .bb-confirm-ok {
        background: #c62828;
    }

    .bb-confirm-box.is-danger .bb-confirm-ok:hover {
        background: #a51f1f;
    }
</style>

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
