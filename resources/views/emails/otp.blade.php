<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $subject }}</title>
    <style>
        @media only screen and (max-width: 640px) {
            .email-shell {
                padding: 28px 20px 0 !important;
            }

            .email-title {
                font-size: 30px !important;
            }

            .otp-code {
                font-size: 42px !important;
                letter-spacing: 10px !important;
            }

            .download-block {
                text-align: left !important;
            }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#ffffff; color:#5f646d; font-family:Inter, Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; background:#ffffff;">
        <tr>
            <td class="email-shell" style="padding:56px 72px 0;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%; max-width:980px; border-collapse:collapse;">
                    <tr>
                        <td style="padding:0 0 52px;">
                            @if ($isSeller)
                                <table role="presentation" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td style="vertical-align:middle; padding:0 24px 0 0;">
                                            <img src="{{ $brandMarkUrl }}" width="88" height="88" alt="" style="display:block; width:88px; height:88px; border:0;">
                                        </td>
                                        <td style="vertical-align:middle;">
                                            <img src="{{ $wordmarkUrl }}" width="150" alt="Kresek.in" style="display:block; width:150px; max-width:150px; height:auto; border:0;">
                                            <p style="margin:4px 0 0; color:#8a8a8a; font-size:24px; line-height:1.1;">{{ $brandLabel }}</p>
                                        </td>
                                    </tr>
                                </table>
                            @else
                                <img src="{{ $brandMarkUrl }}" width="88" height="88" alt="" style="display:block; width:88px; height:88px; border:0;">
                                <img src="{{ $wordmarkUrl }}" width="150" alt="Kresek.in" style="display:block; width:150px; max-width:150px; height:auto; border:0; margin-top:8px;">
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <h1 class="email-title" style="margin:0; color:#5f646d; font-size:34px; line-height:1.22; font-weight:700;">{{ $heading }}</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-top:44px;">
                            <p style="margin:0; color:#5f646d; font-size:19px; line-height:1.6;">{{ $intro }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:48px 0 52px;">
                            <table role="presentation" width="520" cellspacing="0" cellpadding="0" style="width:520px; max-width:100%; border-collapse:separate; border:1px solid #8c8c8c; border-radius:7px;">
                                <tr>
                                    <td align="center" class="otp-code" style="padding:16px 22px 18px; color:#5f646d; font-size:52px; line-height:1; font-weight:800; letter-spacing:18px;">{{ $otp }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-bottom:24px;">
                            <p style="margin:0; color:#5f646d; font-size:18px; line-height:1.6;">Email dibuat secara otomatis. Mohon tidak mengirimkan balasan ke email ini.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="border-top:1px solid #bfc1c5; padding-top:22px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; background:#ffe3e3; border-radius:7px;">
                                <tr>
                                    <td style="padding:22px 26px;">
                                        <p style="margin:0 0 10px; color:#5f646d; font-size:18px; line-height:1.5; font-weight:700;">Catatan:</p>
                                        <p style="margin:0; color:#5f646d; font-size:18px; line-height:1.6;">Kode di atas hanya berlaku <strong style="font-weight:800;">selama {{ $expiresInMinutes }} menit.</strong>@if ($noteSuffix) {{ $noteSuffix }}@endif</p>
                                        <p style="margin:38px 0 0; color:#5f646d; font-size:18px; line-height:1.8;">Jangan bagikan kode ini kepada siapa pun demi menjaga keamanan akun. Hati-hati untuk tidak memberikan data penting Anda kepada pihak yang mengatasnamakan <strong style="font-weight:800;">Kresek.in</strong> atau yang tidak dapat dijamin keamanannya.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-top:28px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse;">
                                <tr>
                                    <td style="vertical-align:top; padding:0 24px 0 0;">
                                        <p style="margin:0; color:#5f646d; font-size:18px; line-height:1.6;">{{ $closingMessage }}</p>
                                    </td>
                                    <td class="download-block" align="right" style="vertical-align:top; width:240px;">
                                        <p style="margin:0 0 18px; color:#5f646d; font-size:18px; line-height:1.4;">Download aplikasi <span style="color:#00bdc8;">{{ $downloadLabel }}</span></p>
                                        @if ($playstoreUrl)
                                            <a href="{{ $playstoreUrl }}" style="display:inline-block; text-decoration:none;">
                                                <img src="{{ $playstoreBadgeUrl }}" width="166" height="48" alt="Get it on Google Play" style="display:block; width:166px; height:48px; border:0;">
                                            </a>
                                        @else
                                            <img src="{{ $playstoreBadgeUrl }}" width="166" height="48" alt="Get it on Google Play" style="display:inline-block; width:166px; height:48px; border:0;">
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="padding-top:18px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; background:#e7e7e7; border-radius:7px 7px 0 0;">
                    <tr>
                        <td align="center" style="padding:20px 24px;">
                            <p style="margin:0; color:#5f646d; font-size:18px; line-height:1.5;">Jika butuh bantuan, hubungi <a href="mailto:{{ $supportEmail }}" style="color:#00bdc8; text-decoration:none;">{{ $supportEmail }}</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
