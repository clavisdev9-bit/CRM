<?php

namespace App\Console\Commands;

use App\Mail\FollowUpReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ==========================================================================
 * REMINDER EMAIL FOLLOW UP -- follow-up:send-reminders
 * --------------------------------------------------------------------------
 * Follow up yang PENDING dan tinggal <= 12 jam lagi sampai follow_up_at,
 * otomatis dikirimin email reminder ke sales yang pegang (assigned_to,
 * fallback ke created_by kalau assigned_to kosong) -- TANPA CC ke siapapun,
 * cuma ke sales yang bersangkutan. Command ini dijadwalkan jalan TIAP JAM
 * lewat scheduler Laravel (lihat instruksi setup di percakapan -- saya
 * nggak punya akses ke Kernel.php/bootstrap/app.php project ini).
 *
 * Sengaja pakai kolom penanda `reminder_sent_at` (bukan cuma ngecek jendela
 * waktu doang) supaya sales CUMA di-email SEKALI per follow-up, walau
 * command ini jalan berkali-kali (tiap jam) selama window 12 jam itu masih
 * berlangsung. Follow-up yang jendelanya udah lewat pas pertama kali
 * ke-detect (misal dibuat mendadak, sisa 3 jam lagi) TETAP langsung dapat
 * reminder di run berikutnya -- bukan dilewat.
 *
 * PENTING kalau follow_up_at di-reschedule (mundur/maju) lewat controller
 * yang sudah ada: reminder_sent_at yang sudah terisi HARUS di-reset manual
 * jadi NULL lagi di situ, supaya command ini bisa ngirim reminder baru buat
 * jadwal barunya. Saya nggak sentuh controller follow_ups yang sudah ada
 * (soalnya scattered di beberapa file/tidak ada file FollowUpController
 * terpisah) -- tolong tambahkan baris ini secara manual di titik mana pun
 * follow_ups.follow_up_at di-update:
 *   DB::table('follow_ups')->where('id', $id)->update([
 *       'follow_up_at'      => $newDate,
 *       'reminder_sent_at'  => null,
 *   ]);
 *
 * ASUMSI kolom email di ms_users bernama `email` -- tinggal ganti semua
 * referensi `.email` di bawah kalau ternyata nama kolomnya beda.
 *
 * ASUMSI follow_ups TIDAK punya kolom branch_id (sesuai migration yang kamu
 * kasih ke saya: cuma ada lead_id/customer_id dengan XOR constraint
 * chk_followups_owner) -- kalau ternyata project kamu sudah nambah
 * branch_id lewat migration lain, tambahkan lagi join ke customer_branches
 * di dueFollowUps() di bawah.
 *
 * NO REF -- sama persis pola "ref_code" yang sudah dipakai di
 * SalesActivityDashboardController (buildActivitiesUnion/buildFollowUpReminders):
 *   - Kalau follow_ups.visit_id TERISI (follow-up ini auto-generated dari
 *     sebuah visit) -> No Ref-nya ambil dari VISIT yang bersangkutan:
 *     visits.no_reference (nomor ERP manual sales pas check-out), fallback
 *     ke visits.visit_code kalau belum diisi.
 *   - Kalau follow_ups.visit_id KOSONG (follow-up dibuat langsung/manual,
 *     bukan dari visit) -> No Ref-nya ambil dari follow_ups.no_reference
 *     sendiri (kolom ini ditambahin lewat migration terpisah, ALTER TABLE
 *     after follow_up_code), fallback ke follow_ups.follow_up_code.
 * ==========================================================================
 */
class SendFollowUpReminders extends Command
{
    protected $signature = 'follow-up:send-reminders';

    protected $description = 'Kirim email reminder untuk follow up PENDING yang jatuh tempo dalam <= 12 jam ke depan (sekali per follow-up).';

