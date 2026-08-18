<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooProduct extends Model
{
    protected $table = 'odoo_products';

    protected $fillable = [
        'odoo_product_id',
        'name',
        'default_code',
        'barcode',
        'categ_id',
        'categ_name',
        'uom_id',
        'uom_name',
        'list_price',
        'standard_price',
        'qty_available',
        'active',
    ];

    protected $casts = [
        'list_price'     => 'decimal:2',
        'standard_price' => 'decimal:2',
        'qty_available'  => 'decimal:2',
        'active'         => 'boolean',
    ];
}