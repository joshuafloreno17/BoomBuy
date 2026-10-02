<style>
    .bb-navbar {
        width: 100%;
        max-width: 100%;
        height: 72px;

        background: white;
        border-bottom: 1px solid #ffe9e2;

        padding: 16px 7%;

        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        column-gap: 20px;

        position: sticky;
        top: 0;
        z-index: 100;

        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .bb-navbar a {
        text-decoration: none;
    }

    .bb-nav-left {
        display: flex;
        align-items: center;
        min-width: 0;
    }

    .bb-logo {
        flex-shrink: 0;

        font-family: 'Baloo 2', 'Plus Jakarta Sans', sans-serif;
        font-size: 23px;
        font-weight: 800;
        color: #e8420f;
    }

    .bb-logo span {
        color: #172033;
    }

    .bb-nav-links {
        display: flex;
        align-items: center;

        gap: 22px;

        margin-left: 40px;

        min-width: 0;
    }

    .bb-nav-links a {
        color: #8d6c62;
        font-size: 13px;
        font-weight: 700;

        padding: 8px 2px;

        white-space: nowrap;

        transition: 0.2s;
    }

    .bb-nav-links a:hover,
    .bb-nav-links a.active {
        color: #e8420f;
    }

    .bb-nav-search {
        display: flex;
        align-items: stretch;

        height: 44px;

        background: #fff;
        border: 2px solid #172033;
        border-radius: 13px;

        width: 100%;
        max-width: 560px;
        margin: 0 auto;
        min-width: 0;
    }

    .bb-nav-search input {
        flex: 1;
        min-width: 0;

        border: none;
        background: transparent;
        outline: none;

        padding: 0 14px;

        font-family: inherit;
        font-size: 13px;
        color: #33241f;
    }

    .bb-nav-search input::placeholder {
        color: #a38f88;
    }

    .bb-nav-search .bb-nav-search-btn {
        flex-shrink: 0;

        display: inline-flex;
        align-items: center;
        gap: 7px;

        border: none;
        border-radius: 0 10px 10px 0;
        background: #172033;
        color: #fff;
        cursor: pointer;

        padding: 0 18px;

        font-family: inherit;
        font-size: 13px;
        font-weight: 800;

        transition: background 0.2s ease;
    }

    .bb-nav-search .bb-nav-search-btn:hover {
        background: #2a3550;
    }

    /* =========================
       MOBILE BOTTOM TAB BAR (buyers)
    ========================= */

    .bb-tabbar {
        display: none;
    }

    @media (max-width: 760px) {
        body.has-bb-tabbar {
            padding-bottom: calc(68px + env(safe-area-inset-bottom));
        }

        .bb-tabbar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 900;

            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));

            height: calc(68px + env(safe-area-inset-bottom));
            padding-bottom: env(safe-area-inset-bottom);

            background: #fff;
            border-top: 1px solid #f7e5e0;
            box-shadow: 0 -6px 20px -14px rgba(23, 32, 51, 0.25);

            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .bb-tabbar a {
            position: relative;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;

            color: #6f5a53;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }

        .bb-tabbar a i {
            font-size: 20px;
        }

        .bb-tabbar a.active {
            color: #c43408;
            font-weight: 800;
        }

        .bb-tabbar-count {
            position: absolute;
            top: 8px;
            left: calc(50% + 4px);

            min-width: 17px;
            height: 17px;
            padding: 0 4px;

            border-radius: 999px;
            background: #c43408;
            color: #fff;

            font-size: 10px;
            font-weight: 800;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Home/Shop/Orders, Messages and the account menu live in the tab bar on phones. */
        .has-bb-tabbar .bb-nav-links,
        .has-bb-tabbar .bb-nav-messages,
        .has-bb-tabbar .bb-account {
            display: none;
        }
    }

    .bb-nav-right {
        display: flex;
        align-items: center;
        gap: 14px;

        flex-shrink: 0;
    }

    .bb-nav-right form {
        margin: 0;
    }

    .bb-nav-right .bb-cart-link {
        position: relative;

        color: #8d6c62;
        font-size: 18px;

        transition: 0.2s;
    }

    .bb-nav-right .bb-cart-link:hover,
    .bb-nav-right .bb-cart-link.active {
        color: #e8420f;
    }

    .bb-nav-right .bb-cart-number {
        position: absolute;
        top: -8px;
        right: -10px;

        min-width: 17px;
        height: 17px;

        padding: 0 4px;

        font-size: 9.5px;
    }

    .bb-user-chip {
        display: flex;
        align-items: center;
        gap: 8px;

        padding: 6px 10px 6px 6px;

        background: #fff4f1;
        border: none;
        cursor: pointer;

        border-radius: 30px;

        font-family: inherit;
        font-size: 13px;
        font-weight: 700;

        color: #172033;

        white-space: nowrap;

        transition: 0.2s ease;
    }

    .bb-user-chip:hover,
    .bb-account.is-open .bb-user-chip {
        background: #ffe4dc;
    }

    .bb-user-chip:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(232, 66, 15, 0.2);
    }

    .bb-chip-caret {
        font-size: 10px;
        color: #8d6c62;
        transition: transform 0.2s ease;
    }

    .bb-account.is-open .bb-chip-caret {
        transform: rotate(180deg);
    }

    /* =========================
       ACCOUNT DROPDOWN
    ========================= */

    .bb-account {
        position: relative;
    }

    .bb-account-menu {
        display: none;

        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        z-index: 4000;

        width: 250px;

        background: #fff;
        border: 1px solid #f7e5e0;
        border-radius: 14px;
        box-shadow: 0 18px 40px -12px rgba(23, 32, 51, 0.22);

        padding: 6px;

        animation: bbAccountIn 0.16s ease;
    }

    .bb-account.is-open .bb-account-menu {
        display: block;
    }

    @keyframes bbAccountIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .bb-account-head {
        display: flex;
        align-items: center;
        gap: 10px;

        padding: 10px 10px 12px;
        margin-bottom: 4px;

        border-bottom: 1px solid #f7e5e0;
    }

    .bb-account-head .bb-user-avatar {
        width: 38px;
        height: 38px;
        font-size: 15px;
    }

    .bb-account-name {
        font-size: 13.5px;
        font-weight: 800;
        color: #172033;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bb-account-email {
        font-size: 11.5px;
        color: #8d6c62;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bb-account-menu a,
    .bb-account-menu button {
        display: flex;
        align-items: center;
        gap: 10px;

        width: 100%;
        padding: 9px 10px;

        border: none;
        border-radius: 9px;
        background: none;
        cursor: pointer;

        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        color: #33241f;
        text-align: left;
    }

    .bb-account-menu a i,
    .bb-account-menu button i {
        width: 16px;
        text-align: center;
        color: #a99088;
    }

    .bb-account-menu a:hover,
    .bb-account-menu a:focus-visible,
    .bb-account-menu button:hover,
    .bb-account-menu button:focus-visible {
        outline: none;
        background: #fff4ef;
    }

    .bb-account-menu a.active {
        color: #e8420f;
    }

    .bb-account-menu a.active i {
        color: #e8420f;
    }

    .bb-account-menu form {
        margin: 4px 0 0;
        padding-top: 4px;
        border-top: 1px solid #f7e5e0;
    }

    .bb-account-menu .bb-account-logout,
    .bb-account-menu .bb-account-logout i {
        color: #dc2626;
    }

    .bb-account-menu .bb-account-logout:hover {
        background: #fff1f1;
    }

    /* =========================
       NOTIFICATIONS DROPDOWN
    ========================= */

    .bb-hover {
        position: relative;
        display: flex;
    }

    .bb-hover.is-open > .bb-cart-link {
        color: #e8420f;
    }

    .bb-pop {
        display: none;

        position: absolute;
        top: calc(100% + 14px);
        right: -12px;
        z-index: 4000;

        width: 370px;

        background: #fff;
        border: 1px solid #f7e5e0;
        border-radius: 14px;
        box-shadow: 0 18px 40px -12px rgba(23, 32, 51, 0.22);

        overflow: hidden;

        animation: bbAccountIn 0.16s ease;
    }

    .bb-hover.is-open .bb-pop {
        display: block;
    }

    .bb-pop-head {
        display: flex;
        align-items: center;
        justify-content: space-between;

        padding: 12px 14px 10px;
        border-bottom: 1px solid #f7e5e0;
    }

    .bb-pop-head strong {
        font-size: 13.5px;
        font-weight: 800;
        color: #172033;
    }

    .bb-pop-head form {
        margin: 0;
    }

    .bb-pop-head button {
        border: none;
        background: none;
        cursor: pointer;

        font-family: inherit;
        font-size: 11.5px;
        font-weight: 700;
        color: #e8420f;
    }

    .bb-pop-list {
        max-height: 420px;
        overflow-y: auto;
    }

    .bb-notif-item {
        display: flex;
        gap: 11px;

        padding: 11px 14px;

        border-bottom: 1px solid #fbf0ec;

        color: inherit;
        transition: background 0.15s ease;
    }

    .bb-notif-item:hover,
    .bb-notif-item:focus-visible {
        outline: none;
        background: #fff7f4;
    }

    .bb-notif-item.is-unread {
        background: #fff4ef;
    }

    .bb-notif-item.is-unread:hover {
        background: #ffece4;
    }

    .bb-notif-icon {
        width: 36px;
        height: 36px;
        flex-shrink: 0;

        border-radius: 10px;
        background: #ffefea;
        color: #e8420f;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 15px;
    }

    .bb-notif-body {
        flex: 1;
        min-width: 0;
    }

    .bb-notif-title {
        display: flex;
        align-items: center;
        gap: 6px;

        font-size: 13px;
        font-weight: 700;
        color: #172033;
    }

    .bb-notif-title span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bb-notif-dot {
        width: 7px;
        height: 7px;
        flex-shrink: 0;
        border-radius: 50%;
        background: #e8420f;
    }

    .bb-notif-message {
        margin-top: 2px;

        font-size: 12px;
        line-height: 1.45;
        color: #6b5048;

        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .bb-notif-time {
        margin-top: 4px;
        font-size: 11px;
        color: #a99088;
    }

    .bb-pop-empty {
        padding: 34px 20px;
        text-align: center;
        font-size: 13px;
        color: #8d6c62;
    }

    .bb-pop-empty i {
        display: block;
        margin-bottom: 8px;
        font-size: 24px;
        color: #e2c9c1;
    }

    .bb-pop-all {
        display: block;

        padding: 12px;

        text-align: center;
        font-size: 13px;
        font-weight: 700;
        color: #e8420f;

        background: #fffaf8;
    }

    .bb-pop-all:hover {
        background: #fff1ec;
    }

    /* Invisible bridge over the gap between icon and panel, so moving the
       mouse down into the panel doesn't count as leaving it. */
    .bb-pop::before {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        top: -16px;
        height: 16px;
    }

    .bb-pop-head .bb-pop-link {
        font-size: 11.5px;
        font-weight: 700;
        color: #e8420f;
    }

    .bb-pop-item {
        display: flex;
        align-items: center;
        gap: 11px;

        padding: 10px 14px;
        border-bottom: 1px solid #fbf0ec;

        color: inherit;
        transition: background 0.15s ease;
    }

    .bb-pop-item:hover,
    .bb-pop-item:focus-visible {
        outline: none;
        background: #fff7f4;
    }

    .bb-pop-item.is-unread {
        background: #fff4ef;
    }

    .bb-pop-thumb {
        width: 40px;
        height: 40px;
        flex-shrink: 0;

        border-radius: 9px;
        background: #ffefea;
        object-fit: cover;

        display: flex;
        align-items: center;
        justify-content: center;

        color: #e8420f;
        font-size: 16px;
    }

    .bb-pop-item .bb-user-avatar {
        width: 38px;
        height: 38px;
        font-size: 15px;
    }

    .bb-pop-body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .bb-pop-title {
        display: flex;
        align-items: center;
        gap: 6px;

        font-size: 13px;
        font-weight: 700;
        color: #172033;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bb-pop-sub {
        margin-top: 2px;

        font-size: 12px;
        color: #8d6c62;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bb-pop-item.is-unread .bb-pop-sub {
        color: #33241f;
        font-weight: 600;
    }

    .bb-pop-price {
        flex-shrink: 0;
        font-size: 12.5px;
        font-weight: 800;
        color: #e8420f;
        white-space: nowrap;
    }

    .bb-pop-time {
        flex-shrink: 0;
        align-self: flex-start;
        margin-top: 2px;

        font-size: 11px;
        color: #a99088;
        white-space: nowrap;
    }

    .bb-pop-foot {
        padding: 12px 14px;
        background: #fffaf8;
    }

    .bb-pop-summary {
        display: flex;
        justify-content: space-between;
        gap: 10px;

        margin-bottom: 10px;

        font-size: 12px;
        color: #8d6c62;
    }

    .bb-pop-summary strong {
        color: #172033;
        font-size: 13px;
    }

    .bb-pop-actions {
        display: flex;
        gap: 8px;
    }

    .bb-pop-btn {
        flex: 1;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        padding: 9px 12px;

        border: 1px solid #f3c6ba;
        border-radius: 10px;
        background: #fff;

        font-size: 12.5px;
        font-weight: 800;
        color: #e8420f;
    }

    .bb-pop-btn:hover {
        background: #fff4ef;
    }

    .bb-pop-btn.is-primary {
        background: #e8420f;
        border-color: #e8420f;
        color: #fff;
    }

    .bb-pop-btn.is-primary:hover {
        background: #c43408;
    }

    .bb-pop-empty .bb-pop-btn {
        display: flex;
        width: fit-content;
        margin: 12px auto 0;
        padding: 9px 20px;
    }

    @media (max-width: 600px) {
        .bb-hover {
            position: static;
        }

        /* Full-width panel just under the navbar on small screens. */
        .bb-pop {
            left: 12px;
            right: 12px;
            width: auto;
            top: calc(100% + 6px);
        }
    }

    .bb-user-avatar {
        width: 26px;
        height: 26px;

        border-radius: 50%;

        background: #e8420f;
        color: white;

        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;

        font-size: 12px;
        font-weight: 800;

        flex-shrink: 0;
    }

    .bb-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .bb-cart-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .bb-cart-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        min-width: 20px;
        height: 20px;

        padding: 0 5px;

        background: #ef4444;
        color: white;

        border-radius: 50%;

        font-size: 11px;
        font-weight: 700;
    }

    .bb-login-link {
        color: #e8420f;
        background: #fff;
        border: 1px solid #f7d9cf;

        padding: 9px 16px;
        border-radius: 10px;

        font-size: 12px;
        font-weight: 800;

        white-space: nowrap;
        transition: 0.2s ease;
    }

    .bb-login-link:hover {
        background: #fff3f0;
    }

    .bb-register-link {
        background: #e8420f;
        color: white;

        padding: 10px 16px;
        border-radius: 10px;

        font-size: 12px;
        font-weight: 800;

        white-space: nowrap;
        transition: 0.2s ease;
    }

    .bb-register-link:hover {
        background: #c43408;
    }

    @media (max-width: 1100px) {
        .bb-nav-links {
            gap: 18px;
            margin-left: 25px;
        }

        .bb-nav-links a {
            font-size: 12px;
        }

        .bb-nav-search {
            max-width: 320px;
        }

        .bb-nav-search-btn span {
            display: none;
        }
    }

    @media (max-width: 900px) {
        /* Phones: logo + actions, then the search, then the page links on
           their own row (so they never squeeze next to Login / Register). */
        .bb-navbar {
            grid-template-columns: 1fr auto;
            grid-template-areas:
                "logo right"
                "search search"
                "links links";

            height: auto;
            min-height: 72px;

            row-gap: 0;

            padding: 15px 20px;
        }

        .bb-nav-left {
            display: contents;
        }

        .bb-logo {
            grid-area: logo;
            align-self: center;
        }

        .bb-nav-search {
            grid-area: search;
            max-width: none;
            width: 100%;
            margin: 12px 0 0;
        }

        .bb-nav-right {
            grid-area: right;
        }

        .bb-logo {
            font-size: 21px;
        }

        .bb-nav-links {
            grid-area: links;

            width: 100%;

            justify-content: flex-start;

            margin: 10px 0 0;

            gap: 18px;

            flex-wrap: nowrap;
            overflow-x: auto;
        }

        .bb-nav-links a {
            font-size: 12px;
        }

        .bb-user-chip .bb-chip-text {
            display: none;
        }
    }

    @media (max-width: 450px) {
        .bb-nav-links {
            gap: 10px;
        }

        .bb-nav-links a {
            font-size: 11px;
        }

        .bb-nav-right {
            gap: 12px;
        }
    }
</style>

@php
    $bbNavUser = session()->get('user');
    $bbCartCount = array_sum(session()->get('cart', []));
    $bbActive = $activeNav ?? null;

    $bbNotificationCount = 0;
    $bbMessageCount = 0;

    if ($bbNavUser) {
        $bbNotificationCount = \App\Models\Notification::where(
            'user_id',
            $bbNavUser['id']
        )
        ->whereNull('read_at')
        ->count();

        $bbMessageCount = \App\Models\Message::where(
            'recipient_id',
            $bbNavUser['id']
        )
        ->whereNull('read_at')
        ->count();

        // Latest few for the bell hover panel.
        $bbRecentNotifications = \App\Models\Notification::where('user_id', $bbNavUser['id'])
            ->latest()
            ->limit(6)
            ->get();

        // Latest few conversations for the chat hover panel: newest message
        // per chat partner, plus how many of theirs are still unread.
        $bbMeId = (int) $bbNavUser['id'];

        $bbRecentChats = \App\Models\Message::where('sender_id', $bbMeId)
            ->orWhere('recipient_id', $bbMeId)
            ->latest()
            ->limit(200)
            ->get()
            ->groupBy(fn ($m) => (int) $m->sender_id === $bbMeId ? (int) $m->recipient_id : (int) $m->sender_id)
            ->take(5)
            ->map(fn ($thread, $partnerId) => [
                'partner_id' => $partnerId,
                'last' => $thread->first(),
                'unread' => $thread->where('recipient_id', $bbMeId)->whereNull('read_at')->count(),
            ]);

        $bbChatPartners = \App\Models\User::whereIn('id', $bbRecentChats->keys())->get(['id', 'name', 'profile_photo'])->keyBy('id');
    }
@endphp

<nav class="bb-navbar">

    <div class="bb-nav-left">

    <a href="{{ $bbNavUser ? route('buyer.dashboard') : route('home') }}" class="bb-logo">
        Boom<span>Buy</span>
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
