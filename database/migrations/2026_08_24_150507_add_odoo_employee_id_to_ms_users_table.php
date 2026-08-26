<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom cache buat fitur Expenses -- setiap sales (ms_users) di-mapping ke
 * satu employee di Odoo (hr.employee) lewat AUTO-MATCH BY NAME + CACHE:
 * pertama kali expense sales itu mau di-push ke Odoo, sistem cari
 * hr.employee yang namanya cocok persis sama fullname, lalu ID-nya
 * disimpan di sini supaya push berikutnya gak perlu cari ulang.
 *
 * odoo_employee_name ikut disimpan (denormalized) cuma buat memudahkan
 * Admin verifikasi manual "oh ini match-nya ke siapa" tanpa perlu buka
 * Odoo, bukan dipakai buat query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ms_users', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_employee_id')->nullable()->after('divisi_id');
            $table->string('odoo_employee_name')->nullable()->after('odoo_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('ms_users', function (Blueprint $table) {
            $table->dropColumn(['odoo_employee_id', 'odoo_employee_name']);
        });
    }
};