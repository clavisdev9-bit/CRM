<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class MsAccessSubmenu extends Model
{
  use HasFactory;

    protected $table = 'ms_access_submenu';
    protected $primaryKey = 'id_access_submenu';
    public $incrementing = true;
    public $timestamps = true;
     protected $fillable = [
        'id_user',
        'id_submenu',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'id_user', 'id_user');
    }


     public function submenu()
    {
        return $this->belongsTo(MsSubmenu::class, 'id_submenu', 'id_submenu');
    }

    
}
