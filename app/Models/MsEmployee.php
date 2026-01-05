<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsEmployee extends Model
{
     use HasFactory;
    use SoftDeletes;
    protected $table = 'employees';
    protected $primaryKey = 'id_employee';
    public    $incrementing = true;
    public    $timestamps = true;
     protected $fillable = [
    'user_id',
    'nik',
    'tempat_lahir',
    'tanggal_lahir',
    'jenis_kelamin',
    'alamat',
    'no_hp',
    'tanggal_masuk',
    'status_karyawan',
];

    public function user()
    {
        return $this->belongsTo(MsUsers::class, 'user_id', 'id_user');
    }


    //cek apakah ada name menu yang sama  untuk add
    public static function isEmployeExistsAdd($emp)
    {
        return self::where('nik', $emp)->exists();
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
            $q->where('nik', 'like', "%{$search}%");
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

    // Cek hanya jika nik dikirim
    if (!array_key_exists('nik', $data) || empty($data['nik'])) {
        return $errors;
    }

    $query = static::where('nik', $data['nik']);

    // Exclude data yang sedang diupdate
    if ($id) {
        $query->where('id_employee', '!=', $id);
    }

    if ($query->exists()) {
        $errors['nik'][] = 'NIK employee already exists.';
    }

    return $errors;
}

}
