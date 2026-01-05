<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsDivision extends Model
{
 

     use HasFactory;
     use SoftDeletes;
    protected $table = 'ms_division';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
     protected $fillable = [
        'name_division',
        'kode_division',
        'description_division',
    ];
}
