<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class MsSubmenu extends Model
{
   use HasFactory;
    use SoftDeletes;
    protected $table = 'ms_submenu';
    protected $primaryKey = 'id_submenu';
    public    $incrementing = true;
    public    $timestamps = true;
     protected $fillable = [
        'id_menu',
        'url',
        'icon',
        'title',
        'noted',
        'is_active',
        'parent_id'
    ];

   public function menu()
    {
        return $this->belongsTo(MsMenu::class, 'id_menu', 'id_menu');
    }

    

        public function children()
        {
            return $this->hasMany(self::class, 'parent_id', 'id_submenu')
                        ->orderBy('title', 'asc');
        }


    //cek apakah ada name  yang sama  untuk add
    public static function isSubmenuExistsAdd($submenu)
    {
        return self::where('title', $submenu)->exists();
    }

    //cek apakah ada name submenu yang sama  untuk update
    public static function isSubmenuExists($submenu, $excludeId = null)
    {
        return self::where('title', $submenu)
            ->where('id_submenu', '!=', $excludeId)
            ->exists();
    }


     

            public function scopeOnlyDeleted($query, bool $onlyDeleted = false)
        {
            if ($onlyDeleted) {
                return $query->onlyTrashed();
            }

            return $query->whereNull('deleted_at');
        }




        public function scopeSearch($query, $search)
        {
            if (!$search) {
                return $query;
            }

            return $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                ->orWhere('url', 'LIKE', "%{$search}%")
                ->orWhereHas('menu', function ($menu) use ($search) {
                    $menu->where('menu', 'LIKE', "%{$search}%");
                });
            });
        }



// Scope untuk sorting dinamis
public function scopeSort($query, $sortBy, $sortDir)
{
    return $query->orderBy($sortBy ?? 'created_at', $sortDir ?? 'asc');
}


public static function isDuplicate(array $data, $id = null): array
{
    $errors = [];

    $query = static::where('title', $data['title']);

    if ($id) {
        $query->where('id_submenu', '!=', $id); // Kecualikan ID yang sedang diupdate
    }

    if ($query->exists()) {
        $errors['submenu'] = ['Name Submenu Already Exist.'];
    }
    return $errors;
}
}
