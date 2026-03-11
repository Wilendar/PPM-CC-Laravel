<!DOCTYPE html>
<html lang="pl" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #1a1a2e; font-family: Arial, Helvetica, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">

    <!-- Outer wrapper table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #1a1a2e; padding: 40px 20px;">
        <tr>
            <td align="center" style="padding: 0;">

                <!-- Main container 600px -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #16213e; border-radius: 12px; overflow: hidden; max-width: 600px; width: 100%;">

                    <!-- ============================================ -->
                    <!-- GRADIENT HEADER                              -->
                    <!-- ============================================ -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #e0ac7e, #d1975a); padding: 32px 30px; text-align: center;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 2px; line-height: 1.2;">
                                            PPM
                                        </h1>
                                        <p style="color: rgba(255, 255, 255, 0.85); margin: 6px 0 0; font-size: 13px; font-weight: 400; letter-spacing: 0.5px;">
                                            Prestashop Product Manager
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ============================================ -->
                    <!-- CONTENT AREA                                 -->
                    <!-- ============================================ -->
                    <tr>
                        <td style="padding: 40px 30px 36px;">

                            <!-- Type Badge -->
                            @php
                                $badgeColors = [
                                    'sync_failed'        => ['bg' => '#ef4444', 'text' => '#ffffff', 'label' => 'Synchronizacja'],
                                    'import_ready'       => ['bg' => '#22c55e', 'text' => '#ffffff', 'label' => 'Import'],
                                    'import_scheduled'   => ['bg' => '#22c55e', 'text' => '#ffffff', 'label' => 'Import'],
                                    'import_published'   => ['bg' => '#22c55e', 'text' => '#ffffff', 'label' => 'Import'],
                                    'login_new_ip'       => ['bg' => '#f59e0b', 'text' => '#1a1a2e', 'label' => 'Bezpieczenstwo'],
                                    'security'           => ['bg' => '#f59e0b', 'text' => '#1a1a2e', 'label' => 'Bezpieczenstwo'],
                                    'new_user_pending'   => ['bg' => '#3b82f6', 'text' => '#ffffff', 'label' => 'Uzytkownicy'],
                                    'backup_completed'   => ['bg' => '#6b7280', 'text' => '#ffffff', 'label' => 'System'],
                                    'system'             => ['bg' => '#6b7280', 'text' => '#ffffff', 'label' => 'System'],
                                ];

                                $badge = $badgeColors[$type] ?? ['bg' => '#6b7280', 'text' => '#ffffff', 'label' => ucfirst(str_replace('_', ' ', $type))];
                            @endphp

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background-color: {{ $badge['bg'] }}; color: {{ $badge['text'] }}; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 5px 14px; border-radius: 20px; line-height: 1.4;">
                                        {{ $badge['label'] }}
                                    </td>
                                </tr>
                            </table>

                            <!-- Title -->
                            <h2 style="color: #f8fafc; margin: 20px 0 0; font-size: 22px; font-weight: 600; line-height: 1.4;">
                                {{ $title }}
                            </h2>

                            <!-- Separator -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 20px 0;">
                                <tr>
                                    <td style="border-top: 1px solid #374151; font-size: 0; line-height: 0;">&nbsp;</td>
                                </tr>
                            </table>

                            <!-- Message body -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: #cbd5e1; font-size: 15px; line-height: 1.7; padding: 0;">
                                        {!! nl2br(e($notificationMessage)) !!}
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            @if(!empty($actionUrl) && !empty($actionText))
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 32px auto 0;" align="center">
                                    <tr>
                                        <td align="center" style="border-radius: 8px; background: linear-gradient(135deg, #e0ac7e, #d1975a);">
                                            <a href="{{ $actionUrl }}" target="_blank" style="display: inline-block; padding: 14px 36px; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; letter-spacing: 0.3px; border-radius: 8px; line-height: 1.2;">
                                                {{ $actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                        </td>
                    </tr>

                    <!-- ============================================ -->
                    <!-- FOOTER                                       -->
                    <!-- ============================================ -->
                    <tr>
                        <td style="padding: 24px 30px; border-top: 1px solid #374151; text-align: center;">
                            <p style="color: #6b7280; font-size: 12px; line-height: 1.6; margin: 0;">
                                &copy; {{ date('Y') }} MPP TRADE Sp. z o.o. | Prestashop Product Manager
                            </p>
                            <p style="color: #4b5563; font-size: 11px; line-height: 1.5; margin: 10px 0 0;">
                                To powiadomienie zostalo wyslane automatycznie.
                                <br>
                                Prosimy nie odpowiadac na te wiadomosc.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Main container -->

            </td>
        </tr>
    </table>
    <!-- /Outer wrapper -->

</body>
</html>
