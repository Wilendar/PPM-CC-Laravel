<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowy uzytkownik</title>
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
                            <h2 style="color: #fbbf24; margin: 0 0 20px;">Nowy uzytkownik czeka na zatwierdzenie</h2>
                            <p style="color: #d1d5db; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Nowy uzytkownik zarejestrowal sie przez Microsoft OAuth i czeka na Twoje zatwierdzenie.
                            </p>
                            <div style="background-color: #1f2937; border-radius: 8px; padding: 20px; margin: 0 0 25px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="color: #9ca3af; font-size: 14px; padding: 5px 0;">Imie i nazwisko:</td>
                                        <td style="color: #fff; font-size: 14px; padding: 5px 0; text-align: right;">{{ $newUser->full_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #9ca3af; font-size: 14px; padding: 5px 0;">Email:</td>
                                        <td style="color: #fff; font-size: 14px; padding: 5px 0; text-align: right;">{{ $newUser->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #9ca3af; font-size: 14px; padding: 5px 0;">Domena:</td>
                                        <td style="color: #fff; font-size: 14px; padding: 5px 0; text-align: right;">{{ $newUser->oauth_domain }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #9ca3af; font-size: 14px; padding: 5px 0;">Data rejestracji:</td>
                                        <td style="color: #fff; font-size: 14px; padding: 5px 0; text-align: right;">{{ $newUser->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                            <table cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="background: linear-gradient(135deg, #e0ac7e, #d1975a); border-radius: 8px;">
                                        <a href="{{ url('/admin/users') }}" style="display: inline-block; padding: 14px 32px; color: #fff; text-decoration: none; font-weight: bold; font-size: 16px;">
                                            Przejdz do panelu uzytkownikow
                                        </a>
                                    </td>
                                </tr>
                            </table>
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
