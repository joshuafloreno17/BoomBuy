@php
    // The marketplace login (/login) and the BoomBuy Logistics login
    // (/logistics/login) share this page; $portal says which one it is.
    $isLogistics = ($portal ?? null) === 'logistics';

    // /register: the same page, opened on the account types.
    $join = ($join ?? false) && !$isLogistics;

    // Line icons used by the orb and its satellites.
    $icons = [
        'bag' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'box' => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/>',
        'hub' => '<path d="M3 21V8l9-5 9 5v13"/><path d="M7 21v-8h10v8"/><path d="M7 17h10"/>',
        'truck' => '<path d="M1 4h13v12H1z"/><path d="M14 8h4l4 4v4h-8"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="18.5" cy="18.5" r="2"/>',
        'home' => '<path d="M3 10.5 12 3l9 7.5V21H3z"/><path d="m9 14 2 2 4-4"/>',
        'pin' => '<path d="M12 22s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12z"/><circle cx="12" cy="10" r="2.5"/>',
    ];

    // The order journey the orb plays, and the chips that light up with it.
    $journey = $isLogistics
        ? [['Picked up', 'box'], ['At sorting center', 'hub'], ['Out for delivery', 'truck'], ['Delivered', 'home']]
        : [['Order placed', 'bag'], ['Packed', 'box'], ['On the way', 'truck'], ['Delivered', 'home']];

    $chips = $isLogistics
        ? [['Pickup assigned', '#7c5ce8', 0], ['Parcel sorted', '#9b82f0', 1], ['COD remitted', '#22c55e', 3]]
        : [['Payment received', '#22c55e', 0], ['Rider on the way', '#f5b84a', 2], ['Order delivered', '#9b82f0', 3]];

    $satellites = $isLogistics ? ['truck', 'pin', 'box'] : ['truck', null, 'bag'];

    // "Create one" flips the card to these. Riders sign up inside the app only.
    $inApp = \App\Support\MobileAccess::isApp(request());

    $icons['star'] = '<path d="M12 3l2.5 5.5L20 9l-4 4 1 6-5-3-5 3 1-6-4-4 5.5-.5z"/>';
    $icons['store'] = '<path d="M3 9 4.5 4h15L21 9"/><path d="M4 9v11h16V9"/><path d="M3 9h18"/><path d="M9 20v-6h6v6"/>';
    $icons['bike'] = '<circle cx="5.5" cy="17.5" r="3"/><circle cx="18.5" cy="17.5" r="3"/><path d="M5.5 17.5 9 10h5l3 7.5"/><path d="M14 10l-1.5-4H10"/>';

    $roleIcons = ['none' => [$inApp ? 'Choose your role' : 'Buyer or seller?', 'star'], 'buyer' => ['Buyer', 'bag'], 'seller' => ['Seller', 'store'], 'rider' => ['Rider', 'bike']];

    $roles = [
        ['buyer', 'I’m a Buyer', 'Shop from sellers across the Philippines.', route('buyer.register'), 'bag',
            ['Pay with GCash, Maya, card or COD', 'Track your order until it reaches you']],
        ['seller', 'I’m a Seller', 'Open your shop and track every order.', route('seller.register'), 'store',
            ['List products with photos for every option', 'A rider picks up orders from your shop']],
    ];

    if ($inApp) {
        $roles[] = ['rider', 'I’m a Rider', 'Deliver orders and earn on your own schedule.', route('rider.apply'), 'bike', []];
    }
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['title' => $isLogistics ? 'Login — BoomBuy Logistics' : ($join ? 'Create account — BoomBuy' : 'Login — BoomBuy'), 'fonts' => true])

    <link rel="stylesheet" href="{{ vasset('css/views/login.css') }}">

    {{-- Came here with the "other portal" link: play the switch-in (once). --}}
    <script>
        try {
            var bbSwitch = (sessionStorage.getItem('bbPortalSwitch') || '').split(':');
            sessionStorage.removeItem('bbPortalSwitch');
            if (bbSwitch[0] === @json($isLogistics ? 'logistics' : 'market')
                && !(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) {
                document.documentElement.classList.add('bb-switch-in', bbSwitch[1]);
            }
        } catch (e) {}
    </script>
</head>

<body class="bb-auth {{ $isLogistics ? 'is-logistics' : '' }} {{ $join ? 'is-join' : '' }}">

    @if($isLogistics)
        @include('partials.logistics-net')
    @endif

    <header class="auth-top">

        <a href="{{ $isLogistics ? route('logistics.home') : route('home') }}" class="logo">
            <img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" class="bb-logo-wordmark" width="131" height="46">
            @if($isLogistics)
                <span class="portal-tag">Logistics</span>
            @endif
        </a>

    </header>


    <main class="auth-stage">

        {{-- LEFT: what BoomBuy is --}}

        <section class="auth-copy" aria-label="{{ $isLogistics ? 'About BoomBuy Logistics' : 'About BoomBuy' }}">

            <div class="auth-badge">
                <span class="auth-live" aria-hidden="true"></span>
                <strong>Open 24/7</strong>
                @if($isLogistics)
                    <span aria-hidden="true">·</span> Pickup <span aria-hidden="true">·</span> Sort <span aria-hidden="true">·</span> Deliver
                @else
                    <span aria-hidden="true">·</span> Shop <span aria-hidden="true">·</span> Sell <span aria-hidden="true">·</span> Deliver
                @endif
            </div>

            {{-- Login copy, and (marketplace) the "Create one" copy it swaps to. --}}
            <div class="auth-stack">
                <div class="auth-copy-block is-login">
                    @if($isLogistics)
                        <h1>Every parcel<br><span class="accent">on its way.</span></h1>
                        <p>Pickups, sorting, deliveries and COD remittance — all in one place.</p>
                    @else
                        <h1>The market<br><span class="accent">never sleeps.</span></h1>
                        <p>Shop, sell and track every order — any time, from the shop to your door.</p>
                    @endif
                </div>

                @unless($isLogistics)
                    <div class="auth-copy-block is-join">
                        <h2 class="auth-join-title">Find it. Love it.<br><span class="accent">Boom — it’s yours.</span></h2>
                        <p>One account to shop from sellers nationwide, or to open your own shop.</p>
                    </div>
                @endunless
            </div>

            <div class="auth-stack">
                <div class="auth-chips">
                    @foreach($chips as [$label, $dot, $step])
                        <span class="auth-chip" data-step="{{ $step }}" style="--dot: {{ $dot }}">
                            <i aria-hidden="true"></i>{{ $label }}
                        </span>
                    @endforeach
                </div>

                @unless($isLogistics)
                    <ol class="auth-steps">
                        <li class="is-current"><span>1</span>Choose your role</li>
                        <li><span>2</span>Fill in your details</li>
                        <li><span>3</span>Verify your email</li>
                    </ol>
                @endunless
            </div>

        </section>


        {{-- MIDDLE: the orb, playing an order's journey --}}

        <div class="auth-orb" id="authOrb" aria-hidden="true">

            <div class="auth-orb-stage">

                <span class="auth-ring is-outer"></span>
                <span class="auth-ring is-inner"></span>

                <div class="auth-orbit">
                    <span class="auth-sat is-top"><svg viewBox="0 0 24 24">{!! $icons[$satellites[0]] !!}</svg></span>
                    <span class="auth-sat is-bottom">
                        @if($satellites[1])
                            <svg viewBox="0 0 24 24">{!! $icons[$satellites[1]] !!}</svg>
                        @else
                            ₱
                        @endif
                    </span>
                </div>

                <div class="auth-orbit is-inner">
                    <span class="auth-sat"><svg viewBox="0 0 24 24">{!! $icons[$satellites[2]] !!}</svg></span>
                </div>

                {{-- Progress ring, drawn while choosing an account type. --}}
                <svg class="auth-progress" viewBox="0 0 360 360">
                    <circle cx="180" cy="180" r="150"/>
                    <circle cx="180" cy="180" r="150" class="is-fill"/>
                </svg>

                <div class="auth-ball">
                    <div class="auth-ball-content">
                        @foreach($journey as $i => [$label, $icon])
                            <span class="auth-icon {{ $i === 0 ? 'is-on' : '' }}" data-label="{{ $label }}">
                                <svg viewBox="0 0 24 24">{!! $icons[$icon] !!}</svg>
                            </span>
                        @endforeach
                        @unless($isLogistics)
                            @foreach($roleIcons as $key => [$label, $icon])
                                <span class="auth-icon is-role" data-role="{{ $key }}" data-label="{{ $label }}">
                                    <svg viewBox="0 0 24 24">{!! $icons[$icon] !!}</svg>
                                </span>
                            @endforeach
                        @endunless
                        <span class="auth-ball-label">
                            <small id="authStepNum">Step 1 of 4</small>
                            <strong id="authStepLabel">{{ $journey[0][0] }}</strong>
                        </span>
                    </div>
                </div>

            </div>

            <div class="auth-dots">
                @foreach($journey as $i => $j)
                    <span class="{{ $i === 0 ? 'is-on' : '' }}"></span>
                @endforeach
            </div>

        </div>


        {{-- RIGHT: the login card --}}

        <div class="auth-card-wrap">

          <div class="auth-flip">

            <div
                class="auth-card"
                id="authCard"
                data-hint-logistics="{{ route('logistics.login') }}"
                data-hint-app="{{ route('app.download') }}"
                data-hint-market="{{ route('login') }}"
                @if($join) inert @endif
            >

                <div class="auth-card-head">
                    <span class="auth-kicker">{{ $isLogistics ? 'Logistics portal' : 'Welcome back' }}</span>
                    <h2>{{ $isLogistics ? 'Login to BoomBuy Logistics' : 'Login to BoomBuy' }}</h2>
                    @if($isLogistics)
                        <p>For sorting center and logistics staff.</p>
                    @endif
                </div>


                {{-- ERROR --}}

                @if(session('error'))

                    <div class="error-box">
                        {{ session('error') }}

                        @if(session('portal_hint') === 'logistics')
                            <a href="{{ route('logistics.login') }}" class="portal-switch">Go to BoomBuy Logistics login →</a>
                        @elseif(session('portal_hint') === 'app')
                            <a href="{{ route('app.download') }}" class="portal-switch">Get the BoomBuy app →</a>
                        @elseif(session('portal_hint') === 'market')
                            <a href="{{ route('login') }}" class="portal-switch">Go to BoomBuy marketplace login →</a>
                        @endif
                    </div>

                @endif


                {{-- SUCCESS --}}

                @if(session('success'))

                    <div class="success-box">
                        {{ session('success') }}
                    </div>

                @endif


                {{-- VALIDATION ERRORS --}}

                @if($errors->any())

                    <div class="error-box">
                        {{ $errors->first() }}
                    </div>

                @endif


                {{-- Filled in by the script when a sign-in is refused. --}}
                <div class="error-box" id="authError" role="alert" hidden></div>


                <form
                    action="{{ route($isLogistics ? 'logistics.login.submit' : 'login.submit') }}"
                    method="POST"
                    id="loginForm"
                >

                    @csrf


                    {{-- EMAIL --}}

                    <div class="form-group">

                        <label for="login-email">
                            Email address
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="login-email"
                            value="{{ old('email') }}"
                            placeholder="{{ $isLogistics ? 'you@company.com' : 'you@example.com' }}"
                            autocomplete="email"
                            autofocus
                            required
                        >

                    </div>


                    {{-- PASSWORD --}}

                    <div class="form-group">

                        <div class="password-row">

                            <label for="login-password">
                                Password
                            </label>

                            <a
                                href="{{ $isLogistics ? route('password.request', ['portal' => 'logistics']) : route('password.request') }}"
                                class="forgot"
                            >
                                Forgot password?
                            </a>

                        </div>


                        <div class="password-input-wrapper">

                            <input
                                type="password"
                                name="password"
                                id="login-password"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="show-password-btn"
                                onclick="togglePassword('login-password', this)"
                                aria-label="Show password"
                            ><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>

                        </div>

                        <label class="remember-row">
                            <input type="checkbox" name="remember">
                            <span>Remember me for 30 days</span>
                        </label>

                    </div>


                    {{-- LOGIN BUTTON --}}

                    <button
                        type="submit"
                        class="login-btn"
                        id="loginBtn"
                    >
                        <span class="login-spinner" aria-hidden="true"></span>
                        <span class="login-btn-label">Login</span>
                    </button>


                    {{-- TERMS --}}

                    <div class="terms-login">

                        By continuing, you agree to BoomBuy’s

                        <a
                            href="#"
                            onclick="openTerms(event)"
                        >
                            Terms & Conditions
                        </a>

                        and

                        <a
                            href="#"
                            onclick="openPrivacy(event)"
                        >
                            Privacy Policy
                        </a>.

                    </div>


                </form>


                <div class="auth-card-foot">

                    <div class="register-text">

                        {{ $isLogistics ? 'New to BoomBuy Logistics?' : 'Don’t have an account?' }}

                        <a
                            href="{{ route($isLogistics ? 'logistics.register' : 'register') }}"
                            @unless($isLogistics) data-flip="join" @endunless
                        >
                            {{ $isLogistics ? 'Join BoomBuy Logistics' : 'Create one' }}
                        </a>

                    </div>

                    <div class="portal-other">
                        @if($isLogistics)
                            Buyer or seller? <a href="{{ route('login') }}" data-portal-switch="market">Log in to the marketplace</a>
                        @elseif(!$inApp)
                            Logistics staff? <a href="{{ route('logistics.login') }}" data-portal-switch="logistics">BoomBuy Logistics login</a>
                        @endif
                    </div>

                </div>

            </div>

            @unless($isLogistics)

                {{-- BACK OF THE CARD: "Create one" — pick an account type --}}

                <div class="auth-card is-back" id="authBack" @unless($join) inert @endunless>

                    <div class="auth-card-head">
                        <span class="auth-kicker">Create account</span>
                        <h2>How will you use BoomBuy?</h2>
                    </div>

                    <div class="auth-roles">
                        @foreach($roles as [$key, $title, $desc, $href, $icon, $perks])
                            <a href="{{ $href }}" class="auth-role" data-role="{{ $key }}">
                                <span class="auth-role-main">
                                    <span class="auth-role-icon"><svg viewBox="0 0 24 24">{!! $icons[$icon] !!}</svg></span>
                                    <span class="auth-role-text">
                                        <strong>{{ $title }}</strong>
                                        <small>{{ $desc }}</small>
                                    </span>
                                    <span class="auth-role-go" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m13 5 7 7-7 7"/></svg>
                                    </span>
                                </span>
                                @if($perks)
                                    <span class="auth-role-perks">
                                        @foreach($perks as $perk)
                                            <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>{{ $perk }}</span>
                                        @endforeach
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    <div class="auth-card-foot">

                        <div class="register-text">
                            Already have an account?
                            <button type="button" class="auth-link-btn" data-flip="login">Log in</button>
                        </div>

                        {{-- Sorting centers sign up on BoomBuy Logistics (a computer, not the app). --}}
                        @unless($inApp)
                            <div class="portal-other">
                                Run a sorting center? <a href="{{ route('logistics.home') }}">Go to BoomBuy Logistics</a>
                            </div>
                        @endunless

                    </div>

                </div>

            @endunless

          </div>

        </div>

    </main>


    <div class="footer-text">

        © 2026 BoomBuy ·
        {{ $isLogistics ? 'Logistics — every parcel on its way.' : 'Shop smarter. Live better.' }}

    </div>


    {{-- ===================================================== --}}
    {{-- TERMS & CONDITIONS MODAL --}}
    {{-- ===================================================== --}}

    <div
        class="terms-modal"
        id="termsModal"
        onclick="closeTermsOutside(event)"
    >

        <div class="terms-box">

            <div class="terms-header">

                <div>

                    <h2>
                        BoomBuy Terms & Conditions
                    </h2>

                    <p>
                        Please review the terms for using BoomBuy.
                    </p>

                </div>


                <button
                    type="button"
                    class="close-terms"
                    onclick="closeTerms()"
                    aria-label="Close"
                >
                    ×
                </button>

            </div>


            <div class="terms-content">
                @include('partials.policies.body', ['key' => 'terms_policy', 'default' => 'partials.policies.terms-default'])
            </div>


            <div class="terms-footer">

                <button
                    type="button"
                    class="terms-close-btn"
                    onclick="closeTerms()"
                >
                    Close
                </button>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- PRIVACY POLICY MODAL --}}
    {{-- ===================================================== --}}

    <div
        class="terms-modal"
        id="privacyModal"
        onclick="closePrivacyOutside(event)"
    >

        <div class="terms-box">

            <div class="terms-header">

                <div>

                    <h2>
                        BoomBuy Privacy Policy
                    </h2>

                    <p>
                        How BoomBuy handles user information.
                    </p>

                </div>


                <button
                    type="button"
                    class="close-terms"
                    onclick="closePrivacy()"
                    aria-label="Close"
                >
                    ×
                </button>

            </div>


            <div class="terms-content">
                @include('partials.policies.body', ['key' => 'privacy_policy', 'default' => 'partials.policies.privacy-default'])
            </div>


            <div class="terms-footer">

                <button
                    type="button"
                    class="terms-close-btn"
                    onclick="closePrivacy()"
                >
                    Close
                </button>

            </div>

        </div>

    </div>


    <script>

        /* =========================
           PASSWORD SHOW / HIDE
        ========================= */

        const EYE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        const EYE_OFF_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

        function togglePassword(inputId, button) {

            const input = document.getElementById(inputId);

            if (input.type === 'password') {

                input.type = 'text';
                button.innerHTML = EYE_ICON;
                button.setAttribute('aria-label', 'Hide password');

            } else {

                input.type = 'password';
                button.innerHTML = EYE_OFF_ICON;
                button.setAttribute('aria-label', 'Show password');

            }

        }


        /* =========================
           ORB — an order's journey, step by step
        ========================= */

        (function () {

            const orb = document.getElementById('authOrb');
            const icons = orb.querySelectorAll('.auth-icon:not(.is-role)');
            const roleIcons = orb.querySelectorAll('.auth-icon.is-role');
            const dots = orb.querySelectorAll('.auth-dots span');
            const chips = document.querySelectorAll('.auth-chip');
            const stepNum = document.getElementById('authStepNum');
            const stepLabel = document.getElementById('authStepLabel');
            let step = 0;
            let joining = false;

            function show(next) {

                icons.forEach(function (icon, i) {
                    icon.classList.toggle('is-on', !joining && i === next);
                    icon.classList.toggle('is-prev', i === step && i !== next);
                });

                dots.forEach(function (dot, i) {
                    dot.classList.toggle('is-on', i === next);
                });

                chips.forEach(function (chip) {
                    chip.classList.toggle('is-on', Number(chip.dataset.step) === next);
                });

                stepNum.textContent = 'Step ' + (next + 1) + ' of ' + icons.length;
                stepLabel.textContent = icons[next].dataset.label;
                step = next;

            }

            // While choosing an account type the orb shows the hovered role.
            function role(key) {

                roleIcons.forEach(function (icon) {
                    icon.classList.toggle('is-on', icon.dataset.role === key);
                });

                const on = orb.querySelector('.auth-icon.is-role.is-on');
                stepNum.textContent = 'Create account';
                stepLabel.textContent = on ? on.dataset.label : '';

            }

            function start() {
                clearInterval(window.bbOrbTimer);
                window.bbOrbTimer = setInterval(function () {
                    show((step + 1) % icons.length);
                }, 2200);
            }

            window.bbOrb = {
                join: function (on) {
                    joining = on;
                    clearInterval(window.bbOrbTimer);
                    icons.forEach(function (icon) { icon.classList.remove('is-on', 'is-prev'); });

                    if (on) {
                        role('none');
                    } else {
                        role(null);
                        show(step);
                        start();
                    }
                },
                role: function (key) {
                    if (joining) {
                        role(key || 'none');
                    }
                }
            };

            show(0);
            start();

        })();


        /* =========================
           CREATE ONE — flip the card to the account types
        ========================= */

        (function () {

            const front = document.getElementById('authCard');
            const back = document.getElementById('authBack');

            if (!back) {
                return;
            }

            function flip(toJoin) {

                document.body.classList.toggle('is-join', toJoin);
                front.inert = toJoin;
                back.inert = !toJoin;
                window.bbOrb.join(toJoin);

                const focusTarget = toJoin ? back.querySelector('.auth-role') : front.querySelector('[data-flip="join"]');
                setTimeout(function () { focusTarget && focusTarget.focus({ preventScroll: true }); }, 450);

            }

            document.querySelectorAll('[data-flip]').forEach(function (trigger) {
                trigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    flip(trigger.dataset.flip === 'join');
                });
            });

            back.querySelectorAll('.auth-role').forEach(function (tile) {
                ['mouseenter', 'focus'].forEach(function (type) {
                    tile.addEventListener(type, function () { window.bbOrb.role(tile.dataset.role); });
                });
                ['mouseleave', 'blur'].forEach(function (type) {
                    tile.addEventListener(type, function () { window.bbOrb.role(null); });
                });
            });

            // /register and /login#join open straight on the account types.
            if (document.body.classList.contains('is-join') || window.location.hash === '#join') {
                flip(true);
            }

        })();


        /* =========================
           SWITCHING PORTALS — the page slides out toward where you're going
           (Logistics to the left, the marketplace to the right), and the
           other login slides in from the opposite side.
        ========================= */

        (function () {

            var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

            document.querySelectorAll('[data-portal-switch]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;

                    var toLogistics = link.dataset.portalSwitch === 'logistics';

                    try {
                        sessionStorage.setItem('bbPortalSwitch', link.dataset.portalSwitch + ':' + (toLogistics ? 'from-right' : 'from-left'));
                    } catch (e) {}

                    if (reduce) return;

                    event.preventDefault();
                    document.body.classList.add('is-switching-out', toLogistics ? 'to-left' : 'to-right');
                    setTimeout(function () { window.location.href = link.href; }, 260);
                });
            });

            // Back button: the browser may restore this page mid-slide.
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    document.body.classList.remove('is-switching-out', 'to-left', 'to-right');
                }
            });

        })();


        /* =========================
           SIGN IN — check first, then play the orb
        ========================= */

        (function () {

            const form = document.getElementById('loginForm');
            const card = document.getElementById('authCard');
            const errorBox = document.getElementById('authError');
            const button = document.getElementById('loginBtn');

            if (!window.fetch || !window.FormData) {
                return;
            }

            const HINTS = {
                logistics: ['hintLogistics', 'Go to BoomBuy Logistics login →'],
                app: ['hintApp', 'Get the BoomBuy app →'],
                market: ['hintMarket', 'Go to BoomBuy marketplace login →']
            };

            function showError(message, hint) {

                errorBox.textContent = message;

                if (hint && HINTS[hint]) {
                    const link = document.createElement('a');
                    link.className = 'portal-switch';
                    link.href = card.dataset[HINTS[hint][0]];
                    link.textContent = HINTS[hint][1];
                    errorBox.appendChild(link);
                }

                card.querySelectorAll('.error-box:not(#authError), .success-box').forEach(function (box) {
                    box.remove();
                });

                errorBox.hidden = false;
                card.classList.remove('is-shake');
                void card.offsetWidth;
                card.classList.add('is-shake');

            }

            function busy(on) {
                button.disabled = on;
                button.classList.toggle('is-busy', on);
                button.querySelector('.login-btn-label').textContent = on ? 'Signing you in…' : 'Login';
            }

            form.addEventListener('submit', function (event) {

                event.preventDefault();
                busy(true);

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {

                        if (response.status === 429) {
                            throw { message: 'Too many login attempts. Please wait a minute and try again.' };
                        }

                        if (response.status === 419) {
                            throw { message: 'This page has expired. Please refresh the page and try again.' };
                        }

                        return response.json().then(function (data) {
                            if (!data.ok) {
                                throw { message: data.error || 'Invalid email or password.', hint: data.hint };
                            }
                            return data;
                        });

                    })
                    .then(function (data) {

                        // Signed in: the orb grows over the page, then we go.
                        clearInterval(window.bbOrbTimer);
                        document.body.classList.add('is-leaving');

                        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                        setTimeout(function () {
                            window.location.assign(data.redirect);
                        }, reduce ? 0 : 550);

                    })
                    .catch(function (error) {

                        busy(false);

                        if (error && error.message) {
                            showError(error.message, error.hint);
                        } else {
                            // Network trouble: fall back to a normal form post.
                            form.submit();
                        }

                    });

            });

        })();


        /* =========================
           TERMS MODAL
        ========================= */

        function openTerms(event) {

            event.preventDefault();

            document.getElementById('privacyModal')
                .classList.remove('show');

            document.getElementById('termsModal')
                .classList.add('show');

            document.body.style.overflow = 'hidden';

        }


        function closeTerms() {

            document.getElementById('termsModal')
                .classList.remove('show');

            document.body.style.overflow = '';

        }


        function closeTermsOutside(event) {

            if (
                event.target ===
                document.getElementById('termsModal')
            ) {

                closeTerms();

            }

        }


        /* =========================
           PRIVACY MODAL
        ========================= */

        function openPrivacy(event) {

            event.preventDefault();

            document.getElementById('termsModal')
                .classList.remove('show');

            document.getElementById('privacyModal')
                .classList.add('show');

            document.body.style.overflow = 'hidden';

        }


        function closePrivacy() {

            document.getElementById('privacyModal')
                .classList.remove('show');

            document.body.style.overflow = '';

        }


        function closePrivacyOutside(event) {

            if (
                event.target ===
                document.getElementById('privacyModal')
            ) {

                closePrivacy();

            }

        }


        /* =========================
           ESC KEY
        ========================= */

        document.addEventListener('keydown', function(event) {

            if (event.key === 'Escape') {

                closeTerms();
                closePrivacy();

            }

        });

    </script>

    @include('partials.pwa-register')

</body>

</html>
