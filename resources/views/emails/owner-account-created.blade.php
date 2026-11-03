<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isReset ? 'Credentials reset' : 'Welcome to Terra' }}</title>
</head>

<body style="margin:0;padding:0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#19265d;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:560px;background:#ffffff;border:1px solid #e7eaf0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:#19265d;padding:26px 32px;">
                            <span style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:.3px;">
                                Terra<span style="color:#D05208;">.</span>
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 12px;font-size:21px;color:#19265d;">
                                {{ $isReset ? 'Your credentials were reset' : 'Welcome, ' . $ownerName }}
                            </h1>

                            <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#4b5670;">
                                @if ($isReset)
                                Hello {{ $ownerName }}, a new password has been generated for your
                                Terra property owner account. Use the details below to sign in.
                                @else
                                A property owner account has been created for you on Terra.
                                Use the details below to sign in and view your records.
                                @endif
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="background:#fffaf6;border:1px solid #f3dfc8;border-radius:12px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:18px 20px;font-size:13px;line-height:2;">
                                        <span style="color:#748094;">Owner number</span><br>
                                        <strong style="font-size:14px;">{{ $ownerNumber }}</strong><br>

                                        <span style="color:#748094;">Email (username)</span><br>
                                        <strong style="font-size:14px;">{{ $email }}</strong><br>

                                        <span style="color:#748094;">Temporary password</span><br>
                                        <strong style="font-size:16px;font-family:'Courier New',monospace;letter-spacing:.5px;color:#D05208;">{{ $password }}</strong>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="background:#D05208;border-radius:9px;">
                                        <a href="{{ $loginUrl }}"
                                            style="display:inline-block;padding:13px 26px;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">
                                            Sign in to your account
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:12px;line-height:1.7;color:#748094;">
                                For your security, please change this password after your first sign-in
                                and never share it with anyone. If you were not expecting this email,
                                contact the Terra team.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 32px;border-top:1px solid #e7eaf0;font-size:11px;color:#a1a9b6;">
                            &copy; {{ date('Y') }} Terra Smart Rental Management. This is an automated message.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>