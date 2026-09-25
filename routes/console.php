<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:process-due')->everyMinute();

// ── Email reminder follow up (fitur lama, follow-up:send-reminders) --
// sebelumnya belum terdaftar di sini sama sekali, jadi kemungkinan besar
// belum pernah jalan otomatis. Didaftarkan hourly sesuai deskripsi di
// command-nya sendiri ("dijadwalkan jalan TIAP JAM"). ──
Schedule::command('follow-up:send-reminders')->hourly();

// ── Agenda Notification (Fase 2) -- follow_ups (H-1 & overdue) dan
// sales_visit_plans (H-1). Lihat masing-masing command di
// app/Console/Commands/ untuk detail logic-nya. ──
Schedule::command('notifications:process-followup-h1-reminders')->dailyAt('07:00');
Schedule::command('notifications:process-followup-overdue')->hourly();
Schedule::command('notifications:process-visitplan-h1-reminders')->dailyAt('19:00');