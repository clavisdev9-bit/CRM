<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ==========================================================================
 * TAMBAH KOLOM GEOLOKASI KE MASTER CUSTOMER (PHASE 1)
 * --------------------------------------------------------------------------
 * Fitur: auto-fill latitude/longitude ketika user paste/ketik alamat di
 * field Address (Add/Edit Customer) -- forward geocoding lewat Nominatim
 * (lihat Location::search()).
 *
 * radius_meter dipakai untuk kebutuhan PHASE 2 nanti (matching lokasi GPS
 * sales vs lokasi customer ini pas Visit Check-In) -- BELUM dipakai di
 * phase 1 ini, tapi kolomnya disiapkan sekalian sesuai request awal biar
 * ga perlu migration baru lagi nanti. Default 100 meter (radius standar
 * yang wajar buat toleransi akurasi GPS di lokasi customer).
 *
 * Semua kolom NULLABLE -- customer lama yang belum pernah di-geocode ulang
 * (belum edit address-nya) tetap aman, ga wajib diisi.
 * ==========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('address')
                ->comment('Hasil forward-geocoding dari field address (Nominatim)');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude')
                ->comment('Hasil forward-geocoding dari field address (Nominatim)');
            $table->unsignedInteger('radius_meter')->nullable()->default(350)->after('longitude')
                ->comment('Radius toleransi (meter) buat matching lokasi Visit Check-In -- dipakai di phase 2');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'radius_meter']);
        });
    }
};