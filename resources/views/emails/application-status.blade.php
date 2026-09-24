<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BoomBuy Application Update</title>
</head>
<body style="margin:0; padding:0; background:#fff7f4; font-family: Arial, sans-serif;">
    <div style="max-width:460px; margin:40px auto; background:#fff; border-radius:16px; border:1px solid #f7e5e0; padding:32px;">
        <div style="font-size:22px; font-weight:800; color:#e8420f; margin-bottom:20px; text-align:center;">
            Boom<span style="color:#172033;">Buy</span>
        </div>

        <p style="color:#172033; font-size:15px;">Hi {{ $userName }},</p>

        @if($status === 'Approved')

            <p style="color:#172033; font-size:14px; line-height:1.6;">
                Great news! Your <strong>{{ ucfirst($role) }}</strong> application on BoomBuy has been
                <strong style="color:#15803d;">approved</strong>. You can now log in and start using your
                {{ $role }} account.
            </p>

        @else

            <p style="color:#172033; font-size:14px; line-height:1.6;">
                We've reviewed your <strong>{{ ucfirst($role) }}</strong> application on BoomBuy and,
                unfortunately, it was <strong style="color:#be123c;">not approved</strong> at this time.
            </p>

            @if($remarks)
                <div style="background:#fff8f5; border:1px solid #f4e6e1; border-radius:10px; padding:14px; margin:16px 0; color:#563a32; font-size:13px;">
                    <strong>Reason:</strong> {{ $remarks }}
                </div>
            @endif

            <p style="color:#977970; font-size:13px;">
                You're welcome to review the requirements and submit a new application.
            </p>

        @endif

        <p style="color:#977970; font-size:12px; margin-top:24px;">
            If you have questions, please contact BoomBuy support.
        </p>
    </div>
</body>
</html>
