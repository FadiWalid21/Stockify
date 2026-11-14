<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Stockify Password Reset</title>
</head>
<body style="margin:0; padding:30px; background:#f4f6f9; font-family:Arial,sans-serif;">

    <div style="max-width:520px; margin:auto; background:#ffffff; border-radius:12px; overflow:hidden;">

        <div style="background:#172554; padding:25px; text-align:center;">
            <h1 style="color:#ffffff; margin:0;">Stockify</h1>
            <p style="color:#cbd5e1; margin-bottom:0;">Inventory Management Made Simple</p>
        </div>

        <div style="padding:30px; color:#334155;">

            <h2>Password Reset Request</h2>

            <p>Hello {{ $user->name }},</p>

            <p>
                We received a request to reset your Stockify account password.
                Use the verification code below to continue.
            </p>

            <div style="background:#f1f5f9; padding:20px; text-align:center; border-radius:8px; margin:25px 0;">
                <span style="font-size:32px; font-weight:bold; letter-spacing:8px; color:#172554;">
                    {{ $otp }}
                </span>
            </div>

            <p>
                This code expires in <strong>{{ $expiresIn }} minutes</strong>.
            </p>

            <p>
                If you didn't request a password reset, you can safely ignore this email.
                Your password will remain unchanged.
            </p>

            <p>Best regards,<br>Stockify Team</p>

        </div>

    </div>

</body>
</html>