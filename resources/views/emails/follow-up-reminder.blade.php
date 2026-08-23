<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reminder Follow Up</title>
<style>
  /* Notes-nya datang dari rich text editor (Vue) di frontend, jadi isinya
     HTML asli (<p>, <ul>, <strong>, dst) -- style ini cuma buat ngerapiin
     margin bawaan tag-tag itu supaya nggak lompat-lompat di dalam box. */
  .fu-notes p { margin: 0 0 8px; }
  .fu-notes p:last-child { margin-bottom: 0; }
  .fu-notes ul, .fu-notes ol { margin: 0 0 8px 18px; padding: 0; }
  .fu-notes strong { color: #1e293b; }
</style>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,0.08);">

          <!-- HEADER -->
          <tr>
            <td style="background-color:#6366f1; padding:20px 28px;">
              <span style="color:#ffffff; font-size:16px; font-weight:700;">&#128276; Reminder Follow Up</span>
            </td>
          </tr>

          <!-- BODY -->
          <tr>
            <td style="padding:28px;">
              <p style="margin:0 0 4px; font-size:14px; color:#1e293b;">
                Halo <strong>{{ $followUp['sales_name'] ?? 'Sales' }}</strong>,
              </p>
              <p style="margin:0 0 20px; font-size:14px; color:#64748b; line-height:1.6;">
                Follow up di bawah ini jatuh tempo dalam waktu dekat. Jangan sampai lupa ya!
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;">
                <tr>
                  <td style="padding:16px 18px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:6px 0; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#94a3b8; width:40%;">Nomor Follow Up</td>
                        <td style="padding:6px 0; font-size:14px; font-weight:700; color:#1e293b; text-align:right;">{{ $followUp['follow_up_code'] ?? '-' }}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#94a3b8;">No Ref</td>
                        <td style="padding:6px 0; font-size:14px; font-weight:700; color:#6366f1; text-align:right;">{{ $followUp['ref_code'] ?? '-' }}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#94a3b8;">Customer / Target</td>
                        <td style="padding:6px 0; font-size:14px; font-weight:700; color:#1e293b; text-align:right;">{{ $followUp['target_name'] ?? '-' }}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#94a3b8;">Tipe</td>
                        <td style="padding:6px 0; font-size:14px; color:#1e293b; text-align:right;">{{ $followUp['follow_up_type'] ?? '-' }}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#94a3b8;">Jatuh Tempo</td>
                        <td style="padding:6px 0; font-size:14px; font-weight:700; color:#dc2626; text-align:right;">{{ $followUp['follow_up_date'] ?? '-' }} {{ $followUp['follow_up_time'] ?? '' }}</td>
                      </tr>
                      @if(!empty($followUp['subject']))
                      <tr>
                        <td style="padding:6px 0; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#94a3b8; vertical-align:top;">Subjek</td>
                        <td style="padding:6px 0; font-size:14px; color:#1e293b; text-align:right;">{{ $followUp['subject'] }}</td>
                      </tr>
                      @endif
                      @if(!empty($followUp['notes']))
                      <tr>
                        <td colspan="2" style="padding:12px 0 0;">
                          <!-- $followUp['notes'] SENGAJA dirender pakai tanda seru ganda
                               (raw HTML, bukan interpolasi biasa yang otomatis di-escape)
                               karena isinya datang dari rich text editor di frontend Vue --
                               kalau di-escape, tag paragraf/bold/dst bakal keliatan literal
                               kayak sebelumnya, bukan ke-render. -->
                          <div class="fu-notes" style="background-color:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; font-size:13px; color:#64748b; line-height:1.5;">
                            {!! $followUp['notes'] !!}
                          </div>
                        </td>
                      </tr>
                      @endif
                    </table>
                  </td>
                </tr>
              </table>

              <p style="margin:20px 0 0; font-size:12px; color:#94a3b8; line-height:1.6;">
                Email ini dikirim otomatis oleh sistem CRM Duta Indomandiri karena follow up di atas sudah mendekati jatuh tempo. Silakan buka menu <strong>Follow Up</strong> di aplikasi untuk menindaklanjuti.
              </p>
            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td style="padding:16px 28px; background-color:#f8fafc; border-top:1px solid #e2e8f0;">
              <span style="font-size:11px; color:#94a3b8;">PT Duta Indomandiri &middot; CRM Clavis</span>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>