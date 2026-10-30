@php
    // The last step of every sign-up: the left side shows the steps of the
    // form they just filled in, all done, with the email code next.
    $role = session('pending_registration.role') ?? 'buyer';
    $isLogistics = $role === 'logistics';

    $flows = [
        'buyer' => ['Buyer account', route('buyer.register'), [
            ['Personal info', 'user'], ['Delivery address', 'pin'], ['ID and password', 'shield'], ['Review', 'review'],
        ]],
        'seller' => ['Seller account', route('seller.register'), [
            ['Personal info', 'user'], ['Business details', 'brief'], ['Shop address', 'pin'], ['Documents', 'docs'],
        ]],
        'logistics' => ['Logistics account', route('logistics.register'), [
            ['Contact person', 'user'], ['Company details', 'brief'], ['Address and service areas', 'map'], ['Documents', 'docs'],
        ]],
        'rider' => ['Rider account', route('rider.apply'), [
            ['Rider application', 'user'],
        ]],
    ];
    [$kicker, $registerUrl, $steps] = $flows[$role] ?? $flows['buyer'];

    // Counts down from when the code was sent, so it keeps running
    // while the user is away in the mail app.
    $otpExpiresAt = session('otp_expires_at');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $isLogistics ? 'Verify Code - BoomBuy Logistics' : 'Verify Code - BoomBuy', 'fonts' => true])

    <link rel="stylesheet" href="{{ vasset('css/views/register-shell.css') }}">
    <link rel="stylesheet" href="{{ vasset('css/views/verify-otp.css') }}">
</head>
<body class="reg-night {{ $isLogistics ? 'is-logistics' : '' }}">

<div class="reg">

    @include('partials.register-side', [
        'logistics' => $isLogistics,
        'verifying' => true,
        'kicker' => $kicker,
        'headline' => ['One last', 'step.'],
        'blurb' => 'We just need to confirm your email address.',
        'steps' => $steps,
    ])

    <main class="reg-main">
    <div class="reg-inner otp-card">

        <div class="reg-head">
            <div class="reg-card-top">
                <span class="reg-kicker">Verify your email</span>
            </div>
            {{-- Padlock that clicks open when the code is right (verify-otp.css). --}}
            <div class="otp-lock" aria-hidden="true">
                <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                    <path class="otp-shackle" d="M10 15v-4a6 6 0 0 1 12 0v4"/>
                    <rect x="6" y="15" width="20" height="13" rx="3"/>
                    <path d="M16 20v3"/>
                </svg>
                @for ($i = 0; $i < 8; $i++)
                    <i class="otp-spark" style="--a: {{ $i * 45 + 20 }}deg"></i>
                @endfor
            </div>
            <h2 data-otp-title>Enter the 6-digit code</h2>
            <p class="otp-sub" data-otp-sub>
                We sent a code to <strong>{{ session('otp_email') }}</strong>. It expires in 5 minutes.
            </p>
        </div>

        @if ($otpExpiresAt)
            <p class="otp-timer" id="otpTimer" data-expires="{{ \Illuminate\Support\Carbon::parse($otpExpiresAt)->toIso8601String() }}">
                Code expires in <strong id="otpTimerLeft">5:00</strong>
            </p>
        @endif

        @if (session('error'))
            <div class="otp-alert is-error" role="alert">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="otp-alert is-error" role="alert">{{ $errors->first() }}</div>
        @endif

        @if (session('status'))
            <div class="otp-alert is-ok" role="status">{{ session('status') }}</div>
        @endif

        {{-- With JavaScript the single input turns into six boxes (data-no-busy:
             the page shows its own "Verifying" state). Without it, a plain form. --}}
        <form method="POST" action="{{ route('otp.verify') }}" class="otp-form" id="otpForm" data-no-busy>
            @csrf
            <label for="otp_code" class="otp-label">Verification code</label>
            <div class="otp-entry">
                <div class="otp-boxes" aria-hidden="true">
                    @for ($i = 0; $i < 6; $i++)
                        <span class="otp-box" style="--i: {{ $i }}"><span></span></span>
                    @endfor
                </div>
                <input type="text" id="otp_code" name="otp_code" maxlength="6" placeholder="000000"
                    inputmode="numeric" pattern="\d{6}" title="Enter the 6-digit code." autocomplete="one-time-code"
                    class="otp-input" required autofocus>
            </div>
            <p class="otp-msg" id="otpMsg" role="alert"></p>

            <button type="submit" class="otp-btn" id="otpBtn">Verify and finish</button>
        </form>

        <div class="otp-foot">
            <a href="{{ $registerUrl }}">← Wrong email? Start again</a>

            <form method="POST" action="{{ route('otp.resend') }}">
                @csrf
                <span>Didn’t get it?</span>
                <button type="submit">Resend code</button>
            </form>
        </div>

    </div>
    </main>

