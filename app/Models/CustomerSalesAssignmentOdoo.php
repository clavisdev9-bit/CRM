<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSalesAssignmentOdoo extends Model
{
    protected $table = 'customer_sales_assignments_odoo';

    protected $fillable = [
        'odoo_customer_id',
        'sales_id',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function salesUser()
    {
        return $this->belongsTo(MsUsers::class, 'sales_id', 'id_user');
    }

    public function customer()
    {
        return $this->belongsTo(OdooCustomer::class, 'odoo_customer_id', 'odoo_partner_id');
    }
}