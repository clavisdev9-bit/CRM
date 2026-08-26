<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache mapping customer CRM -> partner Odoo (res.partner), buat push
 * quotation sebagai sale.order.partner_id. Pola SAMA PERSIS dengan
 * ms_users.odoo_employee_id di fitur Expenses: auto-match by name +
 * cache, diisi otomatis oleh QuotationController::resolveOdooPartnerId()
 * pas quotation pertama kali di-push, supaya tidak perlu lookup ulang ke
 * Odoo tiap kali push.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_partner_id')->nullable()->after('company_name');
            $table->string('odoo_partner_name')->nullable()->after('odoo_partner_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['odoo_partner_id', 'odoo_partner_name']);
        });
    }
};