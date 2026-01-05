<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsUsers extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'ms_users';
    protected $primaryKey = 'id_user';
    public    $incrementing = true;
    public    $timestamps = true;
    protected $fillable = [
        'fullname',
        'username',
        'email',
        'password',
        'role_id',
        'divisi_id',
        'group_id',
        'image',
        'email_verification_token',
        'email_verification_expires_at',
        'email_verified_at',
        'is_active',
    ];

        public function employee()
        {
            return $this->hasOne(MsEmployee::class, 'user_id', 'id_user');
        }


     public function role()
    {
        return $this->belongsTo(MsRole::class, 'role_id', 'id_role');
    }

     public function division()
    {
        return $this->belongsTo(MsDivision::class, 'divisi_id', 'id');
    }


      public function groups()
    {
        return $this->belongsTo(MsGroupCompany::class, 'group_id', 'id_group');
    }

// hanya untuk add
//     public static function isDuplicate(array $data): array
// {
//     $errors = [];

//     if (self::where('username', $data['username'])
//         ->whereNull('deleted_at')
//         ->exists()) {
//         $errors['username'] = 'Username sudah digunakan';
//     }

//     if (self::where('email', $data['email'])
//         ->whereNull('deleted_at')
//         ->exists()) {
//         $errors['email'] = 'Email sudah digunakan';
//     }

//     return $errors;
// }


public static function isDuplicate(array $data, $ignoreId = null): array
{
    $errors = [];

    if (isset($data['username'])) {
        $query = self::where('username', $data['username'])
            ->whereNull('deleted_at');

        if ($ignoreId) {
            $query->where('id_user', '!=', $ignoreId);
        }

        if ($query->exists()) {
            $errors['username'] = 'Username sudah digunakan';
        }
    }

    if (isset($data['email'])) {
        $query = self::where('email', $data['email'])
            ->whereNull('deleted_at');

        if ($ignoreId) {
            $query->where('id_user', '!=', $ignoreId);
        }

        if ($query->exists()) {
            $errors['email'] = 'Email sudah digunakan';
        }
    }

    return $errors;
}

     

    //cek apakah ada name  yang sama  untuk add
    public static function isUserExistsAdd($submenu)
    {
        return self::where('fullname', $submenu)->exists();
    }

    //cek apakah ada name submenu yang sama  untuk update
    public static function isUserExists($submenu, $excludeId = null)
    {
        return self::where('fullname', $submenu)
            ->where('id_user', '!=', $excludeId)
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
                $q->where('fullname', 'LIKE', "%{$search}%")
                ->orWhere('username', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhereHas('role', function ($role) use ($search) {
                    $role->where('role', 'LIKE', "%{$search}%");
                });
            });
        }



// Scope untuk sorting dinamis
public function scopeSort($query, $sortBy, $sortDir)
{
    return $query->orderBy($sortBy ?? 'created_at', $sortDir ?? 'asc');
}

}
