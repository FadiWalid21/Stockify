<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your Stockify email</title>
</head>

<body style="margin:0; padding:0; background:#f4f6fa;
             font-family:Arial,sans-serif; color:#1f2937;">

    <table width="100%" cellpadding="0" cellspacing="0"
           style="padding:40px 15px;">
        <tr>
            <td align="center">

                <table width="100%" cellpadding="0" cellspacing="0"
                       style="max-width:560px; background:#ffffff;
                              border-radius:12px; overflow:hidden;">

                    <tr>
                        <td align="center"
                            style="background:#172554; padding:30px;">
                            <h1 style="color:#ffffff; margin:0;">
                                Stockify
                            </h1>
                            <p style="color:#bfdbfe; font-size:12px;">
                                INVENTORY MANAGEMENT, SIMPLIFIED
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:40px 30px;">

                            <h2>Welcome to Stockify!</h2>

                            <p>
                                Hello {{ $user->name }},
                            </p>

                            <p>
                                Thank you for creating your Stockify account.
                                We're excited to have you on board.
                            </p>

                            <p>
                                Before you get started, please verify
                                your email address.
                            </p>

                            <table width="100%">
                                <tr>
                                    <td align="center" style="padding:25px 0;">
                                        <a href="{{ $verificationUrl }}"
                                           style="background:#2563eb;
                                                  color:#ffffff;
                                                  padding:15px 28px;
                                                  text-decoration:none;
                                                  border-radius:6px;
                                                  font-weight:bold;">
                                            Verify my email address
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:13px; color:#6b7280;">
                                This verification link expires after
                                60 minutes.
                            </p>

                            <p style="font-size:13px; color:#6b7280;">
                                If the button doesn't work, copy this
                                link into your browser:
                            </p>

                            <p style="font-size:12px; word-break:break-all;">
                                <a href="{{ $verificationUrl }}">
                                    {{ $verificationUrl }}
                                </a>
                            </p>

                            <p>
                                If you didn't create a Stockify account,
                                you can safely ignore this email.
                            </p>

                            <p>
                                Best regards,<br>
                                <strong>The Stockify Team</strong>
                            </p>

                        </td>
                    </tr>

                    <tr>
                        <td align="center"
                            style="background:#f9fafb; padding:20px;
                                   color:#6b7280; font-size:12px;">
                            © {{ date('Y') }} Stockify.
                            All rights reserved.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>