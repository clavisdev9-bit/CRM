<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Command;

/**
 * ── Scheduler command, jalan tiap menit (didaftarkan di routes/console.php
 * atau bootstrap/app.php->withSchedule(), lihat catatan setup) --
 * memproses 2 hal:
 *
 * 1. Reminder (daily/weekly/monthly/scheduled) yang next_run_at-nya
 *    sudah jatuh tempo -> kirim (bikin notification_recipients +
 *    broadcast), lalu hitung next_run_at berikutnya.
 * 2. Bulk notification berstatus 'scheduled' yang scheduled_at-nya
 *    sudah lewat -> kirim sekarang.
 *
 * WAJIB server sudah punya cron `* * * * * php artisan schedule:run`
 * aktif -- tanpa itu command ini TIDAK PERNAH jalan otomatis. ──
 */
class ProcessDueNotifications extends Command
{
    protected $signature = 'notifications:process-due';

    protected $description = 'Kirim reminder yang jatuh tempo & bulk notification terjadwal yang waktunya sudah lewat';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $dueReminders = Notification::dueReminders()->get();
        foreach ($dueReminders as $reminder) {
            $dispatcher->deliverNow($reminder);
            $this->info("Reminder #{$reminder->id} \"{$reminder->title}\" terkirim.");
        }

        $dueBulks = Notification::where('category', 'bulk')
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();
        foreach ($dueBulks as $bulk) {
            $dispatcher->deliverNow($bulk);
            $this->info("Bulk notification #{$bulk->id} \"{$bulk->title}\" terkirim.");
        }

        $total = $dueReminders->count() + $dueBulks->count();
        if ($total === 0) {
            $this->info('Tidak ada notifikasi yang jatuh tempo.');
        }

        return self::SUCCESS;
    }
}