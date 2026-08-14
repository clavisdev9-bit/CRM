<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class Users extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use Notifiable;

    protected $table = 'ms_users';
    protected $primaryKey = 'id_user'; 
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'fullname',
        'username',
        'email',
        'password',
        'role_id',
        'image',
        'email_verification_token',
        'email_verification_expires_at',
        'email_verified_at',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $dates = [
        'email_verified_at',
        'email_verification_expires_at',
    ];

    // JWT identifier menggunakan id_user
    public function getJWTIdentifier()
    {
        return $this->id_user;
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // app/Models/User.php
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
}
