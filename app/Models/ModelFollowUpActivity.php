<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelFollowUpActivity extends Model
{
     protected $fillable = [
        'follow_up_id',
        'activity_type',
        'title',
        'description',
        'activity_at',
        'created_by',
    ];

    public function followUp()
    {
        return $this->belongsTo(MsFollowUp::class);
    }
}
