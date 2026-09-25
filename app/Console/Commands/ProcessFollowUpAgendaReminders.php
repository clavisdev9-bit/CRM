<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesAgendaRecipients;
use App\Models\MsFollowUp;
use App\Models\Notification;
use App\Services\NotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ── Agenda Notification (Fase 2) -- bagian FOLLOW UP, reminder H-1.
 * Dijadwalkan jalan sekali sehari pagi hari (lihat jadwalnya di snippet
 * routes/console.php yang menyertai), cari follow_ups yang:
 *   - status masih PENDING (belum dikerjakan/belum diselesaikan)
 *   - follow_up_at jatuh BESOK (H-1 dari hari command ini jalan)
 *   - belum pernah dikirimi reminder H-1 ini sebelumnya (idempotent
 *     guard lewat kombinasi related_type/related_id/reminder_type --
 *     command ini aman dijalankan berkali-kali/manual, tidak akan
 *     dobel kirim reminder yang sama)
 *
 * Notifikasinya numpang PENUH di infrastruktur category='agenda' yang
 * sudah diantisipasi sejak Fase 1 (lihat Notification::CATEGORIES) --
 * dibuat sebagai baris Notification (target='specific', recipient_ids =
 * sales pemilik + manager-nya lewat trait ResolvesAgendaRecipients),
 * lalu langsung dikirim (bukan dijadwalkan ulang) lewat
 * NotificationDispatcher::deliverNow() yang SUDAH ADA dari Fase 1 --
 * TIDAK ada logic pengiriman/broadcast baru yang ditulis ulang di sini.
 *
 * CATATAN kolom yang "dipinjam":
 * - related_type/related_id diisi string nama TABEL ('follow_ups' + id
 *   baris-nya), BUKAN FQCN model -- disamakan dengan sales_visit_plans
 *   di command sebelah yang memang tidak punya model Eloquent, supaya
 *   ke-2 sumber Agenda ini konsisten cara penandaannya.
 * - reminder_type di sini TIDAK dipakai sesuai arti aslinya (siklus
 *   daily/weekly/monthly/scheduled, yang cuma relevan buat
 *   category='reminder') -- dipakai murni sebagai PENANDA SUBTYPE
 *   notifikasi agenda ini, supaya query "sudah pernah dikirim belum"
 *   gampang & idempotent. Aman dipakai begini karena:
 *     a) Notification::scopeDueReminders() cuma menyaring
 *        category='reminder', jadi baris category='agenda' dengan
 *        reminder_type apapun TIDAK PERNAH kepilih di scope itu.
 *     b) Baris ini dibuat langsung lewat Notification::create() di
 *        command (bukan lewat NotificationValidationRequest yang
 *        membatasi reminder_type ke Notification::REMINDER_TYPES),
 *        jadi tidak kena validasi in: itu sama sekali.
 * ──
 */
class ProcessFollowUpAgendaReminders extends Command
{
    use ResolvesAgendaRecipients;

    protected $signature = 'notifications:process-followup-h1-reminders';

    protected $description = 'Kirim reminder H-1 untuk follow up (leads & customers) yang jadwalnya besok dan masih berstatus PENDING';

    // ── kolom notifications.reminder_type ternyata varchar(20) di DB
    // (ketahuan dari error "value too long" pas testing) -- SUBTYPE di
    // ke-3 command Agenda harus MUAT di bawah 20 karakter. Dipendekin
    // jadi 'agenda_fu_*'/'agenda_vp_*' (bukan 'agenda_followup_*'/
    // 'agenda_visitplan_*' yang kepanjangan). ──
    protected const SUBTYPE = 'agenda_fu_h1';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $tomorrowStart = now()->addDay()->startOfDay();
        $tomorrowEnd = now()->addDay()->endOfDay();

        // ── MsFollowUp pakai SoftDeletes -- baris yang deleted_at-nya
        // terisi otomatis ke-exclude oleh Eloquent, tidak perlu
        // whereNull('deleted_at') manual di sini. ──
        $followUps = MsFollowUp::query()
            ->where('status', 'PENDING')
            ->whereBetween('follow_up_at', [$tomorrowStart, $tomorrowEnd])
            ->get();

        $sent = 0;

        foreach ($followUps as $followUp) {
            $alreadySent = Notification::where('category', 'agenda')
                ->where('related_type', 'follow_ups')
                ->where('related_id', $followUp->id)
                ->where('reminder_type', self::SUBTYPE)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $salesId = $followUp->created_by;
            $recipientIds = $this->resolveAgendaRecipients($salesId);

            if (empty($recipientIds)) {
                continue;
            }

            $targetName = $this->resolveFollowUpTargetName($followUp);
            $scheduleText = Carbon::parse($followUp->follow_up_at)->format('d M Y H:i');

            $notification = Notification::create([
                'category'      => 'agenda',
                'reminder_type' => self::SUBTYPE,
                'title'         => 'Reminder Follow Up Besok',
                'message'       => "Follow up ke {$targetName} dijadwalkan besok, {$scheduleText}. Jangan lupa ya!",
                'related_type'  => 'follow_ups',
                'related_id'    => $followUp->id,
                'target'        => 'specific',
                'recipient_ids' => $recipientIds,
                'status'        => 'sent',
                'created_by'    => $salesId,
            ]);

            $dispatcher->deliverNow($notification);
            $sent++;
        }

        $this->info($sent > 0
            ? "Reminder H-1 follow up terkirim untuk {$sent} follow up."
            : 'Tidak ada follow up yang perlu reminder H-1 hari ini.');

        return self::SUCCESS;
    }

    // ── follow_ups.lead_id XOR customer_id (CHECK constraint di
    // migration) -- pakai nama company yang relevan, fallback ke
    // subject kalau nama company-nya kosong. ──
    private function resolveFollowUpTargetName(MsFollowUp $followUp): string
    {
        if ($followUp->lead_id) {
            $name = DB::table('leads')->where('id', $followUp->lead_id)->value('company_name');
            return $name ?: 'lead ini';
        }

        if ($followUp->customer_id) {
            $name = DB::table('customers')->where('id', $followUp->customer_id)->value('company_name');
            return $name ?: 'customer ini';
        }

        return $followUp->subject ?: 'follow up ini';
    }
}