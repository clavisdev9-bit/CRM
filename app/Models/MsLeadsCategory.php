<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsLeadsCategory extends Model
{
     use HasFactory;
     use SoftDeletes;
    protected $table = 'lead_categories';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
     protected $fillable = [
        'name',
        'description',
        'is_active',
    ];
}

