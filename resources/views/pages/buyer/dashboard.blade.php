<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>BoomBuy — Buyer Home</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: var(--font-body);
            background: var(--cream);
            color: var(--ink);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
        }

        button {
            font-family: inherit;
        }

        /* =========================
           LAYOUT: sidebar + main
        ========================= */

        .dash {
            width: calc(100% - 40px);
            max-width: 1320px;
            margin: 32px auto 64px;

            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            gap: 32px;
            align-items: start;
        }

        .dash-side {
            position: sticky;
            top: 96px;
        }

        .dash-main {
            display: flex;
            flex-direction: column;
            gap: 40px;
            min-width: 0;
        }

        .dash-section {
            display: flex;
            flex-direction: column;
            gap: 14px;
            min-width: 0;
        }

        .dash-section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .dash-section-head h2 {
            font-family: var(--font-display);
            font-size: 26px;
            font-weight: 800;
            line-height: 1.15;
        }

        .dash-section-head p {
            margin-top: 2px;
            font-size: 13.5px;
            color: #6f5a53;
        }

        .dash-link {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--accent-dark);
            white-space: nowrap;
        }

        .dash-link:hover {
            color: #9e2a06;
        }

        /* =========================
           MOBILE GREETING (sidebar is hidden there)
        ========================= */

        .dash-greet {
            display: none;
            align-items: center;
            gap: 12px;
        }

        .dash-greet-avatar {
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            border-radius: 50%;
            overflow: hidden;
            background: var(--accent);
            color: #fff;
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dash-greet-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dash-greet-text {
            flex: 1;
            min-width: 0;
        }

        .dash-greet-text strong {
            display: block;
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
            line-height: 1.1;
        }

        .dash-greet-text span {
            font-size: 12px;
            color: #6f5a53;
        }

        .dash-cod-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 36px;
            padding: 0 12px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 800;
            white-space: nowrap;
        }

        .dash-cod-chip.is-good { background: var(--teal-bg); color: var(--teal-dark); }
        .dash-cod-chip.is-warn { background: #fff4d6; color: #7a5600; }
        .dash-cod-chip.is-blocked { background: #fdecea; color: #b42318; }

        /* =========================
           HERO BANNER
        ========================= */

        .dash-hero {
            position: relative;
            min-height: 250px;
            border-radius: 24px;
            background: #ffe7de;
            overflow: hidden;

            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
        }

        .dash-hero-text {
            position: relative;
            z-index: 1;
            padding: 36px 40px;

            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 12px;
        }

        .dash-hero-eyebrow {
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: 0.14em;
            color: #a32c06;
            text-transform: uppercase;
        }

        .dash-hero h1 {
            font-family: var(--font-display);
            font-size: 44px;
            font-weight: 800;
            line-height: 1.02;
        }

        .dash-hero p {
            font-size: 14.5px;
            color: #5b4a44;
            max-width: 440px;
            line-height: 1.55;
        }

        .dash-hero-cta {
            align-self: flex-start;
            margin-top: 6px;
            min-height: 44px;
            padding: 0 22px;
            border-radius: 12px;
            background: var(--ink);
            color: #fff;
            font-size: 13.5px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s ease;
        }

        .dash-hero-cta:hover {
            background: #2a3550;
        }

        .dash-hero-art {
            position: relative;
        }

        .dash-hero-tile {
            position: absolute;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 16px 30px -18px rgba(23, 32, 51, 0.45);
        }

        .dash-hero-tile.t1 { left: 8%; top: 40px; width: 150px; height: 180px; background: #fff; color: #3b4aa8; font-size: 50px; transform: rotate(-7deg); }
        .dash-hero-tile.t2 { left: 36%; top: 24px; width: 160px; height: 200px; background: var(--ink); color: #ff9a76; font-size: 54px; transform: rotate(4deg); }
        .dash-hero-tile.t3 { left: 66%; top: 70px; width: 130px; height: 150px; background: #fff4d6; color: #8a6300; font-size: 44px; transform: rotate(-3deg); }

        /* =========================
           ACTIVE ORDERS
        ========================= */

        .order-row {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 18px 22px;

            display: grid;
            grid-template-columns: 64px minmax(0, 1.1fr) minmax(0, 1.5fr) auto;
            align-items: center;
            gap: 22px;
        }

        .order-thumb {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: #ffefea;
            color: var(--accent-dark);
            font-size: 26px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .order-thumb,
        .again-thumb {
            position: relative;
        }

        .order-thumb img,
        .again-thumb img {
            position: absolute;
            inset: 0;
        }

        .order-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
            min-width: 0;
        }

        .order-meta {
            font-size: 12px;
            font-weight: 700;
            color: #6f5a53;
        }

        .order-name {
            font-size: 15px;
            font-weight: 800;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .order-progress {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .order-steps {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 5px;
        }

        .order-steps span {
            height: 6px;
            border-radius: 999px;
            background: #f3e6e1;
        }

        .order-steps span.is-on {
            background: var(--accent);
        }

        .order-note {
            font-size: 12px;
            color: #6f5a53;
        }

        .order-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            min-height: 42px;
            padding: 0 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .btn-primary {
            border: none;
            background: var(--accent-dark);
            color: #fff;
        }

        .btn-primary:hover {
            background: #9e2a06;
        }

        .btn-ghost {
            border: 1px solid #f0d9d1;
            background: #fff;
            color: #5b4a44;
            font-weight: 700;
        }

        .btn-ghost:hover {
            background: #fff7f4;
        }

        .btn-dark {
            border: none;
            background: var(--ink);
            color: #fff;
        }

        .btn-dark:hover {
            background: #2a3550;
        }

        .btn[disabled] {
            background: #ece4e1;
            color: #8d7c77;
            cursor: not-allowed;
        }

        /* =========================
           BUY AGAIN
        ========================= */

        .again-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .again-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-width: 0;
        }

        .again-top {
            display: flex;
            gap: 12px;
            align-items: center;
            min-width: 0;
        }

        .again-thumb {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            border-radius: 14px;
            background: #fff4d6;
            color: #8a6300;
            font-size: 22px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .again-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .again-name {
            font-size: 13px;
            font-weight: 800;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .again-price {
            display: block;
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 800;
            color: var(--accent-dark);
        }

        .again-btn {
            min-height: 40px;
            border: 1px solid #f0d9d1;
            border-radius: 10px;
            background: #fff7f4;
            color: var(--accent-dark);
            font-size: 12.5px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .again-btn:hover {
            background: #fff1ec;
        }

        /* =========================
           DISCOVER
        ========================= */

        .discover-tabs {
            display: flex;
            gap: 4px;
            padding: 4px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .discover-tabs::-webkit-scrollbar {
            display: none;
        }

        .discover-tab {
            flex-shrink: 0;
            min-height: 36px;
            padding: 0 14px;
            border: none;
            border-radius: 9px;
            background: transparent;
            color: #5b4a44;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
        }

        .discover-tab:hover {
            background: #fff7f4;
        }

        .discover-tab[aria-selected="true"] {
            background: var(--ink);
            color: #fff;
        }

        .discover-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .discover-empty {
            padding: 40px 20px;
            border: 1px dashed #f0d9d1;
            border-radius: 18px;
            text-align: center;
            font-size: 13.5px;
            color: #6f5a53;
        }

        .p-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 22px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .p-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -20px rgba(23, 32, 51, 0.35);
        }

        .p-media {
            position: relative;
            height: 250px;
            background: #fff4f0;
            color: #e2a08a;
            font-size: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .p-media img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .p-cat {
            position: absolute;
            left: 12px;
            bottom: 12px;
            max-width: calc(100% - 24px);
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.94);
            color: var(--ink);
            font-size: 11px;
            font-weight: 800;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .p-heart {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: #fff;
            color: var(--accent-dark);
            font-size: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(23, 32, 51, 0.1);
        }

        .p-body {
            flex: 1;
            padding: 16px 18px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .p-name {
            font-size: 16px;
            font-weight: 800;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .p-name:hover {
            color: var(--accent-dark);
        }

        .p-sub {
            margin-top: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #6f5a53;
        }

        .p-sub .bi-star-fill {
            color: var(--gold);
        }

        .p-sub .is-low {
            color: #8a4b00;
            font-weight: 800;
        }

        .p-bottom {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .p-price {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 800;
            color: var(--accent-dark);
            white-space: nowrap;
        }

        .p-bottom form {
            margin: 0;
        }

        /* =========================
           FOOTER
        ========================= */

        .dash-footer {
            border-top: 1px solid var(--line);
            background: #fff;
            padding: 26px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 13px;
            color: #6f5a53;
        }

        .dash-footer > * {
            max-width: 1320px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1180px) {
            .order-row {
                grid-template-columns: 64px minmax(0, 1fr) auto;
            }

            .order-progress {
                grid-column: 2 / -1;
                grid-row: 2;
            }

            .again-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .discover-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* Sidebar moves to the Me page (bottom tab bar). */
        @media (max-width: 900px) {
            .dash {
                grid-template-columns: minmax(0, 1fr);
                margin-top: 18px;
                width: calc(100% - 32px);
            }

            .dash-side {
                display: none;
            }

            .dash-main {
                gap: 28px;
            }

            .dash-greet {
                display: flex;
            }

            .dash-section-head h2 {
                font-size: 21px;
            }

            .dash-hero {
                grid-template-columns: minmax(0, 1fr);
                min-height: 0;
                border-radius: 20px;
            }

            .dash-hero-text {
                padding: 24px 22px;
                max-width: 64%;
            }

            .dash-hero h1 {
                font-size: 30px;
            }

            .dash-hero p {
                font-size: 12.5px;
            }

            .dash-hero-art {
                position: absolute;
                inset: 0;
            }

            .dash-hero-tile.t1 { display: none; }
            .dash-hero-tile.t2 { left: auto; right: -18px; top: 34px; width: 110px; height: 136px; font-size: 40px; }
            .dash-hero-tile.t3 { left: auto; right: 70px; top: 90px; width: 72px; height: 88px; font-size: 28px; border-radius: 14px; }

            .order-row {
                grid-template-columns: 52px minmax(0, 1fr);
                gap: 12px 12px;
                padding: 16px;
            }

            .order-thumb {
                width: 52px;
                height: 52px;
                font-size: 22px;
                border-radius: 14px;
            }

            .order-progress,
            .order-actions {
                grid-column: 1 / -1;
            }

            .order-progress {
                grid-row: auto;
            }

            .order-actions .btn {
                flex: 1;
            }

            /* Buy again scrolls sideways */
            .again-grid {
                display: flex;
                overflow-x: auto;
                margin-right: -16px;
                padding-right: 16px;
                scrollbar-width: none;
            }

            .again-grid::-webkit-scrollbar {
                display: none;
            }

            .again-card {
                flex: 0 0 150px;
            }

            .again-top {
                flex-direction: column;
                align-items: stretch;
            }

            .again-thumb {
                width: 100%;
                height: 86px;
                font-size: 28px;
            }

            .discover-grid {
                gap: 12px;
            }

            .p-card {
                border-radius: 18px;
            }

            .p-media {
                height: 170px;
                font-size: 46px;
            }

            .p-cat {
                display: none;
            }

            .p-heart {
                top: 6px;
                right: 6px;
            }

            .p-body {
                padding: 10px 12px 12px;
                gap: 6px;
            }

            .p-name {
                font-size: 13px;
            }

            .p-sub {
                font-size: 11px;
            }

            .p-price {
                font-size: 18px;
            }

            .p-bottom .btn {
                min-width: 42px;
                padding: 0 12px;
            }

            .p-bottom .btn-label {
                display: none;
            }
        }

        @media (max-width: 380px) {
            .dash-hero-text {
                max-width: 72%;
            }

            .p-media {
                height: 140px;
            }
        }
    </style>
</head>

<body>

    @include('partials.buyer-navbar', ['activeNav' => 'home'])

    @php
        $cod = $panel['cod'];
        $codClass = $cod['blocked'] ? 'is-blocked' : ($cod['strikes'] > 0 ? 'is-warn' : 'is-good');
        $codLabel = $cod['blocked'] ? 'COD paused' : ($cod['strikes'] > 0 ? 'COD ' . $cod['strikes'] . '/' . $cod['limit'] : 'COD OK');

        $announcement = \App\Models\PlatformAnnouncement::where('is_active', true)
            ->orderByDesc('created_at')
            ->first();

        $discoverTabs = [
            'latest' => ['label' => 'Latest', 'empty' => 'No products yet.'],
            'top' => ['label' => 'Top rated', 'empty' => 'No rated products yet — reviews show up here once buyers rate their orders.'],
            'budget' => ['label' => 'Under ₱1,000', 'empty' => 'No products under ₱1,000 right now.'],
        ];
    @endphp

    <main class="dash">

        {{-- SIDEBAR (desktop) --}}
        <aside class="dash-side">
            @include('partials.buyer-account-panel', ['panel' => $panel])
        </aside>

        <div class="dash-main">

            {{-- GREETING (mobile) --}}
            <section class="dash-greet">
                <span class="dash-greet-avatar">
                    @if($panel['photo'])
                        <img src="{{ $panel['photo'] }}" alt="">
                    @else
                        {{ strtoupper(substr($panel['name'], 0, 1)) }}
                    @endif
                </span>
                <div class="dash-greet-text">
                    <strong>Hi, {{ \Illuminate\Support\Str::of($panel['name'])->before(' ') }}!</strong>
                    <span>
                        @if($panel['active_orders'] > 0)
                            {{ $panel['active_orders'] }} active {{ \Illuminate\Support\Str::plural('order', $panel['active_orders']) }}
                        @else
                            Welcome back to BoomBuy
                        @endif
                    </span>
                </div>
                <a href="{{ route('buyer.account') }}" class="dash-cod-chip {{ $codClass }}" aria-label="Cash on Delivery standing: {{ $codLabel }}">
                    <i class="bi {{ $cod['blocked'] ? 'bi-slash-circle' : ($cod['strikes'] > 0 ? 'bi-exclamation-circle' : 'bi-check-lg') }}"></i>{{ $codLabel }}
                </a>
            </section>

            {{-- HERO: rotating highlights (announcement, new arrivals, free shipping, COD) --}}
            @include('partials.buyer-hero-carousel', ['announcement' => $announcement, 'latest' => $discover['latest'] ?? []])

            {{-- YOUR ORDERS --}}
            @if(count($activeOrders) > 0)
                <section class="dash-section">
                    <div class="dash-section-head">
                        <h2>Your orders</h2>
                        <a href="{{ route('buyer.orders') }}" class="dash-link">All orders →</a>
                    </div>

                    @foreach($activeOrders as $order)
                        <article class="order-row">
                            <span class="order-thumb">
                                @if($order['image'])
                                    <img src="{{ $order['image'] }}" alt="" onerror="this.remove()">
                                @endif
                                <i class="bi {{ $order['icon'] }}"></i>
                            </span>

                            <div class="order-info">
                                <span class="order-meta">Order #{{ $order['id'] }} · ₱{{ number_format($order['total'], 2) }}</span>
                                <span class="order-name">{{ $order['name'] }}</span>
                                <x-status-pill :status="$order['status']" />
                            </div>

                            <div class="order-progress">
                                <div class="order-steps" role="img" aria-label="Step {{ $order['step'] }} of 5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= $order['step'] ? 'is-on' : '' }}"></span>
                                    @endfor
                                </div>
                                <span class="order-note">{{ $order['note'] }}</span>
                            </div>

                            <div class="order-actions">
                                @if($order['seller_id'])
                                    <a href="{{ route('messages.thread', $order['seller_id']) }}" class="btn btn-ghost">
                                        <i class="bi bi-chat-dots"></i> Chat seller
                                    </a>
                                @endif
                                <a href="{{ route('buyer.orders') }}#order-{{ $order['id'] }}" class="btn btn-primary">Track order</a>
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif

            {{-- BUY AGAIN --}}
            @if(count($buyAgain) > 0)
                <section class="dash-section">
                    <div class="dash-section-head">
                        <div>
                            <h2>Buy again</h2>
                            <p>From your delivered orders.</p>
                        </div>
                    </div>

                    <div class="again-grid">
                        @foreach($buyAgain as $product)
                            <div class="again-card">
                                <div class="again-top">
                                    <span class="again-thumb">
                                        @if($product['image'])
                                            <img src="{{ $product['image'] }}" alt="" onerror="this.remove()">
                                        @endif
                                        <i class="bi {{ $product['icon'] }}"></i>
                                    </span>
                                    <span style="min-width:0;">
                                        <span class="again-name">{{ $product['name'] }}</span>
                                        <span class="again-price">₱{{ number_format($product['price'], 2) }}</span>
                                    </span>
                                </div>
                                <a href="{{ route('product.details', $product['slug']) }}" class="again-btn">
                                    <i class="bi bi-arrow-repeat"></i> Buy again
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- DISCOVER --}}
            <section class="dash-section">
                <div class="dash-section-head">
                    <h2>Discover</h2>
                    <div class="discover-tabs" role="tablist" aria-label="Discover products">
                        @foreach($discoverTabs as $key => $tab)
                            <button
                                type="button"
                                class="discover-tab"
                                role="tab"
                                id="discover-tab-{{ $key }}"
                                aria-controls="discover-{{ $key }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                tabindex="{{ $loop->first ? '0' : '-1' }}"
                                data-discover-tab="{{ $key }}"
                            >{{ $tab['label'] }}</button>
                        @endforeach
                    </div>
                </div>

                @foreach($discoverTabs as $key => $tab)
                    <div
                        id="discover-{{ $key }}"
                        role="tabpanel"
                        aria-labelledby="discover-tab-{{ $key }}"
                        @unless($loop->first) hidden @endunless
                    >
                        @if(count($discover[$key]) === 0)
                            <div class="discover-empty">{{ $tab['empty'] }}</div>
                        @else
                            <div class="discover-grid">
                                @foreach($discover[$key] as $product)
                                    <article class="p-card">
                                        <div class="p-media">
                                            @if($product['image'])
                                                <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" loading="lazy" onerror="this.remove()">
                                            @endif
                                                <i class="bi {{ $product['icon'] }}"></i>

                                            <button
                                                type="button"
                                                class="p-heart"
                                                data-wishlist="{{ $product['id'] }}"
                                                aria-pressed="{{ $product['in_wishlist'] ? 'true' : 'false' }}"
                                                aria-label="{{ $product['in_wishlist'] ? 'Remove from wishlist' : 'Add to wishlist' }}"
                                            >
                                                <i class="bi {{ $product['in_wishlist'] ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                            </button>

                                            <span class="p-cat">{{ $product['category'] }}</span>
                                        </div>

                                        <div class="p-body">
                                            <div>
                                                <a href="{{ route('product.details', $product['slug']) }}" class="p-name">{{ $product['name'] }}</a>
                                                <div class="p-sub">
                                                    @if($product['rating'])
                                                        <i class="bi bi-star-fill"></i> {{ $product['rating'] }} ({{ $product['reviews'] }})
                                                    @else
                                                        New
                                                    @endif
                                                    ·
                                                    @if($product['stock'] <= 0)
                                                        <span class="is-low">Sold out</span>
                                                    @elseif($product['stock'] <= 10)
                                                        <span class="is-low">Only {{ $product['stock'] }} left</span>
                                                    @else
                                                        {{ $product['stock'] }} in stock
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="p-bottom">
                                                <span class="p-price">₱{{ number_format($product['price']) }}</span>

                                                @if($product['stock'] <= 0)
                                                    <button type="button" class="btn" disabled>Sold out</button>
                                                @elseif($product['has_variations'])
                                                    <a href="{{ route('product.details', $product['slug']) }}" class="btn btn-dark" aria-label="Choose options for {{ $product['name'] }}">
                                                        <i class="bi bi-sliders"></i><span class="btn-label">Options</span>
                                                    </a>
                                                @else
                                                    <form action="{{ route('cart.add', $product['id']) }}" method="POST" data-add-cart>
                                                        @csrf
                                                        <button type="submit" class="btn btn-dark" aria-label="Add {{ $product['name'] }} to cart">
                                                            <i class="bi bi-plus-lg"></i><span class="btn-label">Add</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                <div style="text-align:center;">
                    <a href="{{ route('products') }}" class="dash-link">Browse all products →</a>
                </div>
            </section>

        </div>

    </main>


    <script>
    (function () {
        var token = document.querySelector('meta[name="csrf-token"]').content;

        /* Discover tabs */
        var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-discover-tab]'));

        function select(tab) {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                document.getElementById('discover-' + t.dataset.discoverTab).hidden = !on;
            });
        }

        tabs.forEach(function (tab, i) {
            tab.addEventListener('click', function () { select(tab); });
            tab.addEventListener('keydown', function (e) {
                var next = e.key === 'ArrowRight' ? i + 1 : e.key === 'ArrowLeft' ? i - 1 : null;
                if (next === null) return;
                e.preventDefault();
                var target = tabs[(next + tabs.length) % tabs.length];
                select(target);
                target.focus();
            });
        });

        /* Wishlist hearts */
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-wishlist]');
            if (!btn) return;

            var body = new FormData();
            body.append('_token', token);

            fetch('/wishlist/toggle/' + btn.dataset.wishlist, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
                .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
                .then(function (data) {
                    // The same product can appear under several tabs.
                    document.querySelectorAll('[data-wishlist="' + btn.dataset.wishlist + '"]').forEach(function (b) {
                        b.setAttribute('aria-pressed', data.in_wishlist ? 'true' : 'false');
                        b.setAttribute('aria-label', data.in_wishlist ? 'Remove from wishlist' : 'Add to wishlist');
                        b.querySelector('i').className = 'bi ' + (data.in_wishlist ? 'bi-heart-fill' : 'bi-heart');
                    });
                })
                .catch(function () {
                    if (window.bbAlert) bbAlert('Could not update your wishlist. Please try again.');
                });
        });

        /* Add to cart without leaving the page */
        document.querySelectorAll('[data-add-cart]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var button = form.querySelector('button');
                var original = button.innerHTML;
                button.disabled = true;

                fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form)
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        // Only say "Added" when the server actually added it.
                        if (!data.ok) {
                            button.innerHTML = original;
                            button.disabled = false;
                            if (window.bbAlert) bbAlert(data.message || 'Could not add this item to your cart.');
                            return;
                        }

                        button.innerHTML = '<i class="bi bi-check-lg"></i><span class="btn-label">Added</span>';

                        var badge = document.getElementById('cartCount');
                        if (badge && data.cart_count) {
                            badge.textContent = data.cart_count;
                            badge.style.display = '';
                        }

                        setTimeout(function () {
                            button.innerHTML = original;
                            button.disabled = false;
                        }, 1400);
                    })
                    .catch(function () {
                        button.innerHTML = original;
                        button.disabled = false;
                        if (window.bbAlert) bbAlert('Could not add this item to your cart. Please try again.');
                    });
            });
        });
    })();
    </script>

    @include('partials.buyer-footer')

    @include('partials.pwa-register')

</body>
</html>
