<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class MsAttendancePolicies extends Model
{
     use HasFactory;
    protected $table = 'attendance_policies';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
     protected $fillable = [
        'id',
        'policy_name',
        'applies_to',
        'office_id',
        'user_id',
        'min_accuracy',
        'max_accuracy',
        'allowed_radius',
        'is_active',
    ];
}
