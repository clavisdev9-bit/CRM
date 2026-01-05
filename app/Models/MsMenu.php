<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsMenu extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'ms_menu';
    protected $primaryKey = 'id_menu';
    public    $incrementing = true;
    public    $timestamps = true;
     protected $fillable = [
        'menu'
    ];

    public function submenus()
    {
        return $this->hasMany(MsSubmenu::class, 'id_menu', 'id_menu');
    }

    //cek apakah ada name menu yang sama  untuk add
    public static function isMenuExistsAdd($menu)
    {
        return self::where('menu', $menu)->exists();
    }

    //cek apakah ada name menu yang sama  untuk update
    public static function isMenuExists($menu, $excludeId = null)
    {
        return self::where('menu', $menu)
            ->where('id_menu', '!=', $excludeId)
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
            $q->where('menu', 'like', "%{$search}%");
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

    $query = static::where('menu', $data['menu']);

    if ($id) {
        $query->where('id_menu', '!=', $id); // Kecualikan ID yang sedang diupdate
    }

    if ($query->exists()) {
        $errors['menu'] = ['Name Menu Already Exist.'];
    }

    return $errors;
}
}
