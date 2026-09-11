<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product Odoo yang dipakai 1 kategori expense TERNYATA bisa company-
 * specific di Odoo (bukan selalu shared) -- kejadian nyata: kategori yang
 * sama match ke product yang company_id-nya cuma valid buat 1 company,
 * jadinya push expense dari employee company LAIN gagal ("no company
 * crossover is allowed"). Makanya cache mapping-nya perlu dipecah PER
 * COMPANY, bukan 1 mapping global per kategori lagi -- pola sama persis
 * kayak company_id di odoo_products/odoo_customers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_category_odoo_products', function (Blueprint $table) {
            // Company_id ASLI dari Odoo (res.company id). NULL artinya
            // product buat kategori ini SHARED/global di Odoo
            // (company_id = false di sana), dipakai buat semua company.
            // Kalau ada isinya, mapping ini cuma valid buat employee yang
            // company Odoo-nya SAMA kayak angka ini.
            $table->unsignedBigInteger('company_id')->nullable()->after('category');
        });

        // 1 kategori sekarang boleh punya mapping product BEDA per
        // company -- unique constraint diubah dari "category sendirian"
        // jadi gabungan "category + company_id".
        Schema::table('expense_category_odoo_products', function (Blueprint $table) {
            $table->dropUnique(['category']);
            $table->unique(['category', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::table('expense_category_odoo_products', function (Blueprint $table) {
            $table->dropUnique(['category', 'company_id']);
        });

        Schema::table('expense_category_odoo_products', function (Blueprint $table) {
            $table->dropColumn('company_id');
            $table->unique('category');
        });
    }
};