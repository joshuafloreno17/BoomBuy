<link rel="stylesheet" href="{{ vasset('css/partials/notification-modal.css') }}">

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
