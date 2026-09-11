<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logo sebelumnya cuma ada 1 (global, dari tabel app_settings) -- dipakai
 * sama semua company. Sekarang tiap company (group_companies) bisa punya
 * logo sendiri-sendiri, ditampilkan di Sidebar.vue (brandLogoUrl) sesuai
 * company user yang login. Kalau kolom ini kosong buat company tertentu,
 * frontend fallback ke logo App Setting global (perilaku lama, tidak
 * berubah) -- jadi migration ini aman dijalankan tanpa perlu isi data
 * dulu buat semua company sekaligus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_companies', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('description_group');
        });
    }

    public function down(): void
    {
        Schema::table('group_companies', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};