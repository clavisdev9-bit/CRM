<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cache mapping 1 kategori expense CRM -> 1 product Odoo (product.product,
 * can_be_expensed=true) yang dipakai sebagai product_id saat push ke
 * hr.expense. Diisi otomatis lewat auto-match by name di
 * ExpenseController::resolveOdooProductIdForCategory().
 */
class ExpenseCategoryOdooProduct extends Model
{
    protected $table = 'expense_category_odoo_products';

    protected $fillable = [
        'category',
        // Company_id ASLI dari Odoo (res.company id). NULL = mapping ini
        // SHARED/global (product-nya company_id=false di Odoo, dipakai
        // buat semua company). Diisi kalau product-nya company-specific --
        // lihat migration add_company_id_to_expense_category_odoo_products_table.
        'company_id',
        'odoo_product_id',
        'odoo_product_name',
    ];
}