<style>
    .bb-notif-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(23, 32, 51, 0.5);
        z-index: 5000;

        align-items: center;
        justify-content: center;

        padding: 20px;
    }

    .bb-notif-overlay.is-open {
        display: flex;
    }

    .bb-notif-box {
        position: relative;

        background: var(--paper, #fff);
        border-radius: 18px;
        padding: 28px;
        max-width: 440px;
        width: 100%;
        max-height: calc(100vh - 40px);
        overflow-y: auto;

        box-shadow: 0 30px 60px -20px rgba(23, 32, 51, 0.35);

        animation: bbNotifPop 0.2s cubic-bezier(.3,1.4,.5,1);
    }

    @keyframes bbNotifPop {
        from { transform: scale(0.94); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .bb-notif-close {
        position: absolute;
        top: 14px;
        right: 14px;

        width: 32px;
        height: 32px;
        border: none;
        border-radius: 50%;
        background: #f3ede9;
        color: var(--muted, #8d6c62);

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 15px;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .bb-notif-close:hover {
        background: #e9e0da;
        color: var(--ink, #172033);
    }

    .bb-notif-icon {
        width: 50px;
        height: 50px;
        margin-bottom: 14px;

        border-radius: 13px;
        background: #ffefea;
        color: var(--accent, #e8420f);

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 23px;
    }

    .bb-notif-title {
        font-family: var(--font-display, 'Baloo 2', sans-serif);
        font-size: 21px;
        font-weight: 800;
        color: var(--ink, #172033);
        line-height: 1.25;

        padding-right: 30px;
    }

    .bb-notif-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;

        margin-top: 8px;

        color: #b99c93;
        font-size: 11.5px;
        font-weight: 600;
    }

    .bb-notif-type {
        background: #fff2ee;
        color: var(--accent, #e8420f);
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 9.5px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .bb-notif-type:empty {
        display: none;
    }

    .bb-notif-message {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #f7e5e0;

        font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
        font-size: 14px;
        line-height: 1.7;
        color: #5b4a44;
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .bb-notif-ok {
        width: 100%;
        margin-top: 24px;

        padding: 11px 14px;
        border: none;
        border-radius: 10px;
        background: var(--accent, #e8420f);
        color: #fff;

        font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
        font-size: 13px;
        font-weight: 700;

        cursor: pointer;
        transition: 0.2s ease;
    }

    .bb-notif-ok:hover {
        background: var(--accent-dark, #c43408);
    }
</style>

<div class="bb-notif-overlay" id="bbNotifOverlay">
    <div class="bb-notif-box" role="dialog" aria-modal="true" aria-labelledby="bbNotifTitle">
        <button type="button" class="bb-notif-close" data-notif-close aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="bb-notif-icon">
            <i class="bi bi-bell-fill" id="bbNotifIcon"></i>
        </div>

        <div class="bb-notif-title" id="bbNotifTitle"></div>

        <div class="bb-notif-meta">
            <span class="bb-notif-type" id="bbNotifType"></span>
            <span id="bbNotifDate"></span>
        </div>

        <div class="bb-notif-message" id="bbNotifMessage"></div>

        <button type="button" class="bb-notif-ok" data-notif-close>Got it</button>
    </div>
</div>

<script>
(function () {
    var overlay = document.getElementById('bbNotifOverlay');
    var csrf = @json(csrf_token());
    var previousFocus = null;

    function el(id) {
        return document.getElementById(id);
    }

    function open(card) {
        el('bbNotifIcon').className = 'bi ' + (card.dataset.icon || 'bi-bell-fill');
        el('bbNotifTitle').textContent = card.dataset.title || '';
        el('bbNotifType').textContent = card.dataset.type || '';
        el('bbNotifDate').textContent = (card.dataset.date || '') +
            (card.dataset.ago ? ' (' + card.dataset.ago + ')' : '');
        el('bbNotifMessage').textContent = card.dataset.message || '';

        previousFocus = card;
        overlay.classList.add('is-open');
        overlay.querySelector('.bb-notif-ok').focus();

        if (card.dataset.readUrl) {
            markRead(card);
        }
    }

    function close() {
        overlay.classList.remove('is-open');

        if (previousFocus) {
            previousFocus.focus();
        }
    }

    function markRead(card) {
        var url = card.dataset.readUrl;
        delete card.dataset.readUrl;

        // Flip the card to its read state right away.
        card.classList.remove('unread');
        card.classList.add('read');

        var dot = card.querySelector('.unread-dot');
        if (dot) dot.remove();

        var bottom = card.querySelector('.notification-bottom');
        if (bottom && !bottom.querySelector('.read-label')) {
            var label = document.createElement('span');
            label.className = 'read-label';
            label.innerHTML = '<i class="bi bi-check2"></i> Read';
            bottom.appendChild(label);
        }

        // Keep the sidebar's unread count in sync.
        var badge = document.querySelector(
            'a[aria-label="Notifications"] .notification-badge, ' +
            'a[aria-label="Notifications"] .sidebar-notification-badge'
        );
        if (badge && badge.textContent.trim() !== '99+') {
            var count = parseInt(badge.textContent, 10) - 1;
            if (count > 0) {
                badge.textContent = count;
            } else {
                badge.remove();
            }
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).catch(function () {
            // Not critical: it stays unread on the server and shows again on reload.
        });
    }

    document.addEventListener('click', function (e) {
        var card = e.target.closest('[data-notification-open]');

        if (card) {
            open(card);
            return;
        }

        if (e.target === overlay || e.target.closest('[data-notif-close]')) {
            close();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
            close();
        }
    });
})();
</script>
