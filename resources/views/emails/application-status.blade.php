<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BoomBuy Application Update</title>
</head>
<body style="margin:0; padding:0; background:#f8f6fe; font-family: Arial, sans-serif;">
    <div style="max-width:460px; margin:40px auto; background:#fff; border-radius:16px; border:1px solid #e9e4fa; padding:32px;">
        <div style="font-size:22px; font-weight:800; color:#6d4ad1; margin-bottom:20px; text-align:center;">
            Boom<span style="color:#221a3a;">Buy</span>
        </div>

        <p style="color:#221a3a; font-size:15px;">Hi {{ $userName }},</p>

        @if($status === 'Approved')

            <p style="color:#221a3a; font-size:14px; line-height:1.6;">
                Great news! Your <strong>{{ ucfirst($role) }}</strong> application on BoomBuy has been
                <strong style="color:#15803d;">approved</strong>. You can now log in and start using your
                {{ $role }} account.
            </p>

        @else

            <p style="color:#221a3a; font-size:14px; line-height:1.6;">
                We've reviewed your <strong>{{ ucfirst($role) }}</strong> application on BoomBuy and,
                unfortunately, it was <strong style="color:#be123c;">not approved</strong> at this time.
            </p>

            @if($remarks)
                <div style="background:#f8f6fe; border:1px solid #e9e4fa; border-radius:10px; padding:14px; margin:16px 0; color:#463c5e; font-size:13px;">
                    <strong>Reason:</strong> {{ $remarks }}
                </div>
            @endif

            <p style="color:#645a80; font-size:13px;">
                You're welcome to review the requirements and submit a new application.
            </p>

        @endif

        <p style="color:#645a80; font-size:12px; margin-top:24px;">
            If you have questions, please contact BoomBuy support.
        </p>
    </div>
</body>
</html>
