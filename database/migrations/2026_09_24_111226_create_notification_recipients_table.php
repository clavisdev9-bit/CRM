<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ── TABEL NOTIFICATION_RECIPIENTS ──
     *
     * Pivot penerima notifikasi -- 1 baris per (notification, user).
     * Ini yang menampung:
     *   - siapa target sebuah notifikasi (banyak baris untuk Bulk/
     *     Reminder broadcast, 1 baris untuk Reminder personal)
     *   - status baca per user (is_read/read_at) untuk badge lonceng &
     *     Notification Center
     *
     * Untuk reminder yang berulang (daily/weekly/monthly), scheduler
     * command bikin baris BARU di tabel ini tiap kali reminder itu fire
     * -- jadi 1 baris notifications bisa punya banyak baris recipients
     * dari waktu ke waktu (histori tiap kali reminder itu "bunyi"),
     * bukan status baca yang di-reset terus-menerus di baris yang sama.
     */
    public function up(): void
    {
        Schema::create('notification_recipients', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // RELATION
            // =====================================================
            $table->unsignedBigInteger('notification_id');

            $table->unsignedBigInteger('user_id');

            // =====================================================
            // STATUS BACA
            // =====================================================
            $table->boolean('is_read')->default(false);

            $table->timestamp('read_at')->nullable();

            // =====================================================
            // AUDIT
            // -----------------------------------------------------
            // created_at di baris ini = kapan notifikasi ini "muncul"
            // buat user tsb (dipakai sebagai timestamp tampil di
            // Notification Center, bukan created_at induknya di
            // notifications -- penting khususnya untuk reminder
            // berulang yang 1 notification induk bisa fire berkali-kali).
            // =====================================================
            $table->timestamps();

            // =====================================================
            // INDEX
            // =====================================================
            $table->index('notification_id');
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);

            // =====================================================
            // FOREIGN KEY
            // =====================================================
            $table->foreign('notification_id')
                ->references('id')
                ->on('notifications')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id_user')
                ->on('ms_users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};