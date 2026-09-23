<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * ── Kategori Product Catalog. Struktur pohon (self-referencing) lewat
 * parent_id. Tabel ini TIDAK pakai SoftDeletes (tidak ada kolom
 * deleted_at di migration-nya), beda dari Contact/ContactType. ──
 */
class CategoryProductCatalog extends Model
{
    protected $table = 'categories_product_catalog';

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(ProductCatalog::class, 'category_id');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where('name', 'ilike', "%{$search}%");
    }

    public function scopeSort(Builder $query, ?string $sortBy, ?string $sortDir): Builder
    {
        $sortBy = $sortBy ?: 'created_at';
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * ── Generate slug unik dari nama kategori. Kalau slug hasil
     * Str::slug() sudah dipakai (selain oleh baris $ignoreId sendiri,
     * dipakai waktu update), tambahkan suffix angka: nama-2, nama-3, dst. ──
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (
            self::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}