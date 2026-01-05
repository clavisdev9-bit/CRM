<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletes;

class MsAccesMenu extends Model
{
    use HasFactory;
    //  use SoftDeletes;
    protected $table = 'ms_access_menu';
    protected $primaryKey = 'id_access_menu';
    public $incrementing = true;
    public $timestamps = true;
     protected $fillable = [
        'id_role',
        'id_menu',
    ];
}
