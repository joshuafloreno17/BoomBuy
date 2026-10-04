<link rel="stylesheet" href="{{ vasset('css/partials/buyer-navbar.css') }}">

<nav class="bb-navbar">

    <div class="bb-nav-left">

    <a href="{{ $bbNavUser ? route('buyer.dashboard') : route('home') }}" class="bb-logo">
        <img src="{{ asset('images/icon.svg') }}" alt="" width="34" height="34">
        BoomBuy
    </a>

    <div class="bb-nav-links">

        <a href="{{ $bbNavUser ? route('buyer.dashboard') : route('home') }}" class="{{ $bbActive === 'home' ? 'active' : '' }}">
            Home
        </a>

        <a href="{{ route('products') }}" class="{{ $bbActive === 'shop' ? 'active' : '' }}">
            Shop
        </a>

@if($bbNavUser)

    <a
        href="{{ route('buyer.orders') }}"
        class="{{ $bbActive === 'orders' ? 'active' : '' }}"
    >
        My Orders
    </a>

@else

    {{-- Guests: jump to the category grid on the landing page. --}}
    <a href="{{ route('home') }}#categories">
        Categories
    </a>

@endif

    </div>

    </div>

    <form class="bb-nav-search" action="{{ route('products') }}" method="GET" data-search-suggest>
        @php
            // Inside a category on the shop page, searching stays in that category.
            $bbSearchCategory = request()->routeIs('products') ? (\App\Support\Categories::LIST[request('category')] ?? null) : null;
        @endphp
        @if($bbSearchCategory)
            <input type="hidden" name="category" value="{{ request('category') }}" data-label="{{ $bbSearchCategory }}">
        @endif
        <input
            type="text"
            name="search"
            value="{{ request()->routeIs('products') ? request('search') : '' }}"
            placeholder="{{ $bbSearchCategory ? 'Search in ' . $bbSearchCategory : 'What are you looking for today?' }}"
            aria-label="Search products"
        >
        <button type="submit" class="bb-nav-search-btn" aria-label="Search">
            <i class="bi bi-search"></i><span>Search</span>
        </button>
    </form>

    <div class="bb-nav-right">

        @if($bbNavUser)

            {{-- 💬 Messages — hover shows recent chats, click opens Messages --}}
            <div class="bb-hover bb-nav-messages" data-hover-panel>

                <a
                    href="{{ route('messages.index') }}"
                    class="bb-cart-link {{ $bbActive === 'messages' ? 'active' : '' }}"
                    aria-label="Messages{{ $bbMessageCount > 0 ? ' (' . $bbMessageCount . ' unread)' : '' }}"
                    title="Messages"
                >
                    <i class="bi bi-chat-dots-fill"></i>

                    @if($bbMessageCount > 0)
                        <span class="bb-cart-number">
                            {{ $bbMessageCount > 99 ? '99+' : $bbMessageCount }}
                        </span>
                    @endif
                </a>

                <div class="bb-pop" style="width:340px;">

                    <div class="bb-pop-head">
                        <strong>Recent Chats</strong>
                    </div>

                    @if($bbRecentChats->isEmpty())

                        <div class="bb-pop-empty">
                            <i class="bi bi-chat-dots"></i>
                            No messages yet.
                        </div>

                    @else

                        <div class="bb-pop-list">
                            @foreach($bbRecentChats as $bbChat)
                                @php
                                    $bbPartner = $bbChatPartners->get($bbChat['partner_id']);
                                    $bbPartnerName = $bbPartner->name ?? 'Deleted User';
                                    $bbLast = $bbChat['last'];
                                @endphp
                                <a
                                    href="{{ route('messages.thread', $bbChat['partner_id']) }}"
                                    class="bb-pop-item {{ $bbChat['unread'] > 0 ? 'is-unread' : '' }}"
                                >
                                    <span class="bb-user-avatar">
                                        @if(!empty($bbPartner?->profile_photo))
                                            <img src="{{ asset('storage/profile-photos/' . $bbPartner->profile_photo) }}" alt="">
                                        @else
                                            {{ strtoupper(substr($bbPartnerName, 0, 1)) }}
                                        @endif
                                    </span>

                                    <span class="bb-pop-body">
                                        <span class="bb-pop-title">
                                            @if($bbChat['unread'] > 0)
                                                <i class="bb-notif-dot" aria-label="Unread"></i>
                                            @endif
                                            {{ $bbPartnerName }}
                                        </span>
                                        <span class="bb-pop-sub">
                                            {{ (int) $bbLast->sender_id === $bbMeId ? 'You: ' : '' }}{{ $bbLast->message }}
                                        </span>
                                    </span>

                                    <span class="bb-pop-time">
                                        {{ $bbLast->created_at && $bbLast->created_at->gt(now()->subMinute()) ? 'Just now' : $bbLast->created_at?->shortAbsoluteDiffForHumans() }}
                                    </span>
                                </a>
                            @endforeach
                        </div>

                    @endif

                    <a href="{{ route('messages.index') }}" class="bb-pop-all">Open Messages</a>

                </div>

            </div>

            {{-- 🔔 Notifications — hover shows the latest, click opens the list --}}
            <div class="bb-hover" data-hover-panel>

                <a
                    href="{{ route('notifications') }}"
                    class="bb-cart-link {{ $bbActive === 'notifications' ? 'active' : '' }}"
                    aria-label="Notifications{{ $bbNotificationCount > 0 ? ' (' . $bbNotificationCount . ' unread)' : '' }}"
                    title="Notifications"
                >
                    <i class="bi bi-bell-fill"></i>

                    <span
                        class="bb-cart-number"
                        style="{{ $bbNotificationCount > 0 ? '' : 'display:none;' }}"
                    >
                        {{ $bbNotificationCount > 99 ? '99+' : $bbNotificationCount }}
                    </span>
                </a>

                <div class="bb-pop">

                    <div class="bb-pop-head">
                        <strong>Recent Notifications</strong>

                        @if($bbNotificationCount > 0)
                            <form action="{{ route('notifications.read-all') }}" method="POST">
                                @csrf
                                <button type="submit">Mark all as read</button>
                            </form>
                        @endif
                    </div>

                    @if($bbRecentNotifications->isEmpty())

                        <div class="bb-pop-empty">
                            <i class="bi bi-bell-slash"></i>
                            No notifications yet.
                        </div>

                    @else

                        <div class="bb-pop-list">
                            @foreach($bbRecentNotifications as $bbNotification)
                                <a
                                    href="{{ route('notifications.open', $bbNotification->id) }}"
                                    class="bb-notif-item {{ $bbNotification->read_at ? '' : 'is-unread' }}"
                                >
                                    <span class="bb-notif-icon">
                                        <i class="bi {{ $bbNotification->iconClass() }}"></i>
                                    </span>

                                    <span class="bb-notif-body">
                                        <span class="bb-notif-title">
                                            @unless($bbNotification->read_at)
                                                <i class="bb-notif-dot" aria-label="Unread"></i>
                                            @endunless
                                            <span>{{ $bbNotification->title }}</span>
                                        </span>
                                        <span class="bb-notif-message">{{ $bbNotification->message }}</span>
                                        <span class="bb-notif-time" style="display:block;">{{ $bbNotification->created_at && $bbNotification->created_at->gt(now()->subMinute()) ? 'Just now' : $bbNotification->created_at?->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>

                    @endif

                    <a href="{{ route('notifications') }}" class="bb-pop-all">View All</a>

                </div>

            </div>

        @endif

        {{-- 🛒 Cart — hover shows what's in it (re-fetched on hover), click opens the cart.
             Logged-in only: guests can't add to a cart, so theirs would always be empty. --}}
        @if($bbNavUser)
        <div class="bb-hover" data-hover-panel data-hover-refresh="{{ route('cart.preview') }}">

            <a href="{{ route('cart') }}" class="bb-cart-link {{ $bbActive === 'cart' ? 'active' : '' }}" aria-label="Cart" title="Cart">
                <i class="bi bi-cart-fill"></i>

                <span
                    class="bb-cart-number"
                    id="cartCount"
                    style="{{ $bbCartCount > 0 ? '' : 'display:none;' }}"
                >{{ $bbCartCount }}</span>
            </a>

            <div class="bb-pop" style="width:360px;" data-hover-body>
                @include('partials.navbar-cart-preview')
            </div>

        </div>
        @endif

        @if($bbNavUser)

            @php
                $bbDisplayName = $bbNavUser['name'] ?? 'Buyer';
                $bbInitial = strtoupper(substr($bbDisplayName, 0, 1));
            @endphp

            @php
                $bbAvatar = !empty($bbNavUser['profile_photo'])
                    ? asset('storage/profile-photos/' . $bbNavUser['profile_photo'])
                    : null;
            @endphp

            <div class="bb-account" id="bbAccount">

                <button
                    type="button"
                    class="bb-user-chip"
                    id="bbAccountToggle"
                    aria-haspopup="menu"
                    aria-expanded="false"
                    aria-controls="bbAccountMenu"
                >
                    <span class="bb-user-avatar">
                        @if($bbAvatar)
                            <img src="{{ $bbAvatar }}" alt="">
                        @else
                            {{ $bbInitial }}
                        @endif
                    </span>
                    <span class="bb-chip-text">{{ $bbDisplayName }}</span>
                    <i class="bi bi-chevron-down bb-chip-caret"></i>
                </button>

                <div class="bb-account-menu" id="bbAccountMenu" role="menu" aria-labelledby="bbAccountToggle">

                    <div class="bb-account-head">
                        <span class="bb-user-avatar">
                            @if($bbAvatar)
                                <img src="{{ $bbAvatar }}" alt="">
                            @else
                                {{ $bbInitial }}
                            @endif
                        </span>
                        <div style="min-width:0;">
                            <div class="bb-account-name">{{ $bbDisplayName }}</div>
                            <div class="bb-account-email">{{ $bbNavUser['email'] ?? '' }}</div>
                        </div>
                    </div>

                    <a href="{{ route('buyer.profile') }}" role="menuitem" class="{{ $bbActive === 'profile' ? 'active' : '' }}">
                        <i class="bi bi-person-fill"></i> My Profile
                    </a>

                    <a href="{{ route('buyer.orders') }}" role="menuitem" class="{{ $bbActive === 'orders' ? 'active' : '' }}">
                        <i class="bi bi-box-seam-fill"></i> My Orders
                    </a>

                    <a href="{{ route('wishlist.index') }}" role="menuitem" class="{{ $bbActive === 'wishlist' ? 'active' : '' }}">
                        <i class="bi bi-heart-fill"></i> Wishlist
                    </a>

                    <a href="{{ route('complaints.index') }}" role="menuitem" class="{{ $bbActive === 'complaints' ? 'active' : '' }}">
                        <i class="bi bi-exclamation-triangle-fill"></i> Complaints
                    </a>

                    <form
                        action="{{ route('logout') }}"
                        method="POST"
                        onsubmit="return bbConfirmSubmit(event, this, 'Are you sure you want to log out?');"
                    >
                        @csrf
                        <button type="submit" role="menuitem" class="bb-account-logout">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>

                </div>

            </div>

            <script>
            (function () {
                // Shared behaviour for the navbar dropdowns (notifications
                // and account): click to toggle, only one open at a time,
                // arrow keys move between items, Esc / outside click closes.
                var dropdowns = [];

                function dropdown(rootId, toggleId, menuId, itemSelector) {
                    var root = document.getElementById(rootId);
                    var toggle = document.getElementById(toggleId);
                    var menu = document.getElementById(menuId);

                    if (!root || !toggle || !menu) return;

                    function items() {
                        return Array.prototype.slice.call(menu.querySelectorAll(itemSelector));
                    }

                    function setOpen(open, focusFirst) {
                        if (open) {
                            dropdowns.forEach(function (d) { if (d.root !== root) d.setOpen(false); });
                        }
                        root.classList.toggle('is-open', open);
                        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                        if (open && focusFirst && items()[0]) items()[0].focus();
                    }

                    dropdowns.push({ root: root, setOpen: setOpen });

                    toggle.addEventListener('click', function () {
                        setOpen(!root.classList.contains('is-open'), false);
                    });

                    toggle.addEventListener('keydown', function (e) {
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            setOpen(true, true);
                        }
                    });

                    menu.addEventListener('keydown', function (e) {
                        var list = items();
                        var i = list.indexOf(document.activeElement);

                        if (e.key === 'ArrowDown' && list.length) {
                            e.preventDefault();
                            list[(i + 1) % list.length].focus();
                        } else if (e.key === 'ArrowUp' && list.length) {
                            e.preventDefault();
                            list[(i - 1 + list.length) % list.length].focus();
                        } else if (e.key === 'Escape') {
                            setOpen(false);
                            toggle.focus();
                        }
                    });

                    document.addEventListener('mousedown', function (e) {
                        if (!root.contains(e.target)) setOpen(false);
                    });

                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape' && root.classList.contains('is-open')) setOpen(false);
                    });

                    root.addEventListener('focusout', function (e) {
                        if (e.relatedTarget && !root.contains(e.relatedTarget)) setOpen(false);
                    });
                }

                dropdown('bbAccount', 'bbAccountToggle', 'bbAccountMenu', '[role="menuitem"]');
            })();
            </script>

        @else

            <a href="{{ route('login') }}" class="bb-login-link">
                Login
            </a>

            <a href="{{ route('register') }}" class="bb-register-link">
                Register
            </a>

        @endif

    </div>

</nav>

@if($bbNavUser && ($bbNavUser['role'] ?? '') === 'buyer')

    @php
        // Pages reached from the Me screen keep "Me" highlighted.
        $bbTab = match ($bbActive) {
            'home' => 'home',
            'shop' => 'shop',
            'orders' => 'orders',
            'messages' => 'messages',
            'account', 'profile', 'wishlist', 'complaints', 'notifications' => 'me',
            default => null,
        };
    @endphp

    {{-- 📱 Bottom tab bar on phones --}}
    <nav class="bb-tabbar" aria-label="Main">
        <a href="{{ route('buyer.dashboard') }}" class="{{ $bbTab === 'home' ? 'active' : '' }}" @if($bbTab === 'home') aria-current="page" @endif>
            <i class="bi {{ $bbTab === 'home' ? 'bi-house-door-fill' : 'bi-house-door' }}"></i>Home
        </a>
        <a href="{{ route('products') }}" class="{{ $bbTab === 'shop' ? 'active' : '' }}" @if($bbTab === 'shop') aria-current="page" @endif>
            <i class="bi {{ $bbTab === 'shop' ? 'bi-grid-fill' : 'bi-grid' }}"></i>Shop
        </a>
        <a href="{{ route('buyer.orders') }}" class="{{ $bbTab === 'orders' ? 'active' : '' }}" @if($bbTab === 'orders') aria-current="page" @endif>
            <i class="bi {{ $bbTab === 'orders' ? 'bi-box-seam-fill' : 'bi-box-seam' }}"></i>Orders
        </a>
        <a href="{{ route('messages.index') }}" class="{{ $bbTab === 'messages' ? 'active' : '' }}" @if($bbTab === 'messages') aria-current="page" @endif aria-label="Chat{{ $bbMessageCount > 0 ? ' (' . $bbMessageCount . ' unread)' : '' }}">
            @if($bbMessageCount > 0)
                <span class="bb-tabbar-count">{{ $bbMessageCount > 99 ? '99+' : $bbMessageCount }}</span>
            @endif
            <i class="bi {{ $bbTab === 'messages' ? 'bi-chat-dots-fill' : 'bi-chat-dots' }}"></i>Chat
        </a>
        <a href="{{ route('buyer.account') }}" class="{{ $bbTab === 'me' ? 'active' : '' }}" @if($bbTab === 'me') aria-current="page" @endif>
            <i class="bi {{ $bbTab === 'me' ? 'bi-person-fill' : 'bi-person' }}"></i>Me
        </a>
    </nav>

    <script>document.body.classList.add('has-bb-tabbar');</script>

@endif

<script>
(function () {
    // 💬 🔔 🛒 hover panels: open when the mouse rests on the icon, stay
    // open while it moves into the panel, close shortly after it leaves.
    // Clicking the icon still goes to its page. Touch screens (no hover)
    // just use the links. Keyboard users get the panel on focus.
    var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    var OPEN_DELAY = 150;
    var CLOSE_DELAY = 250;

    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-hover-panel]'));

    panels.forEach(function (root) {
        var openTimer = null;
        var closeTimer = null;
        var refreshUrl = root.getAttribute('data-hover-refresh');
        var body = root.querySelector('[data-hover-body]');

        function refresh() {
            // e.g. the cart: items may have been added with AJAX since the page loaded.
            if (!refreshUrl || !body) return;

            fetch(refreshUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.ok ? res.text() : null; })
                .then(function (html) { if (html) body.innerHTML = html; })
                .catch(function () { /* keep what's already shown */ });
        }

        function open() {
            clearTimeout(closeTimer);
            if (root.classList.contains('is-open')) return;

            panels.forEach(function (other) { if (other !== root) other.classList.remove('is-open'); });
            root.classList.add('is-open');
            refresh();
        }

        function close() {
            clearTimeout(openTimer);
            root.classList.remove('is-open');
        }

        if (canHover) {
            root.addEventListener('mouseenter', function () {
                clearTimeout(closeTimer);
                openTimer = setTimeout(open, OPEN_DELAY);
            });

            root.addEventListener('mouseleave', function () {
                clearTimeout(openTimer);
                closeTimer = setTimeout(close, CLOSE_DELAY);
            });
        }

        root.addEventListener('focusin', open);

        root.addEventListener('focusout', function (e) {
            if (!e.relatedTarget || !root.contains(e.relatedTarget)) close();
        });

        root.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                close();
                var link = root.querySelector('.bb-cart-link');
                if (link) link.focus();
            }
        });
    });
})();
</script>

<script>
    // Some browsers bring a page back from the back/forward cache even with
    // no-store, showing an old cart and cart count. Ask the server again.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) window.location.reload();
    });
</script>

@include('partials.confirm-modal')
@include('partials.search-suggest')
