<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Odoo mendukung multi-company dalam SATU instance/database. Kolom ini
 * memetakan tiap company CRM (group_companies) ke company_id yang
 * berkorespondensi di sisi Odoo -- dipakai OdooService::companyIdFor()
 * supaya data (partner, sale order, dst.) yang dibuat manager/sales dari
 * company tertentu otomatis kepush ke company Odoo yang benar, bukan
 * ikut default global begitu saja.
 *
 * Dibuat NULLABLE -- company yang belum di-mapping akan fallback ke
 * odoo_settings.default_company_id (lihat OdooService::companyIdFor()),
 * jadi migration ini aman dijalankan meskipun belum semua company
 * langsung diisi mapping-nya lewat menu Odoo Settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_companies', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_company_id')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('group_companies', function (Blueprint $table) {
            $table->dropColumn('odoo_company_id');
        });
    }
};