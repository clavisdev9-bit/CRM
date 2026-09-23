<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ── Tambahan untuk mendukung kirim catalog via WhatsApp (selain email
 * yang sudah ada di migration awal catalog_send_logs):
 * - channel: 'email' | 'whatsapp' -- default 'email' supaya baris lama
 *   (kalau sudah ada data) tetap konsisten.
 * - recipient_phone: nomor WA tujuan, dipakai kalau channel='whatsapp'.
 * - recipient_email dibikin nullable, karena baris channel='whatsapp'
 *   tidak selalu (bahkan biasanya tidak) punya email tujuan.
 * ──
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_send_logs', function (Blueprint $table) {
            $table->string('channel', 20)->default('email')->after('catalog_title');
            $table->string('recipient_phone')->nullable()->after('recipient_email');
        });

        // recipient_email jadi opsional (khusus baris channel='whatsapp').
        // Pakai raw SQL (bukan ->nullable()->change()) supaya tidak perlu
        // tambahan dependency doctrine/dbal.
        DB::statement('ALTER TABLE catalog_send_logs ALTER COLUMN recipient_email DROP NOT NULL');
    }

    public function down(): void
    {
        // Isi dulu recipient_email yang NULL (mis. baris whatsapp) supaya
        // tidak melanggar NOT NULL constraint waktu dikembalikan.
        DB::statement("UPDATE catalog_send_logs SET recipient_email = '' WHERE recipient_email IS NULL");
        DB::statement('ALTER TABLE catalog_send_logs ALTER COLUMN recipient_email SET NOT NULL');

        Schema::table('catalog_send_logs', function (Blueprint $table) {
            $table->dropColumn(['channel', 'recipient_phone']);
        });
    }
};