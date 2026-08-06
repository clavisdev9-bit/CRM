<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsFollowUp extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'follow_ups';
    protected $primaryKey = 'id';
    public    $incrementing = true;
    public    $timestamps = true;

        protected $fillable = [
            'lead_id',
            'customer_id',
            'branch_id',  
            'visit_id',      
            'follow_up_type',
            'subject',
            'notes',
            'follow_up_code',
            'follow_up_at',
            'status',
            'assigned_to',
            'created_by',
        ];

  // Di dalam class MsFollowUp

public function scopeWithRelations($query)
{
    return $query->select([
            'follow_ups.*',
            'l.company_name as lead_company_name',
            'l.contact_name as lead_contact_name',
            'c.company_name as customer_company_name',
            'c.contact_name as customer_contact_name',
            'sales.fullname as sales_name',
        ])
        ->leftJoin('leads as l', 'l.id', '=', 'follow_ups.lead_id')
        ->leftJoin('customers as c', 'c.id', '=', 'follow_ups.customer_id')
        ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'follow_ups.created_by');
}

public function scopeByCreator($query, $userId)
{
    return $query->where('follow_ups.created_by', $userId);
}

public function scopeSearch($query, $search)
{
    return $query->where(function ($q) use ($search) {
        $q->where('follow_ups.notes', 'ILIKE', "%{$search}%")
          ->orWhere('l.company_name', 'ILIKE', "%{$search}%")
          ->orWhere('c.company_name', 'ILIKE', "%{$search}%");
    });
}

public function scopeDateRange($query, $start, $end)
{
    if ($start) $query->whereDate('follow_ups.follow_up_date', '>=', $start);
    if ($end)   $query->whereDate('follow_ups.follow_up_date', '<=', $end);
    return $query;
}



      //opsional
    public function scopeOnlyDeleted(Builder $query, bool $only = false): Builder
    {
        return $only ? $query->onlyTrashed() : $query;
    }




// Scope untuk sorting dinamis
public function scopeSort($query, $sortBy, $sortDir)
{
    return $query->orderBy($sortBy ?? 'created_at', $sortDir ?? 'asc');
}


public function activities()
{
    return $this->hasMany(ModelFollowUpActivity::class)
        ->orderBy('activity_at');
}


public function logs()
{
    return $this->hasMany(FollowUpActivity::class, 'follow_up_id');
}

}