    public function handle(): int
    {
        $dueRows = $this->dueFollowUps();

        if ($dueRows->isEmpty()) {
            $this->info('Tidak ada follow up yang perlu diingatkan saat ini.');
            return self::SUCCESS;
        }

        $sent    = 0;
        $skipped = 0;

        foreach ($dueRows as $row) {
            if (empty($row->sales_email)) {
                // sales-nya (assigned_to/created_by) nggak punya email -- dilewati,
                // tapi TETAP ditandai reminder_sent_at supaya command ini nggak
                // coba-coba lagi tiap jam buat baris yang sama.
                DB::table('follow_ups')->where('id', $row->id)->update(['reminder_sent_at' => now()]);
                $skipped++;
                $this->warn("Follow up #{$row->follow_up_code}: sales tidak punya email, dilewati.");
                continue;
            }

            try {
                Mail::to($row->sales_email)
                    ->send(new FollowUpReminderMail((array) $row));

                DB::table('follow_ups')->where('id', $row->id)->update(['reminder_sent_at' => now()]);
                $sent++;

            } catch (\Throwable $e) {
                // sengaja TIDAK di-update reminder_sent_at kalau gagal kirim --
                // biar dicoba lagi di run berikutnya (misal SMTP lagi down).
                Log::error("Gagal kirim reminder follow up #{$row->follow_up_code}: {$e->getMessage()}");
                $this->error("Follow up #{$row->follow_up_code}: gagal kirim email -- {$e->getMessage()}");
            }
        }

        $this->info("Selesai. Terkirim: {$sent}, dilewati (sales tanpa email): {$skipped}, total kandidat: {$dueRows->count()}.");

        return self::SUCCESS;
    }

    /**
     * Follow up PENDING yang follow_up_at-nya <= 12 jam lagi DAN belum lewat
     * jatuh tempo (yang sudah lewat sudah kelihatan jelas lewat card merah
     * "Overdue" di UI, jadi nggak perlu di-email lagi) DAN belum pernah
     * diingatkan (reminder_sent_at masih NULL).
     */
    private function dueFollowUps()
    {
        return DB::table('follow_ups as fu')
            ->select([
                'fu.id',
                'fu.follow_up_code',
                'fu.follow_up_type',
                'fu.subject',
                'fu.notes',
                DB::raw("TO_CHAR(fu.follow_up_at, 'YYYY-MM-DD') as follow_up_date"),
                DB::raw("TO_CHAR(fu.follow_up_at, 'HH24:MI') as follow_up_time"),
                DB::raw('COALESCE(assigned_sales.fullname, creator_sales.fullname) as sales_name'),
                DB::raw('COALESCE(assigned_sales.email, creator_sales.email) as sales_email'),
                DB::raw('COALESCE(c.company_name, l.company_name) as target_name'),
                // No Ref: dari visit kalau follow-up ini auto-generated dari visit
                // (fu.visit_id terisi), atau dari follow_ups.no_reference sendiri
                // kalau ini follow-up manual -- lihat catatan lengkap di atas class.
                DB::raw("
                    CASE
                        WHEN fu.visit_id IS NOT NULL THEN COALESCE(v.no_reference, v.visit_code)
                        ELSE COALESCE(fu.no_reference, fu.follow_up_code)
                    END as ref_code
                "),
            ])
            ->leftJoin('ms_users as assigned_sales', 'assigned_sales.id_user', '=', 'fu.assigned_to')
            ->leftJoin('ms_users as creator_sales', 'creator_sales.id_user', '=', 'fu.created_by')
            ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
            ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
            ->leftJoin('visits as v', 'v.id', '=', 'fu.visit_id')
            ->whereNull('fu.deleted_at')
            ->where('fu.status', 'PENDING')
            ->whereNull('fu.reminder_sent_at')
            ->where('fu.follow_up_at', '>', now())
            ->where('fu.follow_up_at', '<=', now()->addHours(12))
            ->orderBy('fu.follow_up_at')
            ->get();
    }
}