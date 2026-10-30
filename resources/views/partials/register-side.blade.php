{{--
    Sign-up pages (buyer, seller, logistics) in the login's look: logo bar,
    then the headline + checklist, the big orb with its progress ring, and
    the page's own form card after it. Goes inside <div class="reg">, before
    <main class="reg-main">. Needs css/views/register-shell.css and
    <body class="reg-night">. The page's wizard calls
    window.bbRegSide.show(n) when it changes step.

    @include('partials.register-side', [
        'kicker' => 'Buyer account',
        'headline' => ['Let’s set up', 'your account.'],
        'blurb' => '…',
        'steps' => [['Personal info', 'user'], ['Delivery address', 'pin'], …],
        'logistics' => false,   // BoomBuy Logistics tag, no "Choose your role"
        'verifying' => false,   // the email-code page: every step done, the code is next
        'roleStep' => true,     // the done "Choose your role" first (default: not on Logistics)
        'finalStep' => 'Verify your email',   // '' for none (password reset)
    ])
--}}
@php
    $logistics = $logistics ?? false;
    $verifying = $verifying ?? false;
    $roleStep = $roleStep ?? !$logistics;
    $finalStep = $finalStep ?? 'Verify your email';

    $regIcons = [
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'pin' => '<path d="M12 22s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12z"/><circle cx="12" cy="10" r="2.5"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'review' => '<path d="M9 2h6v4H9z"/><path d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2"/><path d="m9 14 2 2 4-4"/>',
        'brief' => '<path d="M3 7h18v13H3z"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 13h18"/>',
        'docs' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>',
        'map' => '<path d="M9 3 3 6v15l6-3 6 3 6-3V3l-6 3z"/><path d="M9 3v15"/><path d="M15 6v15"/>',
        'store' => '<path d="M3 9 4.5 4h15L21 9"/><path d="M4 9v11h16V9"/><path d="M3 9h18"/><path d="M9 20v-6h6v6"/>',
        'mail' => '<path d="M2 4h20v16H2z"/><path d="m22 6-10 7L2 6"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.8-9.8"/><path d="m16 7 3 3"/><path d="m19 4 2 2"/>',
        'bike' => '<circle cx="5.5" cy="17.5" r="3"/><circle cx="18.5" cy="17.5" r="3"/><path d="M5.5 17.5 9 10h5l3 7.5"/><path d="M14 10l-1.5-4H10"/>',
    ];

    // Everything on the checklist: choosing a role (already done on the
    // login page), the wizard's steps, then the email code after sign-up.
    $before = $roleStep ? 1 : 0;
    $total = $before + count($steps) + ($finalStep !== '' ? 1 : 0);

    // The orb's icons and label: one per step, or just the envelope on
    // the email-code page.
    $orbIcons = $verifying ? [['Check your email', 'mail']] : $steps;
    $orbNum = $verifying ? 'Almost done' : 'Step 1 of ' . count($steps);
    $ringDone = $verifying ? $total - 0.5 : $before + 0.5;
@endphp

@if($logistics)
    @include('partials.logistics-net')
@endif

<header class="reg-topbar">
    <a href="{{ $logistics ? route('logistics.home') : route('home') }}" class="reg-logo">
        <img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" class="bb-logo-wordmark" width="131" height="46">
        @if($logistics)
            <span class="reg-portal-tag">Logistics</span>
        @endif
    </a>
</header>

<section class="reg-side" aria-label="Your progress">

    <div class="reg-pitch">
        <h1>{{ $headline[0] }}<br><span class="accent">{{ $headline[1] }}</span></h1>
        <p>{{ $blurb }}</p>
    </div>

    <ol class="reg-checklist" id="regChecklist">
        @if($roleStep)
            <li class="is-done"><span class="reg-check-dot"><span>1</span><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></span>Choose your role</li>
        @endif
        @foreach($steps as $i => [$label, $icon])
            <li data-step="{{ $i + 1 }}" data-name="{{ $label }}" @class(['is-done' => $verifying])><span class="reg-check-dot"><span>{{ $before + $i + 1 }}</span><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></span>{{ $label }}</li>
        @endforeach
        @if($finalStep !== '')
            <li @class(['is-current' => $verifying])><span class="reg-check-dot"><span>{{ $total }}</span><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></span>{{ $finalStep }}</li>
        @endif
    </ol>

</section>

{{-- On phones the orb shrinks into a header row beside this caption. --}}
<div class="reg-orb-wrap">

