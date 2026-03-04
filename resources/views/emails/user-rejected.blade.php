<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informacja o koncie</title>
</head>
<body style="margin: 0; padding: 0; background-color: #1a1a2e; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a2e; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #16213e; border-radius: 12px; overflow: hidden;">
                    <tr>
                        <td style="background: linear-gradient(135deg, #e0ac7e, #d1975a); padding: 30px; text-align: center;">
                            <h1 style="color: #fff; margin: 0; font-size: 24px;">PPM - Prestashop Product Manager</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #f87171; margin: 0 0 20px;">Informacja o Twoim koncie</h2>
                            <p style="color: #d1d5db; font-size: 16px; line-height: 1.6; margin: 0 0 15px;">
                                Witaj {{ $user->full_name }},
                            </p>
                            <p style="color: #d1d5db; font-size: 16px; line-height: 1.6; margin: 0 0 15px;">
                                Niestety, Twoje konto w systemie PPM nie zostalo zatwierdzone.
                            </p>
                            @if($reason)
                            <div style="background-color: #1f2937; border-left: 4px solid #f87171; padding: 15px 20px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                                <p style="color: #9ca3af; font-size: 14px; margin: 0 0 5px;">Powod:</p>
                                <p style="color: #d1d5db; font-size: 15px; margin: 0;">{{ $reason }}</p>
                            </div>
                            @endif
                            <p style="color: #9ca3af; font-size: 14px; line-height: 1.6; margin: 20px 0 0;">
                                Jesli masz pytania, skontaktuj sie z administratorem systemu.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px; border-top: 1px solid #374151; text-align: center;">
                            <p style="color: #6b7280; font-size: 12px; margin: 0;">
                                &copy; {{ date('Y') }} MPP TRADE - PPM System
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
