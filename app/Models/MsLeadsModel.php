<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletes;

class MsLeadsModel extends Model
{
     use HasFactory;
    //  use SoftDeletes;
    protected $table = 'leads';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
   protected $fillable = [
    'company_name',
    'contact_name',
    'email',
    'phone',

    'lead_category_id',
    'industry_id',

    'id_user',
    'assigned_to',
    'created_by',

    'visibility_type',
    'lead_source',
    'lead_status',

    'notes',
    'last_contacted_at',
    'converted_at',
];


//cek apakah ada name menu yang sama  untuk add
    public static function isNameExistsAdd($name)
    {
        return self::where('company_name', $name)->exists();
    }

    //cek apakah ada name menu yang sama  untuk update
    public static function isNameExists($name, $excludeId = null)
    {
        return self::where('company_name', $name)
            ->where('id', '!=', $excludeId)
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
            $q->where('company_name', 'like', "%{$search}%");
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

    $query = static::where('company_name', $data['company_name']);

    if ($id) {
        $query->where('id', '!=', $id); // Kecualikan ID yang sedang diupdate
    }

    if ($query->exists()) {
        $errors['company_name'] = ['Company Name Already Exist.'];
    }

    return $errors;
}

protected $casts = [
    'last_contacted_at' => 'datetime',
    'converted_at'      => 'datetime',
    'created_at'        => 'datetime',
    'updated_at'        => 'datetime',
];

}

