<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Master Cabang -- tabel baru, tidak menyentuh tabel lain yang sudah
     * ada. Field mengikuti spesifikasi: Cabang, Alamat, No Telp (semua
     * wajib diisi).
     */
    public function up(): void
    {
        Schema::create('ms_cabang', function (Blueprint $table) {
            $table->unsignedBigInteger('id_cabang', true); // auto increment
            $table->string('cabang');                      // nama kota/cabang, cth: Palembang
            $table->string('alamat');                       // alamat cabang
            $table->string('no_telp');                      // no telp cabang
            $table->timestamps();                            // created_at & updated_at
            $table->softDeletes();                           // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ms_cabang');
    }
};