<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activate Rider Account - BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

</head>
<body style="margin:0; padding:0 16px; background:#fff8f3; font-family: Arial, sans-serif;">

    <div style="max-width:420px; margin:60px auto; background:#fff; border-radius:16px; border:1px solid #f0e2da; padding:32px; text-align:center;">

        <div style="font-size:22px; font-weight:800; margin-bottom:16px;">
            <img src="{{ asset('images/icon.svg') }}" alt="" class="bb-logo-mark" width="32" height="32"><span class="bb-logo-word">BoomBuy</span>
        </div>

        @if ($problem)

            <h1 style="font-size:20px; color:#1b1a1f;">Link can't be used</h1>
            <p style="color:#be123c; font-size:14px; line-height:1.6;">{{ $problem }}</p>
            <a href="{{ route('login') }}" style="color:#e8420f; font-size:13px;">Go to login</a>

        @else

            <h1 style="font-size:20px; color:#1b1a1f;">Activate your rider account</h1>
            <p style="color:#6b6570; font-size:14px;">
                Hi {{ $riderName }}, set a password to start using BoomBuy.
            </p>

            @if ($errors->any())
                <div style="background:#ffe5e0; color:#c0392b; padding:10px; border-radius:8px; margin-bottom:16px; font-size:13px; text-align:left;">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('rider.activate.submit', $token) }}" style="text-align:left;">
                @csrf
                <label for="password" style="font-size:13px; font-weight:700; color:#1b1a1f;">Password</label>
                <input type="password" id="password" name="password" minlength="8" required autofocus
                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}"
                    title="At least 8 characters with an uppercase letter, a lowercase letter, a number and a special character."
                    style="width:100%; box-sizing:border-box; font-size:15px; padding:12px; border:1px solid #f0e2da; border-radius:10px; margin:6px 0 4px;">
                <div style="font-size:11px; color:#8a7f86; margin-bottom:14px;">At least 8 characters, with an uppercase letter, a lowercase letter, a number and a special character.</div>

                <label for="password_confirmation" style="font-size:13px; font-weight:700; color:#1b1a1f;">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    style="width:100%; box-sizing:border-box; font-size:15px; padding:12px; border:1px solid #f0e2da; border-radius:10px; margin:6px 0 18px;">

                <button type="submit"
                    style="width:100%; background:#e8420f; color:#fff; border:none; padding:14px; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer;">
                    Activate
                </button>
            </form>

        @endif

    </div>

    @include('partials.pwa-register')

</body>
</html>
