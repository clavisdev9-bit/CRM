<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ── Sisi PENERIMA ("notifikasi saya") -- dipakai lonceng notifikasi &
 * Notification Center per-user. Meratakan (flatten) isi notification
 * induk + status baca baris recipient ini, supaya frontend tidak perlu
 * nested object notification.title dst. ──
 */
class NotificationRecipientResources extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'notification_id' => $this->notification_id,
            'category'        => $this->notification?->category,
            'reminder_type'   => $this->notification?->reminder_type,
            'title'           => $this->notification?->title,
            'message'         => $this->notification?->message,
            'related_type'    => $this->notification?->related_type,
            'related_id'      => $this->notification?->related_id,
            'is_read'         => (bool) $this->is_read,
            'read_at'         => $this->read_at?->toDateTimeString(),
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }
}