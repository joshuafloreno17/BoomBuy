<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your BoomBuy Verification Code</title>
</head>
<body style="margin:0; padding:0; background:#f8f6fe; font-family: Arial, sans-serif;">
    <div style="max-width:420px; margin:40px auto; background:#fff; border-radius:16px; border:1px solid #e9e4fa; padding:32px; text-align:center;">
        <div style="font-size:22px; font-weight:800; color:#6d4ad1; margin-bottom:16px;">
            Boom<span style="color:#221a3a;">Buy</span>
        </div>
        <p style="color:#221a3a; font-size:15px;">Hi {{ $userName }},</p>
        <p style="color:#645a80; font-size:14px;">Gamitin ang code na ito para makapag-verify ng iyong email:</p>
        <div style="font-size:32px; font-weight:800; letter-spacing:10px; color:#6d4ad1; margin:20px 0;">
            {{ $otpCode }}
        </div>
        <p style="color:#645a80; font-size:12px;">Mag-e-expire ito sa loob ng {{ $minutes }} minuto. Kung hindi ikaw ang humiling nito, i-ignore mo na lang ang email na ito.</p>
    </div>
</body>
</html>