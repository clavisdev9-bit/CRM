<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ── Untuk sisi Admin/Manager: daftar notifikasi yang PERNAH DIBUAT
 * (Bulk & Reminder), bukan sisi penerima. Lihat NotificationRecipientResources
 * untuk sisi penerima ("notifikasi saya"). ──
 */
class NotificationResources extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'category'        => $this->category,
            'reminder_type'   => $this->reminder_type,
            'reminder_scope'  => $this->reminder_scope,
            // ── FIX: field ini sebelumnya tidak ada sama sekali di sini,
            // padahal sudah ke-save di kolom `target` (lihat store() di
            // NotificationController -- 'target' => $data['target'] ?? null).
            // Akibatnya kolom TARGET di tabel "Kelola Notifikasi" selalu
            // tampil '-' karena item.target selalu undefined di frontend. ──
            'target'          => $this->target,
            'title'           => $this->title,
            'message'         => $this->message,
            'day_of_week'     => $this->day_of_week,
            'day_of_month'    => $this->day_of_month,
            'time_of_day'     => $this->time_of_day,
            'next_run_at'     => $this->next_run_at?->toDateTimeString(),
            'last_run_at'     => $this->last_run_at?->toDateTimeString(),
            'status'          => $this->status,
            'scheduled_at'    => $this->scheduled_at?->toDateTimeString(),
            'sent_at'         => $this->sent_at?->toDateTimeString(),
            'created_by'      => $this->created_by,
            'created_by_name' => $this->whenLoaded('creator', fn () => $this->creator?->fullname),
            'recipients_count' => $this->whenCounted('recipients'),
            'created_at'      => $this->created_at?->toDateTimeString(),
            'updated_at'      => $this->updated_at?->toDateTimeString(),
        ];
    }
}