<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nambah kolom cabang_id ke ms_users supaya tiap Sales Person bisa
     * dikelompokkan berdasarkan Cabang di laporan. Nullable, jadi user
     * lama aman (dianggap belum ada cabangnya sampai diisi manual).
     *
     * File terpisah dari create_ms_cabang_table supaya urutannya jelas:
     * tabel ms_cabang harus sudah ada dulu sebelum foreign key ini dibuat.
     */
    public function up(): void
    {
        Schema::table('ms_users', function (Blueprint $table) {
            $table->unsignedBigInteger('cabang_id')->nullable()->after('divisi_id');

            $table->foreign('cabang_id')
                  ->references('id_cabang')
                  ->on('ms_cabang')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ms_users', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });
    }
};
