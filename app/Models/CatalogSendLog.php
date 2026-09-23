<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * ── Log pengiriman Catalog (audit trail), via email ATAU whatsapp.
 * Tidak pakai SoftDeletes -- log tidak pernah dihapus. ──
 */
class CatalogSendLog extends Model
{
    protected $table = 'catalog_send_logs';

    public const CHANNELS = ['email', 'whatsapp'];
    public const STATUSES = ['sent', 'failed'];

    protected $fillable = [
        'catalog_id',
        'product_id',
        'sender_id',
        'sender_name',
        'sender_email',
        'recipient_name',
        'recipient_email',
        'recipient_phone',
        'product_name',
        'catalog_title',
        'channel',
        'status',
        'error_message',
    ];

    public function catalog()
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductCatalog::class, 'product_id');
    }

    public function sender()
    {
        return $this->belongsTo(MsUsers::class, 'sender_id', 'id_user');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('recipient_name', 'ilike', "%{$search}%")
                ->orWhere('recipient_email', 'ilike', "%{$search}%")
                ->orWhere('recipient_phone', 'ilike', "%{$search}%")
                ->orWhere('product_name', 'ilike', "%{$search}%")
                ->orWhere('catalog_title', 'ilike', "%{$search}%");
        });
    }

    public function scopeSort(Builder $query, ?string $sortBy, ?string $sortDir): Builder
    {
        $sortBy = $sortBy ?: 'created_at';
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir);
    }
}