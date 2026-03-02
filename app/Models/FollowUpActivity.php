<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUpActivity extends Model
{
    protected $table = 'follow_up_activities';

    protected $fillable = [
        'follow_up_id',
        'activity_type',
        'title',
        'description',
        'activity_at',
        'scheduled_for',
        'created_by',
    ];
}