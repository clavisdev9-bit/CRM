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
        'odoo_product_id',
        'categ_id',
        'categ_name',
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

    // relasi ke product Odoo (kalau target ini level per-BRAND -- di project
    // ini "Brand" = product itu sendiri, bukan field terpisah di Odoo).
    public function odooProduct()
    {
        return $this->belongsTo(OdooProduct::class, 'odoo_product_id', 'odoo_product_id');
    }

    // TIDAK ada relasi khusus buat "kategori" -- categ_id/categ_name di
    // tabel ini didenormalisasi langsung (bukan join ke odoo_products),
    // soalnya categ_id bukan primary/unique key di odoo_products (1
    // kategori dipunyai banyak product).
}