<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * ── Media (PDF/Video) milik sebuah Product Catalog. Tidak pakai
 * SoftDeletes. ──
 */
class Catalog extends Model
{
    protected $table = 'catalogs';

    public const MEDIA_TYPES = ['pdf', 'video'];
    public const SOURCE_TYPES = ['youtube', 'vimeo', 'upload', 'external_link'];

    protected $fillable = [
        'product_id',
        'media_type',
        'source_type',
        'url',
        'title',
        'sort_order',
    ];

    public function product()
    {
        return $this->belongsTo(ProductCatalog::class, 'product_id');
    }

    public function sendLogs()
    {
        return $this->hasMany(CatalogSendLog::class, 'catalog_id');
    }

    public function scopeSort(Builder $query, ?string $sortBy = null, ?string $sortDir = null): Builder
    {
        if ($sortBy) {
            return $query->orderBy($sortBy, strtolower($sortDir) === 'desc' ? 'desc' : 'asc');
        }

        return $query->orderBy('sort_order', 'asc');
    }
}