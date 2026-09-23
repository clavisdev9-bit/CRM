<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Catalog Produk</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f7; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#6366f1; padding:20px 32px;">
                            <span style="color:#ffffff; font-size:16px; font-weight:bold; letter-spacing:0.5px;">CRM CLAVIS SYSTEM</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin:0 0 8px; color:#111827; font-size:20px;">{{ $product->name }}</h2>

                            @if($catalog && $catalog->title)
                                <p style="margin:0 0 16px; color:#6b7280; font-size:14px;">{{ $catalog->title }}</p>
                            @endif

                            @if($product->description)
                                <p style="margin:0 0 20px; color:#374151; font-size:14px; line-height:1.6;">
                                    {{ $product->description }}
                                </p>
                            @endif

                            @if($catalog && $catalog->source_type === 'upload')
                                <p style="margin:0 0 20px; color:#374151; font-size:14px; line-height:1.6;">
                                    File catalog (PDF) terlampir pada email ini.
                                </p>
                            @elseif($catalog)
                                <table role="presentation" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="border-radius:6px; background-color:#6366f1;">
                                            <a href="{{ $catalog->url }}" target="_blank" style="display:inline-block; padding:12px 24px; color:#ffffff; font-size:14px; text-decoration:none; font-weight:bold;">
                                                Lihat Catalog
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <p style="margin:28px 0 0; color:#9ca3af; font-size:12px; line-height:1.5;">
                                Email ini dikirim otomatis oleh CRM CLAVIS SYSTEM atas permintaan tim Sales kami.
                                Jika Anda merasa tidak seharusnya menerima email ini, mohon abaikan.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>