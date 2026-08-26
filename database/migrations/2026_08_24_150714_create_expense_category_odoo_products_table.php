<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache mapping 7 kategori expense CRM (Gasoline, Entertainment, Parking,
 * Transportation, Toll, Hotel, Restaurant, Other) ke product Odoo
 * (product.product, can_be_expensed=true) yang dipakai sebagai product_id
 * saat push ke hr.expense.
 *
 * Sama pola-nya kayak mapping employee di ms_users: AUTO-MATCH BY NAME +
 * CACHE. Pertama kali kategori tertentu mau di-push, sistem cari product
 * Odoo yang namanya cocok, simpan hasilnya di sini (satu baris per
 * kategori, di-upsert lewat updateOrCreate), dipakai terus buat push
 * berikutnya tanpa cari ulang ke Odoo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_category_odoo_products', function (Blueprint $table) {
            $table->id();
            $table->string('category')->unique(); // salah satu dari Expense::CATEGORIES
            $table->unsignedBigInteger('odoo_product_id')->nullable();
            $table->string('odoo_product_name')->nullable(); // denormalized, buat verifikasi manual
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_category_odoo_products');
    }
};