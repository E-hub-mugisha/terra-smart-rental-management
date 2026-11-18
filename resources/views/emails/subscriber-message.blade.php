<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
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
                            <p style="margin:0 0 16px;font-size:15px;">Dear {{ $recipientName }},</p>
                            <div style="font-size:14px;line-height:1.75;color:#33405e;">{!! nl2br(e($body)) !!}</div>
                            @if ($actionUrl)
                                <p style="margin:24px 0 0;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#D05208;color:#fff;text-decoration:none;padding:11px 20px;border-radius:9px;font-size:14px;">Open Terra</a>
                                </p>
                            @endif
                            <p style="margin:26px 0 0;font-size:14px;color:#4b5670;">Kind regards,<br>{{ $senderName }}<br>Terra Smart Rental Management</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px;background:#f8f9fc;font-size:12px;color:#748094;">
                            You can reply to this email or message us from your Terra account.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
