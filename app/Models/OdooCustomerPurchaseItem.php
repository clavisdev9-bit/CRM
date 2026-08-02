<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooCustomerPurchaseItem extends Model
{
    protected $fillable = [
        'odoo_order_line_id',
        'odoo_order_id',
        'order_name',
        'order_date',
        'odoo_customer_id',
        'odoo_product_id',
        'product_name',
        'product_code',
        'qty',
        'price_unit',
        'company_id',
    ];
}