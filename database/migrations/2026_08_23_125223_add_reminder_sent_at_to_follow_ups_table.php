<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ==========================================================================
 * REMINDER EMAIL FOLLOW-UP -- kolom penanda
 * --------------------------------------------------------------------------
 * Fitur baru: begitu follow_ups.follow_up_at tinggal <= 12 jam lagi (dan
 * status-nya masih PENDING), sistem otomatis kirim email reminder ke sales
 * yang pegang follow-up itu (assigned_to, fallback ke created_by), plus CC
 * ke semua Manager/Admin (role_id != 2).
 *
 * `reminder_sent_at` NULLABLE, cuma diisi SEKALI (timestamp pas email
 * reminder itu berhasil dikirim) -- dipakai command
 * `follow-up:send-reminders` sebagai penanda "sudah pernah diingatkan",
 * supaya sales nggak di-spam email berkali-kali tiap kali command itu jalan
 * (dijadwalkan tiap jam). Kalau follow-up-nya di-reschedule (follow_up_at
 * diubah ke tanggal lain), kolom ini SENGAJA di-reset ke NULL lagi oleh
 * controller yang nge-update follow_up_at itu supaya reminder baru bisa
 * jalan lagi buat jadwal barunya -- lihat catatan di
 * SendFollowUpReminders::handle().
 * ==========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};