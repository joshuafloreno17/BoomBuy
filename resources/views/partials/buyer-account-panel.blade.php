{{--
    Buyer account panel. Needs $panel from BuyerController::accountPanelData().

    - Dashboard sidebar (desktop): only what the navbar does not already have —
      My Purchases shortcuts, COD standing and help. Profile and account links
      live in the navbar account menu, so they are not repeated here.
    - $panelVariant = 'page': the mobile "Me" screen (buyer.account), which also
      carries the profile, the account menu and Log out, because phones hide the
      navbar account menu in favour of the bottom tab bar.
--}}
@php
    $panelVariant = $panelVariant ?? 'sidebar';
    $isPage = $panelVariant === 'page';
    $cod = $panel['cod'];
    $initial = strtoupper(substr($panel['name'], 0, 1));

    // Me screen menu — Home, Orders and Chat are already in the bottom tab bar.
    $panelLinks = [
        ['label' => 'Wishlist', 'icon' => 'bi-heart-fill', 'url' => route('wishlist.index'), 'count' => 0],
        ['label' => 'Notifications', 'icon' => 'bi-bell-fill', 'url' => route('notifications'), 'count' => $panel['unread_notifications']],
        ['label' => 'Complaints', 'icon' => 'bi-exclamation-triangle-fill', 'url' => route('complaints.index'), 'count' => 0],
        ['label' => 'Profile & Addresses', 'icon' => 'bi-person-fill', 'url' => route('buyer.profile') . '#addresses', 'count' => 0],
    ];

    $shortcuts = [
        ['label' => 'To Ship', 'icon' => 'bi-box-seam', 'tab' => 'to-ship', 'count' => $panel['to_ship']],
        ['label' => 'To Receive', 'icon' => 'bi-truck', 'tab' => 'to-receive', 'count' => $panel['to_receive']],
        ['label' => 'To Review', 'icon' => 'bi-star', 'tab' => 'delivered', 'count' => $panel['to_review']],
        ['label' => 'Cancelled', 'icon' => 'bi-arrow-counterclockwise', 'tab' => 'cancelled', 'count' => 0],
    ];
@endphp

