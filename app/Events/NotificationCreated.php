<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ── Di-fire tiap kali notifikasi (Bulk/Reminder, nanti Agenda) beneran
 * terkirim ke satu atau beberapa user -- dipakai frontend (lonceng
 * notifikasi) buat langsung nampilin toast + update badge count tanpa
 * refresh, lewat Laravel Reverb.
 *
 * broadcastOn() balikin 1 PrivateChannel PER USER penerima (bukan 1
 * channel umum) -- supaya user A tidak pernah bisa dengar notifikasi
 * yang ditujukan ke user B, walau keduanya online bersamaan. Otorisasi
 * channel `notifications.{id_user}` ada di routes/channels.php. ──
 */
class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;

    /** @var int[] */
    public array $userIds;

    public function __construct(Notification $notification, array $userIds)
    {
        $this->notification = $notification;
        $this->userIds = $userIds;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn ($userId) => new PrivateChannel('notifications.' . $userId),
            $this->userIds
        );
    }

    // ── nama event yang didengar di frontend lewat .listen('NotificationCreated', ...) ──
    public function broadcastAs(): string
    {
        return 'NotificationCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'            => $this->notification->id,
            'category'      => $this->notification->category,
            'reminder_type' => $this->notification->reminder_type,
            'title'         => $this->notification->title,
            'message'       => $this->notification->message,
            'related_type'  => $this->notification->related_type,
            'related_id'    => $this->notification->related_id,
            'created_at'    => now()->toDateTimeString(),
        ];
    }
}