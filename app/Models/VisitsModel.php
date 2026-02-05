<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VisitsModel extends Model
{
      use HasFactory;
     use SoftDeletes;
    protected $table = 'visits';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
   protected $fillable = [
        'visit_code',
        'lead_id',
        'customer_id',
        'sales_id',
        'visit_at',
        'check_in_at',
        'check_out_at',
        'latitude',
        'longitude',
        'gps_snapshot',
        'photo',
        'notes',
        'visit_result',
        'customer_response',
        'created_by',
    ];

    /**
     * Atribut yang harus dikonversi ke tipe data tertentu (Casting).
     * Memudahkan manipulasi tanggal/waktu.
     */
    protected $casts = [
        'visit_at' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
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
            $q->where('visit_code', 'like', "%{$search}%");
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

    $query = static::where('visit_code', $data['visit_code']);

    if ($id) {
        $query->where('id', '!=', $id); // Kecualikan ID yang sedang diupdate
    }

    if ($query->exists()) {
        $errors['visit_code'] = ['Code Visit Already Exist.'];
    }

    return $errors;
}




public static function generateVisitCode()
{
    $prefix = 'VIS-' . now()->format('Ym') . '-';

    $lastVisit = self::where('visit_code', 'like', $prefix . '%')
        ->orderBy('visit_code', 'desc')
        ->first();

    if ($lastVisit) {
        $lastNumber = intval(substr($lastVisit->visit_code, -5));
        $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
    } else {
        $nextNumber = '00001';
    }

    return $prefix . $nextNumber;
}

}
