<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsLeadsIndustries extends Model
{
     use HasFactory;
     use SoftDeletes;
    protected $table = 'lead_industries';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
     protected $fillable = [
        'name',
        'is_active',
    ];
}
