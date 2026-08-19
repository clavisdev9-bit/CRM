<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTarget extends Model
{
    use SoftDeletes;

    protected $table = 'sales_targets';

    protected $fillable = [
        'sales_id',
        'period_year',
        'odoo_customer_id',
        'target_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
    ];

    public function salesUser()
    {
        return $this->belongsTo(MsUsers::class, 'sales_id', 'id_user');
    }

    public function creator()
    {
        return $this->belongsTo(MsUsers::class, 'created_by', 'id_user');
    }

    // relasi ke customer Odoo (kalau target ini level per-customer, bukan total)
    public function odooCustomer()
    {
        return $this->belongsTo(OdooCustomer::class, 'odoo_customer_id', 'odoo_partner_id');
    }
}