<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $table = 'quotation_items';

    protected $fillable = [
        'quotation_id',
        'odoo_product_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total',
        'sort_order',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    /**
     * odoo_product_id di sini FK ke odoo_products.id (PK lokal), bukan
     * ke odoo_products.odoo_product_id -- lihat komentar di migration
     * create_quotation_items_table.
     */
    public function odooProduct()
    {
        return $this->belongsTo(OdooProduct::class, 'odoo_product_id', 'id');
    }
}