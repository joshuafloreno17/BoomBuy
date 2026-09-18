<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Code - BoomBuy</title>

    @include('partials.pwa-head')

</head>
<body style="margin:0; padding:0; background:#fff7f4; font-family: Arial, sans-serif;">

    <div style="max-width:420px; margin:60px auto; background:#fff; border-radius:16px; border:1px solid #f7e5e0; padding:32px; text-align:center;">

        <div style="font-size:22px; font-weight:800; color:#e8420f; margin-bottom:16px;">
            Boom<span style="color:#172033;">Buy</span>
        </div>

        <h1 style="font-size:20px; color:#172033;">Enter Verification Code</h1>
        <p style="color:#977970; font-size:14px;">
            We sent a 6-digit code to <strong>{{ session('otp_email') }}</strong>. It expires in 10 minutes.
        </p>

        @if ($errors->any())
            <div style="background:#ffe5e0; color:#c0392b; padding:10px; border-radius:8px; margin-bottom:16px; font-size:13px;">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('status'))
            <div style="background:#e5ffe8; color:#2c8c3c; padding:10px; border-radius:8px; margin-bottom:16px; font-size:13px;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('otp.verify') }}">
            @csrf
            <input type="text" name="otp_code" maxlength="6" placeholder="000000"
                style="width:100%; box-sizing:border-box; font-size:28px; letter-spacing:10px; text-align:center; padding:14px; border:1px solid #f7e5e0; border-radius:10px; margin-bottom:16px;" required autofocus>

            <button type="submit"
                style="width:100%; background:#e8420f; color:#fff; border:none; padding:14px; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer;">
                Verify
            </button>
        </form>

        <form method="POST" action="{{ route('otp.resend') }}" style="margin-top:16px;">
            @csrf
            <button type="submit" style="background:none; border:none; color:#e8420f; font-size:13px; cursor:pointer; text-decoration:underline;">
                Resend Code
            </button>
        </form>

    </div>

    @include('partials.pwa-register')

</body>
</html>