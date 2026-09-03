<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nambah 1 kolom baru ke ms_users TANPA mengubah/menghapus kolom yang
     * sudah ada, supaya data & fitur lama tetap jalan normal:
     *
     * manager_id -- self-relation (adjacency list) buat konsep hierarki
     * atasan/bawahan sesuai permintaan atasan (User A -> B/E -> C/D/F).
     * Nullable, jadi semua user lama otomatis dianggap "belum ada
     * atasan" sampai diisi manual, tidak ada data lama yang perlu
     * di-backfill paksa.
     */
    public function up(): void
    {
        Schema::table('ms_users', function (Blueprint $table) {
            $table->unsignedBigInteger('manager_id')->nullable()->after('divisi_id');

            // Self-relation ke ms_users sendiri (manager_id = atasan langsung
            // user ini) -- kalau atasannya dihapus, bawahannya TIDAK ikut
            // terhapus, cuma relasinya dilepas (set null), supaya data
            // bawahan tetap aman.
            $table->foreign('manager_id')
                  ->references('id_user')
                  ->on('ms_users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ms_users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn('manager_id');
        });
    }
};