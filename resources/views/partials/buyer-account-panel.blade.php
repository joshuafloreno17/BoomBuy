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
<link rel="stylesheet" href="{{ vasset('css/partials/buyer-account-panel.css') }}">
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
