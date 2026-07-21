<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Bem-vindo ao EstampaFlow</title>
</head>
<body style="margin:0;padding:0;background:#f7f8fb;color:#1d1b24;font-family:Inter,Arial,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        Seu ambiente no EstampaFlow está pronto para começar.
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f7f8fb;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border:1px solid #e5e8ef;border-radius:22px;overflow:hidden;box-shadow:0 16px 50px rgba(38,25,69,.08);">
                    <tr>
                        <td style="padding:30px 32px;background:#f8f6ff;border-bottom:1px solid #e5e8ef;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle">
                                        <div style="font-size:21px;font-weight:800;letter-spacing:-.4px;color:#1d1b24;">
                                            Estampa<span style="color:#8a4fff;">Flow</span>
                                        </div>
                                        <div style="margin-top:5px;font-size:12px;color:#626573;">Gestão inteligente para estamparias</div>
                                    </td>
                                    <td align="right" valign="middle">
                                        <span style="display:inline-block;padding:7px 12px;border-radius:999px;background:#eeeaff;color:#5d2dc0;font-size:11px;font-weight:800;">CONTA CRIADA</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:34px 32px 16px;">
                            <div style="font-size:13px;font-weight:700;color:#8a4fff;">Olá, {{ $userName }}!</div>
                            <h1 style="margin:10px 0 12px;font-size:28px;line-height:1.2;letter-spacing:-.7px;color:#14121a;">Sua estamparia já está no EstampaFlow.</h1>
                            <p style="margin:0;font-size:15px;line-height:1.7;color:#626573;">
                                O ambiente de <strong style="color:#272730;">{{ $tenantName }}</strong> foi criado e seu período de teste já começou. Use os dados abaixo para acessar seu espaço exclusivo.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:14px 32px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#effffa;border:1px solid #dcefe9;border-radius:16px;">
                                <tr>
                                    <td style="padding:21px 22px;">
                                        <div style="font-size:11px;font-weight:800;letter-spacing:.08em;color:#626573;text-transform:uppercase;">Seu ambiente</div>
                                        <div style="margin-top:8px;font-size:16px;font-weight:800;color:#1d1b24;">{{ $tenantName }}</div>
                                        <div style="margin-top:5px;font-size:13px;line-height:1.5;color:#626573;word-break:break-all;">{{ $tenantUrl }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:12px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #e5e8ef;border-radius:16px;">
                                <tr>
                                    <td width="50%" valign="top" style="padding:18px 20px;border-right:1px solid #e5e8ef;">
                                        <div style="font-size:10px;font-weight:800;letter-spacing:.08em;color:#888b98;text-transform:uppercase;">E-mail de acesso</div>
                                        <div style="margin-top:7px;font-size:14px;font-weight:700;color:#272730;word-break:break-all;">{{ $loginEmail }}</div>
                                    </td>
                                    <td width="50%" valign="top" style="padding:18px 20px;">
                                        <div style="font-size:10px;font-weight:800;letter-spacing:.08em;color:#888b98;text-transform:uppercase;">Trial disponível até</div>
                                        <div style="margin-top:7px;font-size:14px;font-weight:700;color:#272730;">{{ $trialEndsAt }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:18px 32px 14px;">
                            <a href="{{ $loginUrl }}" style="display:inline-block;padding:14px 24px;border-radius:12px;background:#8a4fff;color:#ffffff;text-decoration:none;font-size:14px;font-weight:800;box-shadow:0 10px 24px rgba(138,79,255,.22);">Acessar minha estamparia</a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:12px 32px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8f6ff;border-radius:14px;">
                                <tr>
                                    <td style="padding:17px 19px;font-size:12px;line-height:1.65;color:#626573;">
                                        <strong style="color:#383943;">Sobre sua senha:</strong> por segurança, ela não é exibida nem enviada por e-mail. Use a mesma senha que você definiu no cadastro. Caso não se lembre, <a href="{{ $passwordResetUrl }}" style="color:#7138e6;font-weight:700;">redefina sua senha</a>.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 32px;background:#ffffff;border-top:1px solid #e5e8ef;font-size:11px;line-height:1.6;color:#888b98;text-align:center;">
                            Este e-mail foi enviado porque uma conta foi criada no EstampaFlow.<br>
                            Caso o botão não funcione, acesse: <a href="{{ $loginUrl }}" style="color:#7138e6;word-break:break-all;">{{ $loginUrl }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
