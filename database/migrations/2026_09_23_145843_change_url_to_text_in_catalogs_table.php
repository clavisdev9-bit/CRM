<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ── Perbesar kolom catalogs.url dari VARCHAR(500) -> TEXT.
 *
 * Link YouTube/Vimeo memang pendek, tapi link CDN pihak ketiga (mis.
 * Widen, Brightcove, dsb) sering berupa signed URL dengan query string/
 * token yang panjang -- bisa jauh melebihi 500 karakter. Sebelumnya ini
 * bikin gagal INSERT/UPDATE (Postgres menolak, ditangkap sebagai
 * QueryException lalu dikembalikan sebagai 422 oleh CatalogController),
 * walau field-nya sendiri sudah lolos validasi FormRequest (lihat
 * CatalogValidationRequest -- max sudah dinaikkan ke 2048).
 *
 * Dipilih TEXT (bukan VARCHAR dengan angka tertentu) supaya tidak perlu
 * naik-naik batas lagi ke depannya untuk link CDN sejenis. ──
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE catalogs ALTER COLUMN url TYPE TEXT");
    }

    public function down(): void
    {
        // ── Balik ke VARCHAR(500) -- link yang sudah kepanjangan waktu
        // rollback akan dipotong (LEFT ... 500) supaya ALTER-nya tidak
        // gagal karena data existing lebih panjang dari batas kolom.
        // Ini konsekuensi wajar rollback tipe data (sama seperti
        // migration down() lain di project ini, mis. yang mengubah
        // contacts.email/phone ke jsonb). ──
        DB::statement("ALTER TABLE catalogs ALTER COLUMN url TYPE VARCHAR(500) USING (LEFT(url, 500))");
    }
};