<div class="reg-orb" aria-hidden="true">
    <div class="reg-orb-stage">

        <svg class="reg-orb-ring" viewBox="0 0 360 360">
            <circle cx="180" cy="180" r="150"/>
            <circle cx="180" cy="180" r="150" class="is-fill" id="regRing" style="stroke-dashoffset: {{ round(943 * (1 - $ringDone / $total)) }}"/>
        </svg>

        <div class="reg-orb-ball">
            @foreach($orbIcons as $i => [$label, $icon])
                <span class="reg-orb-icon {{ $i === 0 ? 'is-on' : '' }}">
                    <svg viewBox="0 0 24 24">{!! $regIcons[$icon] !!}</svg>
                </span>
            @endforeach
            <span class="reg-orb-label">
                <small data-reg-num>{{ $orbNum }}</small>
                <strong data-reg-name>{{ $orbIcons[0][0] }}</strong>
            </span>
        </div>

    </div>
</div>

<div class="reg-orb-caption" aria-hidden="true">
    <small><span>{{ $kicker }} · </span><span data-reg-num>{{ $orbNum }}</span></small>
    <strong data-reg-name>{{ $orbIcons[0][0] }}</strong>
</div>

</div>

<script>
    // Going back to the login (Back to login, Change role, Log in): this page
    // slides out to the right and the login slides in from the left — the
    // login reads the same `bbPortalSwitch` note as its portal switch.
    document.addEventListener('DOMContentLoaded', function () {
        var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
        var portal = @json($logistics ? 'logistics' : 'market');

        document.querySelectorAll('.reg a[href]').forEach(function (link) {
            var path = new URL(link.href, location.href).pathname;
            if (path !== '/login' && path !== '/logistics/login') return;

            link.addEventListener('click', function (event) {
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;

                try { sessionStorage.setItem('bbPortalSwitch', portal + ':from-left'); } catch (e) {}
                if (reduce) return;

                event.preventDefault();
                document.body.classList.add('reg-leaving');
                setTimeout(function () { window.location.href = link.href; }, 260);
            });
        });

        // Back button: the browser may restore this page mid-slide.
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) document.body.classList.remove('reg-leaving');
        });
    });

    // The sticky Back / Continue bar gets its shadow only while it sits over
    // the form, i.e. while its own place (a marker right after it) is below
    // the bottom of the screen.
    document.addEventListener('DOMContentLoaded', function () {
        var bar = document.querySelector('.reg-actions');
        if (!bar) return;

        var marker = document.createElement('div');
        bar.parentNode.insertBefore(marker, bar.nextSibling);

        var queued = false;
        function check() {
            queued = false;
            // A few pixels under the edge cover nothing worth a shadow.
            bar.classList.toggle('is-stuck', marker.getBoundingClientRect().top > window.innerHeight + 24);
        }
        function later() {
            if (!queued) { queued = true; requestAnimationFrame(check); }
        }

        ['scroll', 'resize', 'load'].forEach(function (type) { window.addEventListener(type, later); });
        // Changing step changes the form's height.
        document.addEventListener('click', function () { setTimeout(later, 50); });
        later();
    });

    window.bbRegSide = (function () {
        var BEFORE = {{ $before }};
        var TOTAL = {{ $total }};
        var RING = 943;

        var icons = document.querySelectorAll('.reg-orb-icon');
        var items = document.querySelectorAll('#regChecklist li[data-step]');

        return {
            show: function (n) {
                icons.forEach(function (icon, i) {
                    icon.classList.toggle('is-on', i === n - 1);
                    icon.classList.toggle('is-prev', i < n - 1);
                });

                items.forEach(function (li) {
                    var s = +li.dataset.step;
                    li.classList.toggle('is-done', s < n);
                    li.classList.toggle('is-current', s === n);
                });

                var name = items[n - 1] ? items[n - 1].dataset.name : '';
                document.querySelectorAll('[data-reg-num]').forEach(function (el) { el.textContent = 'Step ' + n + ' of ' + items.length; });
                document.querySelectorAll('[data-reg-name]').forEach(function (el) { el.textContent = name; });

                // Steps behind you count in full, the one you're on counts half.
                var done = BEFORE + (n - 1) + 0.5;
                document.getElementById('regRing').style.strokeDashoffset = Math.round(RING * (1 - done / TOTAL));
            }
        };
    })();
</script>
