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
        'odoo_product_id',
        'odoo_product_name',
    ];
}