<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ==========================================================================
 * PHASE 2 -- VALIDASI RADIUS VISIT CHECK-IN
 * --------------------------------------------------------------------------
 * Nambah kolom ke tabel `visits` buat nyimpen hasil pengecekan jarak antara
 * lokasi GPS sales pas check-in vs lokasi customer (latitude/longitude/
 * radius_meter yang udah ditambahin ke tabel `customers` di Phase 1).
 *
 * - is_outside_radius   : null = belum/gak dievaluasi (customer belum punya
 *                         lat/lng, atau checkin ke branch yang belum
 *                         didukung fitur ini), true/false = hasil evaluasi.
 * - distance_meter      : jarak asli (meter) antara GPS sales vs lokasi
 *                         customer pas checkin, buat referensi Manager.
 * - radius_confirm_reason : diisi kalau sales checkin di luar radius dan
 *                         milih "tetap lanjut" -- alasan/catatan opsional
 *                         yang mereka isi pas konfirmasi.
 *
 * Semua NULLABLE -- visit lama (sebelum fitur ini ada) & visit ke lead
 * (bukan customer) tetap aman, kolomnya cuma NULL aja.
 * ==========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->boolean('is_outside_radius')->nullable()->after('longitude')
                ->comment('null = belum dievaluasi, true = checkin di luar radius customer, false = di dalam radius');
            $table->decimal('distance_meter', 10, 2)->nullable()->after('is_outside_radius')
                ->comment('Jarak GPS sales ke lokasi customer pas checkin, dalam meter');
            $table->text('radius_confirm_reason')->nullable()->after('distance_meter')
                ->comment('Alasan/catatan sales kalau tetap checkin walau di luar radius');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['is_outside_radius', 'distance_meter', 'radius_confirm_reason']);
        });
    }
};