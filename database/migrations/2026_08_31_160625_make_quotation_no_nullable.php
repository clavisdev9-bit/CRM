<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ==========================================================================
 * QUOTATION_NO: dari WAJIB (required, unique) jadi OPSIONAL (nullable,
 * tetap unique)
 * --------------------------------------------------------------------------
 * Field "No. Quotation" di form Buat/Edit Quotation (MyQuotations.vue)
 * sempat di-hidden dari tampilan (di-comment) oleh sales karena nomor
 * quotation biasanya belum ada di awal proses (baru terbit belakangan) --
 * padahal validasi backend & frontend masih mewajibkan field ini diisi,
 * jadi form quotation baru jadi TIDAK BISA disubmit sama sekali (tombol
 * submit permanen ke-disable karena quotation_no selalu kosong).
 *
 * Kolomnya sendiri TETAP unique -- kalau sales isi manual, nomornya tetap
 * harus unik. Yang berubah cuma NOT NULL constraint-nya dilepas, boleh
 * dikosongkan.
 *
 * Kenapa pakai DB::statement() bukan ->nullable()->change(): ->change()
 * butuh package doctrine/dbal cuma buat ganti constraint NOT NULL doang --
 * raw SQL lebih ringan & gak nambah dependency (pola sama persis dengan
 * 2026_08_22_000003_change_default_radius_meter_to_350.php).
 *
 * Unique constraint DIBIARKAN APA ADANYA -- PostgreSQL memperlakukan NULL
 * sebagai "tidak sama dengan NULL manapun" di kolom unique, jadi boleh ada
 * banyak baris quotation_no = NULL sekaligus tanpa melanggar constraint
 * unique-nya (cuma nilai non-NULL yang harus tetap unik satu sama lain).
 * ==========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE quotations ALTER COLUMN quotation_no DROP NOT NULL');
    }

    public function down(): void
    {
        // Catatan: reversal ini akan GAGAL kalau di database sudah ada
        // baris quotation_no yang NULL (hasil dari fitur opsional ini
        // dipakai beneran) -- itu memang konsekuensi wajar mundurin field
        // opsional balik jadi wajib. Kalau perlu di-rollback, isi manual
        // dulu baris yang NULL sebelum jalanin down() ini.
        DB::statement('ALTER TABLE quotations ALTER COLUMN quotation_no SET NOT NULL');
    }
};