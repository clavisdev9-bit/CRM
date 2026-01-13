<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletes;

class MsOffice extends Model
{
      use HasFactory;
    //  use SoftDeletes;
    protected $table = 'offices';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
     protected $fillable = [
        'id',
        'office_name',
        'latitude',
        'longitude',
        'radius',
        'is_active',
    ];

     public function employees()
    {
        return $this->hasMany(MsEmployee::class, 'office_id', 'id');
    }
}
