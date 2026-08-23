<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsCustomers extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customers';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [

        // =====================================
        // RELATION
        // =====================================
        'lead_id',
        'lead_category_id',
        'industry_id',

        // =====================================
        // CUSTOMER
        // =====================================
        'customer_code',
        'company_name',
        'contact_name',
        'email',
        'phone',

        // =====================================
        // OWNER
        // =====================================
        'id_user',
        'assigned_to',
        'created_by',

        // =====================================
        // STATUS
        // =====================================
        'customer_status',
        'visibility_type',

        // =====================================
        // APPROVAL
        // =====================================
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_note',
        'approval_revision',

        // =====================================
        // INFORMATION
        // =====================================
        'lead_source',
        'address',
        'notes',

        // =====================================
        // GEOLOCATION (auto-fill dari forward-geocoding Address --
        // lihat Location::search() -- dipakai juga buat matching
        // radius Visit Check-In di phase 2)
        // =====================================
        'latitude',
        'longitude',
        'radius_meter',

        // =====================================
        // ACTIVITY
        // =====================================
        'converted_at',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
        'approved_at'  => 'datetime',
        'latitude'     => 'decimal:7',
        'longitude'    => 'decimal:7',
        'radius_meter' => 'integer',
    ];

    protected $attributes = [
        'customer_status' => 'Active',
        'visibility_type' => 'PRIVATE',
        'approval_status' => 'pending',
        'approval_revision' => 0,
        'radius_meter' => 100,
    ];

    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE
    |--------------------------------------------------------------------------
    */

    public static function isCustExistsAdd($code)
    {
        return self::where('customer_code', $code)->exists();
    }

    public static function isCustExists($code, $excludeId = null)
    {
        return self::where('customer_code', $code)
            ->where('id', '!=', $excludeId)
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeOnlyDeleted($query, $onlyDeleted)
    {
        if ($onlyDeleted) {
            return $query->onlyTrashed();
        }

        return $query;
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('customers.company_name', 'ILIKE', "%{$search}%")
              ->orWhere('customers.customer_code', 'ILIKE', "%{$search}%")
              ->orWhere('customers.contact_name', 'ILIKE', "%{$search}%")
              ->orWhere('customers.email', 'ILIKE', "%{$search}%")
              ->orWhere('customers.phone', 'ILIKE', "%{$search}%");
        });
    }

    public function scopeSort($query, $sortBy = 'created_at', $sortDir = 'desc')
    {
        return $query->orderBy($sortBy, $sortDir);
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVAL SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }
}