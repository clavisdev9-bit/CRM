<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ── Ubah contacts.email & contacts.phone dari varchar tunggal jadi
 * jsonb array, supaya 1 contact STANDALONE (Principle, Competitor, dst)
 * bisa punya lebih dari 1 email/nomor telepon. HANYA menyentuh tabel
 * contacts -- customers/leads/customer_contacts/branch_contacts (sumber
 * data untuk contact LINKED) tetap varchar tunggal seperti semula,
 * sesuai keputusan scope (lihat Contact::resolveSource(), tidak diubah).
 *
 * Data lama (yang masih berupa string tunggal / NULL) otomatis
 * dikonversi jadi array 1 elemen lewat klausa USING supaya tidak ada
 * data yang hilang.
 * ──
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE contacts
            ALTER COLUMN email TYPE jsonb USING (
                CASE WHEN email IS NULL OR email = '' THEN NULL ELSE jsonb_build_array(email) END
            )
        ");

        DB::statement("
            ALTER TABLE contacts
            ALTER COLUMN phone TYPE jsonb USING (
                CASE WHEN phone IS NULL OR phone = '' THEN NULL ELSE jsonb_build_array(phone) END
            )
        ");
    }

    public function down(): void
    {
        // ── Balik ke varchar, ambil elemen pertama dari array sebagai
        // representasi tunggal (elemen ke-2 dst akan hilang -- konsekuensi
        // wajar dari rollback fitur multi-value, sama seperti down()
        // migration lain yang menyusutkan tipe data). ──
        DB::statement("
            ALTER TABLE contacts
            ALTER COLUMN email TYPE VARCHAR(100) USING (
                CASE WHEN email IS NULL THEN NULL ELSE (email->>0) END
            )
        ");

        DB::statement("
            ALTER TABLE contacts
            ALTER COLUMN phone TYPE VARCHAR(20) USING (
                CASE WHEN phone IS NULL THEN NULL ELSE (phone->>0) END
            )
        ");
    }
};