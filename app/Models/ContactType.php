<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactType extends Model
{

    use HasFactory;
    use SoftDeletes;

    protected $table = 'contact_types';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        // ── True = jenis reserved yang dipasang OTOMATIS oleh sistem saat
        // sebuah contact di-link dari Customer/Lead/Customer Contact/
        // Branch Contact -- tidak boleh dihapus/diedit dari UI (dicek di
        // ContactTypeController, bukan lewat DB constraint, supaya
        // errornya lebih ramah). ──
        'is_system',
        // ── Hanya diisi untuk is_system = true, menunjuk ke salah satu
        // Contact::SOURCE_TYPES. Dipakai ContactController::storeLink()
        // untuk cari contact_type_id secara deterministic tanpa
        // hardcode/cocokkan 'name'. ──
        'system_source_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

     //opsional
    public function scopeOnlyDeleted(Builder $query, bool $only = false): Builder
    {
        return $only ? $query->onlyTrashed() : $query;
    }



public function scopeSearch($query, $search)
{
    if ($search) {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    }
    return $query;
}



// Scope untuk sorting dinamis
public function scopeSort($query, $sortBy, $sortDir)
{
    return $query->orderBy($sortBy ?? 'created_at', $sortDir ?? 'asc');
}

public static function isDuplicate(array $data, $id = null): array
{
    $errors = [];

    $query = static::where('name', $data['name']);

    if ($id) {
        $query->where('id', '!=', $id); // Kecualikan ID yang sedang diupdate
    }

    if ($query->exists()) {
        $errors['name'] = ['Contact Type Already Exist.'];
    }

    return $errors;
}

    /**
     * Semua contact (standalone maupun linked) yang memakai jenis ini.
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class, 'contact_type_id');
    }

    /**
     * Cari jenis reserved (is_system = true) yang berpasangan dengan
     * source_type tertentu ('customer' | 'lead' | 'customer_contact' |
     * 'branch_contact'). Dipakai ContactController::storeLink() untuk
     * auto-assign contact_type_id saat proses link, tanpa hardcode.
     */
    public static function findBySourceType(string $sourceType): ?self
    {
        return static::where('system_source_type', $sourceType)
            ->where('is_system', true)
            ->first();
    }
}