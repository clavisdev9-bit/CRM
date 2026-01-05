<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletes;

class AppSettingModel extends Model
{
     use HasFactory;
    // use SoftDeletes;
    protected $table = 'app_settings';
    protected $primaryKey = 'id';
    public    $incrementing = true;
    public    $timestamps = true;
     protected $fillable = [
        'app_name',
        'app_short_name',
        'app_tagline',
        'app_logo',
        'app_logo_small',
        'favicon',
        'primary_color',
        'secondary_color',
        'sidebar_color',
        'navbar_color',
        'footer_text',
        'footer_license_url',
        'footer_documentation_url',
        'footer_support_url',
        'version',
        'environment',
    ];

  

    //cek apakah ada name menu yang sama  untuk add
    public static function isSettingExistsAdd($name)
    {
        return self::where('app_short_name', $name)->exists();
    }

    //cek apakah ada name menu yang sama  untuk update
    public static function isSettingExists($name, $excludeId = null)
    {
        return self::where('app_short_name', $name)
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
            $q->where('app_short_name', 'like', "%{$search}%");
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

    $query = static::where('app_short_name', $data['app_short_name']);

    if ($id) {
        $query->where('id', '!=', $id); // Kecualikan ID yang sedang diupdate
    }

    if ($query->exists()) {
        $errors['app_short_name'] = ['Short Name App Already Exist.'];
    }

    return $errors;
}

// public static function isDuplicate(array $data): array
// {
//     $errors = [];

//     if (self::exists()) {
//         $errors['app_setting'] = ['App setting already exists'];
//     }

//     return $errors;
// }

}
