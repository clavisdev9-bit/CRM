<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ==========================================================================
 * TAMBAH KOLOM GEOLOKASI KE CUSTOMER_BRANCHES (PHASE 3)
 * --------------------------------------------------------------------------
 * Nyusul Phase 1 (kolom sama di tabel `customers`) -- sekarang cabang
 * customer (customer_branches) juga bisa di-geocode dari field Address-nya
 * sendiri (forward geocoding lewat Nominatim, lihat Location::search()),
 * dan punya radius_meter sendiri buat validasi jarak GPS sales pas Visit
 * Check-In ke cabang ini (lihat Visits::checkInVisitCustomer()).
 *
 * Sebelum ini, checkin ke branch (branch_id != null di tabel visits) SAMA
 * SEKALI gak divalidasi jaraknya karena branch belum punya titik lokasi
 * sendiri -- sekarang disamain konsepnya dengan customer head company.
 *
 * Semua kolom NULLABLE -- branch lama yang belum pernah di-geocode ulang
 * (belum edit address-nya) tetap aman, ga wajib diisi lewat migration ini.
 * Tapi INGAT: begitu Visits::checkInVisitCustomer() ikut mewajibkan
 * koordinat (Phase 2.1), branch yang masih NULL bakal keblokir checkin-nya
 * sampai dilengkapi lewat form Edit Branch.
 * ==========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_branches', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('city')
                ->comment('Hasil forward-geocoding dari field address cabang (Nominatim)');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude')
                ->comment('Hasil forward-geocoding dari field address cabang (Nominatim)');
            $table->unsignedInteger('radius_meter')->nullable()->default(350)->after('longitude')
                ->comment('Radius toleransi (meter) buat matching lokasi Visit Check-In ke cabang ini');
        });
    }

    public function down(): void
    {
        Schema::table('customer_branches', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'radius_meter']);
        });
    }
};