<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesAgendaRecipients;
use App\Models\Notification;
use App\Services\NotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ── Agenda Notification (Fase 2) -- bagian RENCANA KUNJUNGAN SALES
 * (sales_visit_plans), reminder H-1 (sore/malam sebelum plan_date).
 * Dijadwalkan jalan sekali sehari sore/malam hari (lihat snippet
 * routes/console.php yang menyertai).
 *
 * sales_visit_plans TIDAK punya model Eloquent (lihat
 * SalesVisitPlanController -- semua query di sana pakai DB::table()
 * langsung) -- command ini mengikuti pola yang sama, pakai DB::table()
 * juga, BUKAN bikin model baru buat tabel ini.
 *
 * Cari baris yang:
 *   - status masih 'planned' (belum ditandai done/cancelled manual oleh
 *     sales)
 *   - plan_date jatuh BESOK (H-1 dari hari command ini jalan)
 *   - belum pernah dikirimi reminder ini sebelumnya (idempotent guard,
 *     sama persis pola & alasannya dengan ProcessFollowUpAgendaReminders
 *     -- lihat catatan lengkap soal reuse category='agenda' &
 *     "pinjam" kolom related_type/related_id/reminder_type di file itu,
 *     tidak diulang di sini)
 *
 * related_type diisi 'sales_visit_plans' (nama tabel, konsisten dengan
 * 'follow_ups' di 2 command sebelah). ──
 */
class ProcessVisitPlanAgendaReminders extends Command
{
    use ResolvesAgendaRecipients;

    protected $signature = 'notifications:process-visitplan-h1-reminders';

    protected $description = 'Kirim reminder H-1 untuk rencana kunjungan sales (sales_visit_plans) yang jadwalnya besok dan masih berstatus planned';

    // ── kolom notifications.reminder_type ternyata varchar(20) di DB
    // (ketahuan dari error "value too long" pas testing) -- dipendekin
    // jadi 'agenda_vp_h1', konsisten sama SUBTYPE di 2 command follow up
    // sebelah ('agenda_fu_h1'/'agenda_fu_overdue'). ──
    protected const SUBTYPE = 'agenda_vp_h1';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $tomorrowDate = now()->addDay()->toDateString();

        $plans = DB::table('sales_visit_plans')
            ->where('status', 'planned')
            ->whereDate('plan_date', $tomorrowDate)
            ->get();

        $sent = 0;

        foreach ($plans as $plan) {
            $alreadySent = Notification::where('category', 'agenda')
                ->where('related_type', 'sales_visit_plans')
                ->where('related_id', $plan->id)
                ->where('reminder_type', self::SUBTYPE)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $recipientIds = $this->resolveAgendaRecipients($plan->sales_id);

            if (empty($recipientIds)) {
                continue;
            }

            $planDateText = Carbon::parse($plan->plan_date)->format('d M Y');
            $title = $plan->title ?: 'rencana kunjungan ini';

            $notification = Notification::create([
                'category'      => 'agenda',
                'reminder_type' => self::SUBTYPE,
                'title'         => 'Reminder Rencana Kunjungan Besok',
                'message'       => "Besok ({$planDateText}) Anda memiliki rencana kunjungan ke {$title}. Jangan lupa ya!",
                'related_type'  => 'sales_visit_plans',
                'related_id'    => $plan->id,
                'target'        => 'specific',
                'recipient_ids' => $recipientIds,
                'status'        => 'sent',
                'created_by'    => $plan->sales_id,
            ]);

            $dispatcher->deliverNow($notification);
            $sent++;
        }

        $this->info($sent > 0
            ? "Reminder H-1 rencana kunjungan terkirim untuk {$sent} rencana."
            : 'Tidak ada rencana kunjungan yang perlu reminder H-1 hari ini.');

        return self::SUCCESS;
    }
}