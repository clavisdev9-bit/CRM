<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use Illuminate\Support\Carbon;

/**
 * ── Logic pengiriman notifikasi (bikin baris notification_recipients +
 * broadcast event + update status/next_run_at), dipakai DUA tempat:
 *   1. NotificationController::store() -- untuk bulk yang dikirim
 *      langsung (tanpa scheduled_at).
 *   2. Console\Commands\ProcessDueNotifications -- scheduler yang jalan
 *      tiap menit, buat reminder yang next_run_at-nya jatuh tempo, dan
 *      bulk terjadwal yang scheduled_at-nya sudah lewat.
 * Ditaruh di sini (bukan diulang di Controller & Command) supaya kedua
 * tempat itu selalu konsisten. ──
 */
class NotificationDispatcher
{
    protected NotificationRecipient $NotificationRecipient;

    public function __construct(NotificationRecipient $NotificationRecipient)
    {
        $this->NotificationRecipient = $NotificationRecipient;
    }

    // ── Kirim notifikasi SEKARANG: bikin baris notification_recipients
    // untuk semua target, broadcast event real-time, update status. ──
    public function deliverNow(Notification $notification): void
    {
        $userIds = $notification->resolveRecipientIds();
        if (empty($userIds)) {
            return;
        }

        $now = now();
        $rows = array_map(fn ($userId) => [
            'notification_id' => $notification->id,
            'user_id'         => $userId,
            'is_read'         => false,
            'read_at'         => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $userIds);

        $this->NotificationRecipient->insert($rows);

        if ($notification->category === 'bulk') {
            $notification->status = 'sent';
        } else {
            // reminder: last_run_at diupdate, next_run_at dihitung ulang
            // (kecuali reminder_type=scheduled -- itu sekali jalan saja,
            // langsung di-nonaktifkan supaya tidak fire lagi)
            $notification->last_run_at = $now;
            if ($notification->reminder_type === 'scheduled') {
                $notification->status = 'inactive';
                $notification->next_run_at = null;
            } else {
                $notification->next_run_at = $this->computeNextRunAt($notification, $now);
            }
        }
        $notification->sent_at = $now;
        $notification->save();

        event(new NotificationCreated($notification, $userIds));
    }

    // ── Hitung next_run_at berikutnya berdasarkan reminder_type.
    // $from dipakai scheduler supaya perhitungan "kejar" ke depan dari
    // last_run_at, bukan dari now() (menghindari drift kalau server
    // sempat mati beberapa saat). ──
    public function computeNextRunAt(Notification $notification, ?Carbon $from = null): ?Carbon
    {
        if ($notification->category !== 'reminder' || !$notification->time_of_day) {
            if ($notification->reminder_type === 'scheduled') {
                return $notification->scheduled_at;
            }
            return null;
        }

        $from = $from ? $from->copy() : now();
        [$hour, $minute] = array_pad(explode(':', (string) $notification->time_of_day), 2, 0);

        switch ($notification->reminder_type) {
            case 'daily':
                $next = $from->copy()->setTime((int) $hour, (int) $minute, 0);
                if ($next->lessThanOrEqualTo($from)) {
                    $next->addDay();
                }
                return $next;

            case 'weekly':
                $next = $from->copy()->startOfWeek(Carbon::SUNDAY)
                    ->addDays((int) $notification->day_of_week)
                    ->setTime((int) $hour, (int) $minute, 0);
                if ($next->lessThanOrEqualTo($from)) {
                    $next->addWeek();
                }
                return $next;

            case 'monthly':
                $day = min((int) $notification->day_of_month, $from->copy()->endOfMonth()->day);
                $next = $from->copy()->startOfMonth()->addDays($day - 1)->setTime((int) $hour, (int) $minute, 0);
                if ($next->lessThanOrEqualTo($from)) {
                    $next = $next->addMonthNoOverflow();
                    $day = min((int) $notification->day_of_month, $next->copy()->endOfMonth()->day);
                    $next = $next->startOfMonth()->addDays($day - 1)->setTime((int) $hour, (int) $minute, 0);
                }
                return $next;

            case 'scheduled':
                return $notification->scheduled_at;

            default:
                return null;
        }
    }
}