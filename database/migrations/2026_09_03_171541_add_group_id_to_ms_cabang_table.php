<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 1 Cabang = 1 Company -- nambah kolom group_id ke ms_cabang, FK ke
     * group_companies.id_group. Kolom dibuat NULLABLE di level database
     * (pola yang sama kayak role_id/divisi_id/group_id di ms_users --
     * nullable di DB, tapi WAJIB diisi lewat validasi form/CabangValidationRequest),
     * supaya migration ini aman dijalankan meskipun tabel ms_cabang sudah
     * ada baris data test sebelumnya (tidak perlu backfill paksa).
     *
     * onDelete('cascade') dipilih konsisten sama pola FK lain di app ini
     * buat relasi kepemilikan (ms_submenu->ms_menu, dst): kalau company-nya
     * beneran di-hard-delete, cabang di bawahnya ikut kehapus -- karena
     * cabang tanpa company memang tidak match konsepnya.
     */
    public function up(): void
    {
        Schema::table('ms_cabang', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('cabang');

            $table->foreign('group_id')
                  ->references('id_group')
                  ->on('group_companies')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ms_cabang', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};