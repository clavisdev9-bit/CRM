<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsGroupCompany extends Model
{
 

     use HasFactory;
     use SoftDeletes;
    protected $table = 'group_companies';
    protected $primaryKey = 'id_group';
    public $incrementing = true;
    public $timestamps = true;
     protected $fillable = [
        'name_group',
        'description_group',
        'is_active',
    ];
}
