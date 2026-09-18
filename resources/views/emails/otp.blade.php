<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your BoomBuy Verification Code</title>
</head>
<body style="margin:0; padding:0; background:#fff7f4; font-family: Arial, sans-serif;">
    <div style="max-width:420px; margin:40px auto; background:#fff; border-radius:16px; border:1px solid #f7e5e0; padding:32px; text-align:center;">
        <div style="font-size:22px; font-weight:800; color:#e8420f; margin-bottom:16px;">
            Boom<span style="color:#172033;">Buy</span>
        </div>
        <p style="color:#172033; font-size:15px;">Hi {{ $userName }},</p>
        <p style="color:#977970; font-size:14px;">Gamitin ang code na ito para makapag-verify ng iyong email:</p>
        <div style="font-size:32px; font-weight:800; letter-spacing:10px; color:#e8420f; margin:20px 0;">
            {{ $otpCode }}
        </div>
        <p style="color:#977970; font-size:12px;">Mag-e-expire ito sa loob ng 10 minuto. Kung hindi ikaw ang humiling nito, i-ignore mo na lang ang email na ito.</p>
    </div>
</body>
</html>