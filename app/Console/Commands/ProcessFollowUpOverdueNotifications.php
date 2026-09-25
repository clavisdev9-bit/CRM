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
 * ── Agenda Notification (Fase 2) -- bagian FOLLOW UP, notifikasi
 * OVERDUE. Dijadwalkan jalan tiap jam (lihat snippet routes/console.php
 * yang menyertai) -- lebih sering dari reminder H-1 karena "overdue"
 * butuh dideteksi cepat begitu follow_up_at-nya kelewat, bukan cuma
 * sekali di pagi hari.
 *
 * Kondisi yang dicari SAMA PERSIS dengan logic $overdueCondition yang
 * sudah dipakai berulang kali di FollowUp controller (followUpSalesByLeads,
 * followUpSalesByCustomers, getLeadsNeedFollowUp): follow_up_at sudah
 * lewat DAN status masih PENDING. Di sini logic itu dipakai buat TRIGGER
 * notifikasi (bukan cuma computed column tampilan seperti di controller).
 *
 * Sama seperti ProcessFollowUpAgendaReminders (H-1) -- lihat catatan
 * lengkap soal reuse category='agenda' & "pinjam" kolom
 * related_type/related_id/reminder_type di file itu, tidak diulang di
 * sini. Bedanya cuma subtype penandanya (SUBTYPE) dan kondisi query-nya. ──
 */
class ProcessFollowUpOverdueNotifications extends Command
{
    use ResolvesAgendaRecipients;

    protected $signature = 'notifications:process-followup-overdue';

    protected $description = 'Kirim notifikasi untuk follow up (leads & customers) yang sudah lewat jadwal (follow_up_at) dan masih berstatus PENDING';

    // ── kolom notifications.reminder_type ternyata varchar(20) di DB
    // (ketahuan dari error "value too long" pas testing -- nilai lama
    // 'agenda_followup_overdue' = 23 karakter, kepanjangan). Dipendekin
    // jadi 'agenda_fu_overdue' (17 karakter), konsisten sama SUBTYPE di
    // ProcessFollowUpAgendaReminders. ──
    protected const SUBTYPE = 'agenda_fu_overdue';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $followUps = MsFollowUp::query()
            ->where('status', 'PENDING')
            ->where('follow_up_at', '<', now())
            ->get();

        $sent = 0;

        foreach ($followUps as $followUp) {
            // ── idempotent guard -- SEKALI overdue notification per
            // follow up saja (tidak diulang tiap jam selama dia masih
            // PENDING & overdue). Kalau follow up-nya di-update jadwal
            // barunya (jadi tidak overdue lagi) lalu telat LAGI, ini
            // otomatis dianggap follow up "baru" karena related_id-nya
            // sama tapi... CATATAN: kalau butuh notif ulang per
            // keterlambatan baru (bukan cuma sekali seumur hidup baris
            // ini), tinggal tambahkan filter created_at ke query exists
            // di bawah -- untuk versi awal ini sengaja dibuat sekali
            // saja per baris follow_up supaya tidak spam. ──
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
                'title'         => 'Follow Up Terlambat',
                'message'       => "Follow up ke {$targetName} sudah melewati jadwal ({$scheduleText}) dan masih berstatus Pending. Segera ditindaklanjuti ya!",
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
            ? "Notifikasi overdue terkirim untuk {$sent} follow up."
            : 'Tidak ada follow up overdue baru saat ini.');

        return self::SUCCESS;
    }

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