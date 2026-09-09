<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odoo_products', function (Blueprint $table) {
            // Company_id ASLI dari Odoo (res.company id), bukan id_group
            // CRM lokal -- polanya sama persis kayak kolom company_id yang
            // sudah ada di odoo_customers. NULL artinya product itu
            // shared/global di Odoo (company_id = false), kelihatan buat
            // semua company CRM. Kalau ada isinya, product itu cuma
            // kelihatan buat company CRM yang odoo_company_id-nya (di
            // group_companies) cocok sama angka ini.
            $table->unsignedBigInteger('company_id')->nullable()->after('odoo_product_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('odoo_products', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};