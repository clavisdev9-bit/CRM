<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;


class MsCustomers extends Model
{
   use HasFactory;
    use SoftDeletes;
    protected $table = 'customers';
    protected $primaryKey = 'id';
    public    $incrementing = true;
    public    $timestamps = true;
     protected $fillable = [
    'lead_id',
    'lead_category_id',
    'industry_id',

    'customer_code',
    'company_name',
    'contact_name',
    'email',
    'phone',
    'id_user',        

    'assigned_to',
    'created_by',

    'customer_status',
    'address',
    'notes',

    'converted_at',
        'lead_source',  // ← pastikan ada ini

];


protected $casts = [
    'converted_at' => 'datetime',
];

protected $attributes = [
    'customer_status' => 'Active',
];


    //cek apakah ada name menu yang sama  untuk add
    public static function isCustExistsAdd($code)
    {
        return self::where('customer_code', $code)->exists();
    }

    //cek apakah ada name menu yang sama  untuk update
    public static function isCustExists($code, $excludeId = null)
    {
        return self::where('customer_code', $code)
            ->where('id', '!=', $excludeId)
            ->exists();
    }

    /* =========================
     | SCOPES
     ========================= */

    public function scopeOnlyDeleted($query, $onlyDeleted)
    {
        if ($onlyDeleted) {
            return $query->onlyTrashed();
        }

        return $query; // ⬅️ PENTING
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('customers.company_name', 'ILIKE', "%{$search}%")
              ->orWhere('customers.customer_code', 'ILIKE', "%{$search}%")
              ->orWhere('customers.email', 'ILIKE', "%{$search}%");
        });
    }

    public function scopeSort($query, $sortBy, $sortDir)
    {
        return $query->orderBy($sortBy, $sortDir);
    }
}