</div>

<script>
(function () {
    var timer = document.getElementById('otpTimer');
    if (!timer) return;
    var left = document.getElementById('otpTimerLeft');
    var end = new Date(timer.dataset.expires).getTime();

    function tick() {
        var s = Math.max(0, Math.round((end - Date.now()) / 1000));
        if (s === 0) {
            timer.innerHTML = 'The code has expired. Tap <strong>Resend code</strong> for a new one.';
            timer.classList.add('is-expired');
            return;
        }
        left.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        setTimeout(tick, 1000);
    }

    tick();
    // Coming back from the mail app: show the real time left at once.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && Date.now() < end) {
            var s = Math.round((end - Date.now()) / 1000);
            left.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        }
    });
})();

/*
 * Six boxes over the real input: typing, paste and phone autofill all go
 * into the hidden input, the boxes only draw it. The 6th digit verifies by
 * itself (fetch → JSON from AuthController@verifyOtp): a right code plays the
 * unlock, a wrong one shakes and clears. Any network trouble falls back to a
 * normal form submit.
 */
(function () {
    var form = document.getElementById('otpForm');
    if (!form || !window.fetch) return;

    var card = form.closest('.otp-card');
    var input = document.getElementById('otp_code');
    var boxes = form.querySelectorAll('.otp-box');
    var btn = document.getElementById('otpBtn');
    var msg = document.getElementById('otpMsg');
    var title = card.querySelector('[data-otp-title]');
    var sub = card.querySelector('[data-otp-sub]');
    var busy = false, done = false, next = null;

    card.classList.add('is-boxes');
    input.placeholder = '';

    function draw() {
        var val = input.value.replace(/\D/g, '').slice(0, 6);
        if (input.value !== val) input.value = val;
        var focused = document.activeElement === input;
        boxes.forEach(function (box, i) {
            var d = val[i] || '';
            box.classList.toggle('has', !!d);
            if (d) box.firstChild.textContent = d;
            box.classList.toggle('cur', focused && !busy && i === Math.min(val.length, 5) && !(val.length === 6 && i === 5));
        });
        btn.disabled = val.length !== 6 || busy;
        return val;
    }

    function say(text, kind) {
        msg.textContent = text || '';
        msg.className = 'otp-msg' + (kind ? ' is-' + kind : '');
    }

    function verify() {
        if (busy || done || draw().length !== 6) return;
        busy = true; input.readOnly = true; draw();
        btn.innerHTML = '<span class="otp-spin"></span> Verifying';
        say('');

        fetch(form.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: new FormData(form)
        })
            .then(function (r) {
                return r.json().catch(function () { return null; }).then(function (data) { return { status: r.status, data: data }; });
            })
            .then(function (res) {
                var data = res.data;
                if (res.status === 419) { window.location.reload(); return; } // page sat open too long
                if (!data) throw new Error('no json'); // 500 etc.: let the normal form handle it
                if (res.status === 429) return fail('Too many tries. Wait a minute, then try again.');
                if (data.ok) return success(data);
                if (data.redirect) { window.location.href = data.redirect; return; }
                fail(data.message || 'That code didn’t match. Try again.');
            })
            .catch(function () { form.submit(); });
    }

    function success(data) {
        done = true; busy = false;
        next = data.redirect;
        input.blur();
        card.classList.add('is-ok');
        var timer = document.getElementById('otpTimer');
        if (timer) timer.hidden = true;
        title.textContent = 'You’re verified';
        sub.textContent = data.message || 'Your account has been created.';
        btn.innerHTML = 'Continue to log in <span class="otp-arr">→</span>';
        btn.disabled = false;
        draw();
    }

    function fail(text) {
        busy = false;
        card.classList.add('is-bad');
        say(text, 'bad');
        var entry = form.querySelector('.otp-entry');
        entry.classList.remove('otp-shake'); void entry.offsetWidth; entry.classList.add('otp-shake');
        btn.innerHTML = 'Verify and finish';
        setTimeout(function () {
            input.readOnly = false;
            input.value = '';
            draw();
            input.focus();
        }, 700);
    }

    input.addEventListener('input', function () {
        if (busy || done) return;
        if (card.classList.contains('is-bad')) { card.classList.remove('is-bad'); say(''); }
        if (draw().length === 6) setTimeout(verify, 250);
    });
    input.addEventListener('focus', draw);
    input.addEventListener('blur', draw);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (done && next) { window.location.href = next; return; }
        verify();
    });

    draw();
})();
</script>

@include('partials.pwa-register')

</body>
</html>
