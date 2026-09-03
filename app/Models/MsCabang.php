<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsCabang extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'ms_cabang';
    protected $primaryKey = 'id_cabang';
    public    $incrementing = true;
    public    $timestamps = true;
     protected $fillable = [
        'cabang',
        'alamat',
        'no_telp'
    ];

    

    //cek apakah ada name cabang yang sama  untuk add
    public static function isCabangExistsAdd($cabang)
    {
        return self::where('cabang', $cabang)->exists();
    }

    //cek apakah ada name menu yang sama  untuk update
    public static function isCabangExists($cabang, $excludeId = null)
    {
        return self::where('cabang', $cabang)
            ->where('id_cabang', '!=', $excludeId)
            ->exists();
    }


      //opsional
    public function scopeOnlyDeleted(Builder $query, bool $only = false): Builder
    {
        return $only ? $query->onlyTrashed() : $query;
    }



public function scopeSearch($query, $search)
{
    if ($search) {
        return $query->where(function ($q) use ($search) {
            $q->where('cabang', 'like', "%{$search}%");
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

    $query = static::where('cabang', $data['cabang']);

    if ($id) {
        $query->where('id_cabang', '!=', $id); // Kecualikan ID yang sedang diupdate
    }

    if ($query->exists()) {
        $errors['cabang'] = ['Cabang Already Exist.'];
    }

    return $errors;
}
}
