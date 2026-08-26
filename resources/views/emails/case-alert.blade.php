{{--
    Table-based HTML so the card survives Outlook / Gmail.
    Colours are the design-system tokens, inlined because email CSS is unreliable.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $headline }}</title>
</head>
<body style="margin:0;padding:0;background-color:#F7F5EF;font-family:Inter,Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F7F5EF;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:560px;max-width:100%;background-color:#FFFFFF;border:1px solid #EDEAE2;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#132A45;padding:18px 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle" style="padding-right:12px;">
                                        <div style="width:36px;height:36px;border-radius:18px;background-color:#2F7D6B;color:#FFFFFF;font-size:12px;font-weight:800;letter-spacing:0.04em;text-align:center;line-height:36px;">
                                            DC
                                        </div>
                                    </td>
                                    <td valign="middle">
                                        <div style="font-size:10px;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:#B8C4D1;">
                                            Government of Sindh
                                        </div>
                                        <div style="font-size:15px;font-weight:700;color:#FFFFFF;padding-top:2px;">
                                            District Coordination — Case Management System
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 8px 32px;">
                            <span style="display:inline-block;background-color:{{ $badgeColors['background'] }};color:{{ $badgeColors['text'] }};border-radius:999px;padding:4px 12px;font-size:12px;font-weight:700;line-height:1.3;">
                                {{ $badge }}
                            </span>
                            <h1 style="margin:14px 0 0 0;font-size:26px;line-height:1.25;font-weight:800;color:#132A45;">
                                {{ $headline }}
                            </h1>
                            <p style="margin:12px 0 0 0;font-size:15px;line-height:1.55;color:#3D4854;">
                                {{ $intro }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px 8px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F7F5EF;border:1px solid #EDEAE2;border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0;font-size:15px;line-height:1.4;color:#1A1A1A;">
                                            <strong>{{ $placeLine }}</strong>
                                        </p>
                                        <p style="margin:8px 0 0 0;font-size:14px;line-height:1.5;color:#3D4854;">
                                            {{ \Illuminate\Support\Str::limit($case->issue_summary, 180) }}
                                        </p>
                                        <p style="margin:10px 0 0 0;font-size:12px;line-height:1.4;color:#8A7F6E;">
                                            {{ $metaLine }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 32px 8px 32px;">
                            <a href="{{ $actionUrl }}"
                               style="display:inline-block;background-color:#132A45;color:#FFFFFF;font-size:14px;font-weight:700;text-decoration:none;border-radius:10px;padding:13px 22px;">
                                Open case in the portal
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px 28px 32px;">
                            <div style="border-top:1px solid #EDEAE2;padding-top:16px;">
                                <p style="margin:0;font-size:12px;line-height:1.5;color:#8A7F6E;">
                                    Automated alert · {{ $officeName }}
                                </p>
                                <p style="margin:8px 0 0 0;font-size:12px;line-height:1.5;">
                                    <a href="{{ $settingsUrl }}" style="color:#2E5FA8;text-decoration:underline;">
                                        Manage notification settings
                                    </a>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
