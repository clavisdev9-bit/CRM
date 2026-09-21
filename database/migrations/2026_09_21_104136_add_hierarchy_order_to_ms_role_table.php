<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ── HIERARCHY ORDER (dinamis, data-driven) ──
     * Sebelumnya urutan tampil tier di modal "Hirarki User"
     * (Manager -> Admin -> Sales) di-hardcode lewat array PHP
     * ($tierOrder) di Administrator::userHierarchy(). Itu artinya kalau
     * ada role baru ditambah (misal "Supervisor"), posisinya di hirarki
     * tidak otomatis benar -- perlu sentuh source code lagi.
     *
     * Kolom ini memindahkan aturan urutan itu jadi data di database:
     * semakin KECIL angkanya, semakin TINGGI posisinya di hirarki
     * (ditaruh paling atas). Nullable -- role yang belum diisi otomatis
     * jatuh ke paling bawah (lihat fallback di
     * Administrator::userHierarchy()), jadi migration ini aman dijalankan
     * di data lama tanpa perlu isi nilai default satu-satu dulu.
     */
    public function up(): void
    {
        Schema::table('ms_role', function (Blueprint $table) {
            $table->integer('hierarchy_order')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ms_role', function (Blueprint $table) {
            $table->dropColumn('hierarchy_order');
        });
    }
};