@once
<style>
    .bb-acct {
        display: flex;
        flex-direction: column;
        gap: 16px;
        min-width: 0;
    }

    .bb-acct-card {
        background: #fff;
        border: 1px solid var(--line, #f7e5e0);
        border-radius: 20px;
        padding: 20px;
    }

    .bb-acct-profile {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .bb-acct-avatar {
        width: 52px;
        height: 52px;
        flex-shrink: 0;
        border-radius: 50%;
        overflow: hidden;
        background: var(--accent, #e8420f);
        color: #fff;
        font-family: var(--font-display);
        font-size: 22px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bb-acct-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .bb-acct-name {
        font-size: 15px;
        font-weight: 800;
        color: var(--ink, #172033);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bb-acct-sub {
        font-size: 12px;
        color: #6f5a53;
    }

    .bb-acct-edit {
        font-size: 12px;
        font-weight: 700;
        color: var(--accent-dark, #c43408);
    }

    /* Me screen: dark profile header */
    .bb-acct.is-page .bb-acct-profile-card {
        background: var(--ink, #172033);
        border-color: var(--ink, #172033);
    }

    .bb-acct.is-page .bb-acct-name {
        color: #fff;
        font-size: 17px;
    }

    .bb-acct.is-page .bb-acct-sub {
        color: #cfd4df;
    }

    .bb-acct.is-page .bb-acct-edit {
        color: #ff9a76;
    }

    .bb-acct.is-page .bb-acct-avatar {
        width: 60px;
        height: 60px;
        font-size: 26px;
    }

    .bb-acct-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 14px;
        font-weight: 800;
        color: var(--ink, #172033);
    }

    .bb-acct-head a {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--accent-dark, #c43408);
    }

    .bb-acct-shortcuts {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 4px;
    }

    .bb-acct-shortcut {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 4px 0;
        font-size: 11.5px;
        font-weight: 700;
        color: var(--ink, #172033);
        text-align: center;
    }

    .bb-acct-shortcut-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: #ffefea;
        color: var(--accent-dark, #c43408);
        font-size: 19px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bb-acct-shortcut .bb-acct-count {
        position: absolute;
        top: -2px;
        right: calc(50% - 30px);
    }

    /* Narrow desktop sidebar: 2 × 2 tiles with the label beside the icon. */
    .bb-acct:not(.is-page) .bb-acct-shortcuts {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .bb-acct:not(.is-page) .bb-acct-shortcut {
        flex-direction: row;
        gap: 7px;
        padding: 7px;
        white-space: nowrap;
        border: 1px solid var(--line, #f7e5e0);
        border-radius: 12px;
        font-size: 12px;
        text-align: left;
        transition: background 0.15s ease;
    }

    .bb-acct:not(.is-page) .bb-acct-shortcut:hover {
        background: #fff7f4;
    }

    .bb-acct:not(.is-page) .bb-acct-shortcut-icon {
        width: 28px;
        height: 28px;
        flex-shrink: 0;
        border-radius: 10px;
        font-size: 15px;
    }

    .bb-acct:not(.is-page) .bb-acct-shortcut .bb-acct-count {
        top: -7px;
        right: -5px;
    }

    .bb-acct-nav {
        padding: 8px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .bb-acct-nav a {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 46px;
        padding: 0 12px;
        border-radius: 12px;
        font-size: 13.5px;
        font-weight: 700;
        color: #5b4a44;
        transition: background 0.15s ease;
    }

    .bb-acct-nav a:hover {
        background: #fff7f4;
    }

    .bb-acct-nav a.active {
        background: #fff1ec;
        color: var(--accent-dark, #c43408);
    }

    .bb-acct-nav a i {
        width: 18px;
        text-align: center;
        font-size: 15px;
    }

    .bb-acct-nav a span {
        flex: 1;
    }

    .bb-acct-nav .bi-chevron-right {
        color: #b99c93;
        font-size: 13px;
    }

    .bb-acct-count {
        min-width: 22px;
        height: 22px;
        padding: 0 7px;
        border-radius: 999px;
        background: var(--accent-dark, #c43408);
        color: #fff;
        font-size: 11px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .bb-acct-cod-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
        font-size: 13.5px;
        font-weight: 800;
        color: var(--ink, #172033);
    }

    .bb-acct-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .bb-acct-pill.is-good { background: var(--teal-bg, #e3f6f4); color: var(--teal-dark, #0a6f66); }
    .bb-acct-pill.is-warn { background: #fff4d6; color: #7a5600; }
    .bb-acct-pill.is-blocked { background: #fdecea; color: #b42318; }

    .bb-acct-meter {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 5px;
        margin-bottom: 12px;
    }

    .bb-acct-meter span {
        height: 7px;
        border-radius: 999px;
        background: #f3e6e1;
    }

    .bb-acct-meter span.is-on { background: #d97706; }
    .bb-acct-meter.is-blocked span.is-on { background: #c62828; }

    .bb-acct-cod p {
        margin: 0;
        font-size: 12px;
        line-height: 1.55;
        color: #6f5a53;
    }

    .bb-acct-cod p strong {
        color: var(--ink, #172033);
    }

    .bb-acct-help {
        background: var(--ink, #172033);
        border-color: var(--ink, #172033);
        color: #fff;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .bb-acct-help strong {
        font-size: 14px;
    }

    .bb-acct-help span {
        font-size: 12.5px;
        color: #cfd4df;
        line-height: 1.5;
    }

    .bb-acct-help a {
        margin-top: 8px;
        min-height: 42px;
        border-radius: 10px;
        background: #fff;
        color: var(--ink, #172033);
        font-size: 13px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bb-acct-logout {
        width: 100%;
        min-height: 48px;
        border: 1px solid #f3c9bb;
        border-radius: 14px;
        background: #fff;
        color: #b42318;
        font-family: inherit;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
    }

    .bb-acct-logout:hover {
        background: #fff1f1;
    }
</style>
@endonce

<div class="bb-acct {{ $isPage ? 'is-page' : '' }}">

    {{-- PROFILE (Me screen only — on desktop it lives in the navbar account menu) --}}
    @if($isPage)
    <div class="bb-acct-card bb-acct-profile-card">
        <div class="bb-acct-profile">
            <span class="bb-acct-avatar">
                @if($panel['photo'])
                    <img src="{{ $panel['photo'] }}" alt="">
                @else
                    {{ $initial }}
                @endif
            </span>
            <div style="min-width:0;">
                <div class="bb-acct-name">{{ $panel['name'] }}</div>
                <div class="bb-acct-sub">Buyer{{ $panel['since'] ? ' · since ' . $panel['since'] : '' }}</div>
                <a href="{{ route('buyer.profile') }}" class="bb-acct-edit">Edit profile</a>
            </div>
        </div>
    </div>
    @endif

    {{-- MY PURCHASES --}}
        <div class="bb-acct-card">
            <div class="bb-acct-head">
                <span>My Purchases</span>
                <a href="{{ route('buyer.orders') }}">View all →</a>
            </div>
            <div class="bb-acct-shortcuts">
                @foreach($shortcuts as $shortcut)
                    <a href="{{ route('buyer.orders', ['tab' => $shortcut['tab']]) }}" class="bb-acct-shortcut">
                        @if($shortcut['count'] > 0)
                            <span class="bb-acct-count">{{ $shortcut['count'] > 99 ? '99+' : $shortcut['count'] }}</span>
                        @endif
                        <span class="bb-acct-shortcut-icon"><i class="bi {{ $shortcut['icon'] }}"></i></span>
                        {{ $shortcut['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

    {{-- COD STANDING (CodPolicy) --}}
    <div class="bb-acct-card bb-acct-cod">
        <div class="bb-acct-cod-top">
            <span>Cash on Delivery</span>
            @if($cod['blocked'])
                <span class="bb-acct-pill is-blocked"><i class="bi bi-slash-circle"></i> Paused</span>
            @elseif($cod['strikes'] > 0)
                <span class="bb-acct-pill is-warn"><i class="bi bi-exclamation-circle"></i> Be careful</span>
            @else
                <span class="bb-acct-pill is-good"><i class="bi bi-check-lg"></i> Good standing</span>
            @endif
        </div>

        <div class="bb-acct-meter {{ $cod['blocked'] ? 'is-blocked' : '' }}" aria-hidden="true">
            @for($i = 0; $i < $cod['limit']; $i++)
                <span class="{{ $i < $cod['strikes'] ? 'is-on' : '' }}"></span>
            @endfor
        </div>

        @if($cod['blocked'])
            <p>
                Cash on Delivery is paused on your account because of
                <strong>{{ $cod['strikes'] }} cancellations or refused parcels</strong>
                in the last {{ \App\Support\CodPolicy::WINDOW_DAYS }} days.
                It comes back on <strong>{{ $cod['available_at']->format('M j, Y') }}</strong>.
            </p>
        @else
            <p>
                <strong>{{ $cod['strikes'] }} of {{ $cod['limit'] }}</strong>
                cancellations or refused parcels in the last {{ \App\Support\CodPolicy::WINDOW_DAYS }} days.
                Reaching {{ $cod['limit'] }} pauses Cash on Delivery on your account.
            </p>
        @endif
    </div>

    {{-- ACCOUNT MENU (Me screen only — on desktop it is the navbar account menu) --}}
    @if($isPage)
    <nav class="bb-acct-card bb-acct-nav" aria-label="Account">
        @foreach($panelLinks as $link)
            <a href="{{ $link['url'] }}">
                <i class="bi {{ $link['icon'] }}"></i>
                <span>{{ $link['label'] }}</span>
                @if($link['count'] > 0)
                    <em class="bb-acct-count" style="font-style:normal;">{{ $link['count'] > 99 ? '99+' : $link['count'] }}</em>
                @endif
                <i class="bi bi-chevron-right"></i>
            </a>
        @endforeach
    </nav>
    @endif

    {{-- HELP --}}
    <div class="bb-acct-card bb-acct-help">
        <strong>Problem with an order?</strong>
        <span>Request a return or refund from a delivered order, or file a complaint with BoomBuy.</span>
        <a href="{{ route('complaints.index') }}">Get help</a>
    </div>

    @if($isPage)
        <form action="{{ route('logout') }}" method="POST" onsubmit="return bbConfirmSubmit(event, this, 'Are you sure you want to log out?');">
            @csrf
            <button type="submit" class="bb-acct-logout">Log out</button>
        </form>
    @endif

</div>
