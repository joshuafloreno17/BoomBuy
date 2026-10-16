<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Activate your BoomBuy rider account</title>
</head>
<body style="margin:0; padding:0; background:#f8f6fe; font-family: Arial, sans-serif;">
    <div style="max-width:460px; margin:40px auto; background:#fff; border-radius:16px; border:1px solid #e9e4fa; padding:32px;">
        <div style="font-size:22px; font-weight:800; color:#6d4ad1; margin-bottom:20px; text-align:center;">
            Boom<span style="color:#221a3a;">Buy</span>
        </div>

        <p style="color:#221a3a; font-size:15px;">Hi {{ $userName }},</p>

        <p style="color:#221a3a; font-size:14px; line-height:1.6;">
            {{ $company ?? 'Your logistics company' }} added you as a <strong>rider</strong> on BoomBuy.
            Set your password to activate your account.
        </p>

        <p style="text-align:center; margin:24px 0;">
            <a href="{{ $url }}" style="display:inline-block; background:#6d4ad1; color:#fff; text-decoration:none; font-weight:700; padding:12px 22px; border-radius:10px;">
                Set my password
            </a>
        </p>

        <p style="color:#645a80; font-size:12px; line-height:1.6;">
            This link works for {{ $hours }} hours and only once. If it has expired, ask your logistics company to send a new one.
            If you were not expecting this email, you can ignore it.
        </p>
    </div>
</body>
</html>
