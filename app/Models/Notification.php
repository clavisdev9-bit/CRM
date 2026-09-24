<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * ── Notification (Bulk / Agenda / Reminder). Tidak pakai SoftDeletes --
 * mengikuti pola Catalog/ProductCatalog/CatalogSendLog di project ini,
 * karena notifikasi lebih ke "definisi/log", bukan data referensi
 * bersama seperti Contact. Reminder yang mau "dimatikan" tanpa dihapus
 * pakai status = inactive, bukan soft delete. ──
 */
class Notification extends Model
{
    protected $table = 'notifications';

    public const CATEGORIES = ['bulk', 'agenda', 'reminder'];

    public const REMINDER_TYPES = ['daily', 'weekly', 'monthly', 'scheduled'];

    public const REMINDER_SCOPES = ['personal', 'broadcast'];

    // ── status untuk category = bulk ──
    public const BULK_STATUSES = ['draft', 'scheduled', 'sent', 'failed'];

    // ── status untuk category = reminder (nyala/mati, BUKAN soal
    // "sudah terkirim" -- reminder memang berulang) ──
    public const REMINDER_STATUSES = ['active', 'inactive'];

    protected $fillable = [
        'category',
        'reminder_type',
        'reminder_scope',
        'title',
        'message',
        'related_type',
        'related_id',
        'target',
        'recipient_ids',
        'day_of_week',
        'day_of_month',
        'time_of_day',
        'next_run_at',
        'last_run_at',
        'status',
        'scheduled_at',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'next_run_at'   => 'datetime',
        'last_run_at'   => 'datetime',
        'scheduled_at'  => 'datetime',
        'sent_at'       => 'datetime',
        'day_of_week'   => 'integer',
        'day_of_month'  => 'integer',
        'recipient_ids' => 'array',
    ];

    // ── Resolve daftar id_user penerima SEKARANG (dipanggil tiap kali
    // notifikasi ini dikirim/reminder fire, bukan disimpan statis --
    // kecuali target='specific' yang memang statis dari recipient_ids). ──
    public function resolveRecipientIds(): array
    {
        if ($this->reminder_scope === 'personal') {
            return [$this->created_by];
        }

        if ($this->target === 'specific') {
            return $this->recipient_ids ?? [];
        }

        // target === 'all'
        return MsUsers::pluck('id_user')->all();
    }

    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id');
    }

    public function creator()
    {
        return $this->belongsTo(MsUsers::class, 'created_by', 'id_user');
    }

    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        if (!$category) {
            return $query;
        }
        return $query->where('category', $category);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'ilike', "%{$search}%")
                ->orWhere('message', 'ilike', "%{$search}%");
        });
    }

    public function scopeSort(Builder $query, ?string $sortBy = null, ?string $sortDir = null): Builder
    {
        $sortBy = $sortBy ?: 'created_at';
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir);
    }

    // ── Reminder yang sudah waktunya dieksekusi (dipakai scheduler
    // command ProcessReminderNotifications) ──
    public function scopeDueReminders(Builder $query): Builder
    {
        return $query->where('category', 'reminder')
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }
}