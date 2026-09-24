<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ── TABEL NOTIFICATIONS ──
     *
     * Satu tabel untuk 3 kategori sekaligus (dibedakan lewat kolom
     * `category`), mengikuti pola diskriminator yang sudah dipakai di
     * project ini (mis. Catalog.media_type/source_type,
     * CatalogSendLog.channel) -- supaya "Notification Center"/lonceng
     * notifikasi di frontend cukup query 1 tabel untuk gabungan semua
     * kategori, bukan UNION 3 tabel terpisah.
     *
     * category:
     *   - bulk     : dikirim massal ke banyak user sekaligus, dibuat
     *                manual oleh Admin/Manager.
     *   - agenda   : terkait agenda/schedule/meeting/visit (PHASE B --
     *                kolom related_type/related_id disiapkan sekarang,
     *                tapi belum ada Controller yang mengisinya sampai
     *                modul Agenda existing di-link).
     *   - reminder : notifikasi pengingat berulang atau terjadwal
     *                sekali. reminder_type & reminder_scope WAJIB diisi
     *                kalau category = reminder, NULL untuk kategori lain.
     *
     * reminder_type (hanya kalau category = reminder):
     *   - daily     : jalan tiap hari jam `time_of_day`.
     *   - weekly    : jalan tiap minggu di `day_of_week` jam `time_of_day`.
     *   - monthly   : jalan tiap bulan di tanggal `day_of_month` jam
     *                 `time_of_day`.
     *   - scheduled : jalan SEKALI SAJA persis di `scheduled_at`
     *                 (bukan berulang -- beda dari daily/weekly/monthly).
     *
     * reminder_scope (hanya kalau category = reminder):
     *   - personal  : dibuat oleh user itu sendiri, cuma untuk dirinya
     *                 (created_by == satu-satunya penerima).
     *   - broadcast : dibuat Admin/Manager untuk banyak user sekaligus.
     *
     * status -- makna beda-beda tergantung category, lihat komentar di
     * Notification.php:
     *   - bulk     : draft | scheduled | sent | failed
     *   - reminder : active | inactive (nyala/mati, bukan soal "sudah
     *                terkirim" karena reminder memang berulang)
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // KATEGORI & TIPE
            // =====================================================
            $table->string('category', 20);
            // bulk | agenda | reminder

            $table->string('reminder_type', 20)->nullable();
            // daily | weekly | monthly | scheduled (hanya untuk reminder)

            $table->string('reminder_scope', 20)->nullable();
            // personal | broadcast (hanya untuk reminder)

            // =====================================================
            // ISI NOTIFIKASI
            // =====================================================
            $table->string('title', 150);

            $table->text('message');

            // =====================================================
            // RELASI KE ENTITAS LAIN (polymorphic, opsional)
            // -----------------------------------------------------
            // Disiapkan untuk Agenda Notification (Phase B) -- nunjuk
            // ke baris di modul Agenda/Schedule/Meeting/Visit existing.
            // Tidak dibuatkan FK keras (polymorphic, bisa nunjuk ke
            // beberapa tabel berbeda).
            // =====================================================
            $table->string('related_type', 50)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            // =====================================================
            // TARGET PENERIMA (bulk, & reminder dengan scope broadcast)
            // -----------------------------------------------------
            // target = 'all'      -> SEMUA user, di-resolve ULANG tiap
            //                        kali dikirim/reminder fire (bukan
            //                        daftar statis) -- supaya user baru
            //                        yang register belakangan otomatis
            //                        ikut kebagian.
            // target = 'specific' -> daftar tetap, id-nya disimpan di
            //                        recipient_ids (JSON array of
            //                        id_user), dipakai ulang tiap kali
            //                        reminder ini fire.
            // NULL untuk reminder_scope = personal (penerima = diri
            // sendiri, tidak butuh kolom ini sama sekali).
            // =====================================================
            $table->string('target', 20)->nullable();
            $table->json('recipient_ids')->nullable();

            // =====================================================
            // RECURRENCE (hanya untuk reminder)
            // =====================================================
            $table->unsignedTinyInteger('day_of_week')->nullable();
            // 0 (Minggu) - 6 (Sabtu), untuk reminder_type = weekly

            $table->unsignedTinyInteger('day_of_month')->nullable();
            // 1 - 31, untuk reminder_type = monthly

            $table->time('time_of_day')->nullable();
            // jam eksekusi, untuk daily/weekly/monthly

            $table->timestamp('next_run_at')->nullable();
            // kapan eksekusi berikutnya -- dihitung ulang tiap kali
            // scheduler command jalan (lihat ProcessReminderNotifications)

            $table->timestamp('last_run_at')->nullable();

            // =====================================================
            // STATUS & JADWAL
            // =====================================================
            $table->string('status', 20)->default('draft');

            $table->timestamp('scheduled_at')->nullable();
            // bulk: kapan mau dikirim (kalau dijadwalkan, bukan langsung)
            // reminder (scheduled): kapan reminder ini harus fire (sekali)

            $table->timestamp('sent_at')->nullable();
            // bulk: kapan actually terkirim
            // reminder: sama artinya dengan last_run_at, disimpan juga di
            // sini supaya query "riwayat terkirim" tetap seragam lintas
            // kategori tanpa perlu cek reminder_type dulu

            // =====================================================
            // OWNERSHIP / AUDIT
            // =====================================================
            $table->unsignedBigInteger('created_by');

            $table->timestamps();

            // =====================================================
            // INDEX
            // =====================================================
            $table->index('category');
            $table->index(['category', 'status']);
            $table->index('created_by');
            $table->index('next_run_at');
            $table->index(['related_type', 'related_id']);

            // =====================================================
            // FOREIGN KEY
            // =====================================================
            $table->foreign('created_by')
                ->references('id_user')
                ->on('ms_users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};