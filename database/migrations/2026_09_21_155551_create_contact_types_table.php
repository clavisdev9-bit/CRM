<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ── TABEL MASTER JENIS CONTACT (fleksibel) ──
     * Menampung jenis-jenis contact seperti Principle, Competitor, dll.
     * Admin bisa menambah jenis baru dari UI tanpa perlu migration baru
     * (sama semangatnya dengan ms_role.hierarchy_order sebelumnya).
     *
     * Kolom 'is_system' membedakan 2 macam jenis:
     * - is_system = false (default): jenis yang dibuat manual oleh admin
     *   lewat UI, dipakai untuk contact standalone (Principle, Competitor,
     *   dst). Boleh diedit/dihapus admin.
     * - is_system = true: jenis "reserved" yang dipasang OTOMATIS oleh
     *   sistem saat sebuah contact di-link dari data yang sudah ada
     *   (Customer, Customer Contact, Branch Contact, Lead). Baris ini
     *   sengaja tidak boleh dihapus dari UI (dicek di level
     *   Controller/Model, bukan di database) karena akan merusak relasi
     *   contact yang sudah ter-link.
     */
    public function up(): void
    {
        Schema::create('contact_types', function (Blueprint $table) {

            $table->id();

            $table->string('name', 50)->unique();

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->boolean('is_system')->default(false)
                ->comment('True = jenis reserved yang dipasang otomatis saat contact di-link dari Customer/Lead/dll, tidak bisa dihapus dari UI');

            // ── Menunjuk ke salah satu Contact::SOURCE_TYPES ('customer' |
            // 'lead' | 'customer_contact' | 'branch_contact'). HANYA diisi
            // untuk baris is_system = true -- inilah yang dipakai
            // ContactController::storeLink() untuk menentukan
            // contact_type_id secara otomatis & deterministic (bukan
            // dengan mencocokkan 'name' yang gampang berubah kalau admin
            // rename jenisnya). Null untuk jenis biasa (Principle,
            // Competitor, dst). Unique supaya 1 source_type cuma boleh
            // punya 1 reserved type.
            $table->string('system_source_type', 50)->nullable()->unique();

            $table->timestamps();

            $table->softDeletes();
        });

        // =====================================================
        // SEED: jenis contact "reserved" untuk tiap source yang bisa
        // di-link (lihat Contact::SOURCE_TYPES). Diisi langsung di sini
        // supaya begitu migration ini jalan, ContactController sudah bisa
        // langsung dipakai untuk fitur "Link dari Data Existing" tanpa
        // seeder terpisah.
        // =====================================================
        DB::table('contact_types')->insert([
            [
                'name' => 'Customer',
                'description' => 'Jenis reserved -- otomatis dipasang saat contact di-link dari data Customer.',
                'is_active' => true,
                'is_system' => true,
                'system_source_type' => 'customer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lead',
                'description' => 'Jenis reserved -- otomatis dipasang saat contact di-link dari data Lead.',
                'is_active' => true,
                'is_system' => true,
                'system_source_type' => 'lead',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Customer Contact',
                'description' => 'Jenis reserved -- otomatis dipasang saat contact di-link dari PIC/Customer Contact.',
                'is_active' => true,
                'is_system' => true,
                'system_source_type' => 'customer_contact',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Branch Contact',
                'description' => 'Jenis reserved -- otomatis dipasang saat contact di-link dari PIC/Branch Contact.',
                'is_active' => true,
                'is_system' => true,
                'system_source_type' => 'branch_contact',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_types');
    }
};