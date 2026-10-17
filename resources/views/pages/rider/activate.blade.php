<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activate Rider Account - BoomBuy</title>

    @include('partials.pwa-head')
    @include('partials.design-tokens')

</head>
<body style="margin:0; padding:0 16px; background:#f8f6fe; font-family: Arial, sans-serif;">

    <div style="max-width:420px; margin:60px auto; background:#fff; border-radius:16px; border:1px solid #e9e4fa; padding:32px; text-align:center;">

        <div style="font-size:22px; font-weight:800; margin-bottom:16px;">
            <img src="{{ vasset('images/logo-wordmark.svg') }}" alt="BoomBuy" class="bb-logo-wordmark" width="131" height="46">
        </div>

        @if ($problem)

            <h1 style="font-size:20px; color:#221a3a;">Link can't be used</h1>
            <p style="color:#be123c; font-size:14px; line-height:1.6;">{{ $problem }}</p>
            <a href="{{ route('logistics.login') }}" style="color:#6d4ad1; font-size:13px;">Go to login</a>

        @else

            <h1 style="font-size:20px; color:#221a3a;">Activate your rider account</h1>
            <p style="color:#645a80; font-size:14px;">
                Hi {{ $riderName }}, set a password to start using BoomBuy.
            </p>

            @if ($errors->any())
                <div style="background:#fee2e2; color:#dc2626; padding:10px; border-radius:8px; margin-bottom:16px; font-size:13px; text-align:left;">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('rider.activate.submit', $token) }}" style="text-align:left;">
                @csrf
                <label for="password" style="font-size:13px; font-weight:700; color:#221a3a;">Password</label>
                <input type="password" id="password" name="password" minlength="8" required autofocus
                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}"
                    title="At least 8 characters with an uppercase letter, a lowercase letter, a number and a special character."
                    style="width:100%; box-sizing:border-box; font-size:15px; padding:12px; border:1px solid #e9e4fa; border-radius:10px; margin:6px 0 4px;">
                <div style="font-size:11px; color:#857ca0; margin-bottom:14px;">At least 8 characters, with an uppercase letter, a lowercase letter, a number and a special character.</div>

                <label for="password_confirmation" style="font-size:13px; font-weight:700; color:#221a3a;">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    style="width:100%; box-sizing:border-box; font-size:15px; padding:12px; border:1px solid #e9e4fa; border-radius:10px; margin:6px 0 18px;">

                <button type="submit"
                    style="width:100%; background:#6d4ad1; color:#fff; border:none; padding:14px; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer;">
                    Activate
                </button>
            </form>

        @endif

    </div>

    @include('partials.pwa-register')

</body>
</html>
