<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * ── Product Catalog (tabel products_catalog). Tidak pakai SoftDeletes. ──
 */
class ProductCatalog extends Model
{
    protected $table = 'products_catalog';

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'price',
        'stock',
        'thumbnail_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryProductCatalog::class, 'category_id');
    }

    public function catalogs()
    {
        return $this->hasMany(Catalog::class, 'product_id')->orderBy('sort_order');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
                ->orWhere('sku', 'ilike', "%{$search}%")
                ->orWhere('description', 'ilike', "%{$search}%");
        });
    }

    public function scopeByCategory(Builder $query, $categoryId): Builder
    {
        if (!$categoryId) {
            return $query;
        }

        return $query->where('category_id', $categoryId);
    }

    public function scopeSort(Builder $query, ?string $sortBy, ?string $sortDir): Builder
    {
        $sortBy = $sortBy ?: 'created_at';
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * ── Cek duplikasi SKU (case-insensitive, trimmed). $id diisi waktu
     * update supaya baris yang sedang diedit tidak dianggap duplikat
     * dirinya sendiri. ──
     */
    public static function isDuplicate(array $data, $id = null): bool
    {
        $sku = trim($data['sku'] ?? '');

        if ($sku === '') {
            return false;
        }

        return self::whereRaw('LOWER(sku) = ?', [strtolower($sku)])
            ->when($id, fn ($q) => $q->where('id', '!=', $id))
            ->exists();
    }
}