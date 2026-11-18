<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isReset ? 'Password reset' : 'Welcome to Terra' }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fa;font-family:'Source Serif 4',Georgia,serif;color:#19265d;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:560px;background:#ffffff;border:1px solid #e7eaf0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:#19265d;padding:22px 32px;">
                            <span style="color:#ffffff;font-size:20px;font-weight:700;">Terra<span style="color:#D05208;">.</span></span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px;">
                            <h1 style="margin:0 0 12px;font-size:20px;">{{ $isReset ? 'Your password was reset' : 'Welcome, ' . $staffName }}</h1>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#4b5670;">
                                @if ($isReset)
                                    A new password has been generated for your Terra field staff account.
                                @else
                                    A Terra field staff account has been created for you. Use it to register
                                    property owners and property managers when you meet them in the field.
                                @endif
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fc;border:1px solid #e7eaf0;border-radius:12px;">
                                <tr><td style="padding:14px 18px;font-size:13px;color:#748094;">Staff number</td><td style="padding:14px 18px;font-size:14px;font-weight:700;">{{ $staffNumber }}</td></tr>
                                <tr><td style="padding:0 18px 14px;font-size:13px;color:#748094;">Email</td><td style="padding:0 18px 14px;font-size:14px;font-weight:700;">{{ $email }}</td></tr>
                                <tr><td style="padding:0 18px 14px;font-size:13px;color:#748094;">Temporary password</td><td style="padding:0 18px 14px;font-size:14px;font-weight:700;font-family:monospace;">{{ $password }}</td></tr>
                            </table>
                            <p style="margin:22px 0 0;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;background:#D05208;color:#fff;text-decoration:none;padding:11px 20px;border-radius:9px;font-size:14px;">Sign in to Terra</a>
                            </p>
                            <p style="margin:20px 0 0;font-size:13px;color:#748094;">Please change this password after you sign in.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
