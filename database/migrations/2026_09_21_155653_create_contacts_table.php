<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ── TABEL CONTACT (Principle, Competitor, dan hasil link dari data
     *    yang sudah ada) ──
     *
     * Satu tabel ini menampung 2 macam baris:
     *
     * 1. STANDALONE (source_type & source_id NULL)
     *    Diisi manual lewat form Contact -- dipakai untuk Principle,
     *    Competitor, atau jenis lain yang memang belum ada di tabel
     *    manapun di sistem. Semua field identitas (company_name,
     *    contact_name, email, phone, dst) diisi langsung di baris ini.
     *
     * 2. LINKED (source_type & source_id terisi)
     *    Bukan diketik manual, tapi "pointer" polymorphic ke baris di
     *    tabel lain yang sudah ada: customers | leads | customer_contacts
     *    | branch_contacts. Field identitas (company_name dst) SENGAJA
     *    dibiarkan NULL di sini -- datanya diambil LIVE dari tabel
     *    sumbernya lewat relasi morphTo di Model (lihat Contact.php),
     *    supaya kalau data di sumbernya berubah, otomatis ikut berubah
     *    di sini juga tanpa perlu sinkronisasi manual.
     *    contact_type_id untuk baris jenis ini otomatis diisi salah satu
     *    contact_types yang is_system = true (Customer / Lead / dst),
     *    bukan dipilih manual oleh user.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // RELATION
            // =====================================================
            $table->unsignedBigInteger('contact_type_id');

            // =====================================================
            // CONTACT CODE
            // -----------------------------------------------------
            // Hanya diisi (auto-generate) untuk contact STANDALONE.
            // Contact hasil LINK dibiarkan null karena datanya sudah
            // punya kode sendiri di tabel asal (customer_code, dst).
            // =====================================================
            $table->string('contact_code', 30)->nullable()->unique();

            // =====================================================
            // SOURCE (polymorphic, nullable = standalone)
            // -----------------------------------------------------
            // source_type: null | 'customer' | 'lead' | 'customer_contact'
            //              | 'branch_contact'
            // source_id  : id baris di tabel sumbernya
            // Tidak dibuatkan FK constraint keras karena source_id bisa
            // menunjuk ke salah satu dari beberapa tabel berbeda
            // (polymorphic), FK Postgres tidak mendukung itu.
            // =====================================================
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            // =====================================================
            // CONTACT IDENTITY
            // -----------------------------------------------------
            // Nullable semua: wajib diisi untuk standalone, sengaja
            // dibiarkan kosong untuk linked (diambil live via relasi).
            // =====================================================
            $table->string('company_name', 150)->nullable();

            $table->string('contact_name', 100)->nullable();

            $table->string('email', 100)->nullable();

            $table->string('phone', 20)->nullable();

            $table->text('address')->nullable();

            $table->text('notes')->nullable();

            // =====================================================
            // STATUS
            // =====================================================
            $table->string('status', 50)->default('Active');
            // Active | Inactive

            // =====================================================
            // OWNERSHIP
            // -----------------------------------------------------
            // Tidak ada id_user/assigned_to/visibility_type seperti di
            // customers -- Contact adalah data referensi bersama, semua
            // user boleh lihat semua. created_by murni untuk audit.
            // =====================================================
            $table->unsignedBigInteger('created_by');

            // =====================================================
            // AUDIT
            // =====================================================
            $table->timestamps();

            $table->softDeletes();

            // =====================================================
            // INDEX
            // =====================================================
            $table->index('contact_type_id');

            $table->index('status');

            $table->index('created_by');

            $table->index(['source_type', 'source_id']);

            // =====================================================
            // FOREIGN KEY
            // =====================================================
            $table->foreign('contact_type_id')
                ->references('id')
                ->on('contact_types')
                ->restrictOnDelete();
            // restrictOnDelete: jenis contact tidak boleh dihapus kalau
            // masih dipakai contact manapun (dicek juga di level
            // Controller supaya errornya lebih ramah daripada DB error).

            $table->foreign('created_by')
                ->references('id_user')
                ->on('ms_users')
                ->cascadeOnDelete();
        });

        // =====================================================
        // UNIQUE INDEX: company_name per contact_type_id
        // (case-insensitive, exact match, HANYA untuk baris standalone)
        // ---------------------------------------------------
        // Sama teknik dengan customers_company_name_unique_idx: pakai
        // raw SQL karena butuh LOWER(TRIM(...)) + partial index. Dibatasi
        // per contact_type_id supaya nama yang sama boleh dipakai di
        // jenis berbeda (mis. "PT Maju" boleh jadi Principle DAN
        // Competitor sekaligus), tapi tidak boleh duplikat di jenis yang
        // sama. AND source_type IS NULL karena baris linked sengaja
        // company_name-nya kosong, jadi tidak relevan dicek di sini.
        // =====================================================
        DB::statement("
            CREATE UNIQUE INDEX contacts_company_name_unique_idx
            ON contacts (contact_type_id, LOWER(TRIM(company_name)))
            WHERE deleted_at IS NULL AND source_type IS NULL
        ");

        // =====================================================
        // UNIQUE INDEX: satu baris sumber cuma boleh di-link SEKALI
        // ---------------------------------------------------
        // Mencegah customer/lead/customer_contact/branch_contact yang
        // sama ter-link dobel jadi 2 baris contacts berbeda.
        // =====================================================
        DB::statement("
            CREATE UNIQUE INDEX contacts_source_unique_idx
            ON contacts (source_type, source_id)
            WHERE deleted_at IS NULL AND source_type IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};