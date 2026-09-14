<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom cache buat fitur Quotations -- Salesperson (user_id) di sale.order
 * Odoo itu map ke res.users (akun login Odoo), BUKAN ke hr.employee kayak
 * odoo_employee_id di fitur Expenses (2 model beda di Odoo walaupun sama-sama
 * "identitas karyawan"). Karena itu perlu kolom cache TERPISAH.
 *
 * Sama persis pola odoo_employee_id: AUTO-MATCH BY NAME + CACHE -- pertama
 * kali quotation sales itu di-push ke Odoo, sistem cari res.users yang
 * namanya cocok persis sama fullname, lalu ID-nya disimpan di sini supaya
 * push berikutnya gak perlu cari ulang. Lihat
 * QuotationController::resolveOdooUserId().
 *
 * odoo_user_name ikut disimpan (denormalized) cuma buat memudahkan Admin
 * verifikasi manual "oh ini match-nya ke siapa" tanpa perlu buka Odoo, bukan
 * dipakai buat query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ms_users', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_user_id')->nullable()->after('odoo_employee_name');
            $table->string('odoo_user_name')->nullable()->after('odoo_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('ms_users', function (Blueprint $table) {
            $table->dropColumn(['odoo_user_id', 'odoo_user_name']);
        });
    }
};