<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Tabel target visit yang di-set MANAGER ke SALES tertentu, per Customer ATAU
 * per Branch (persis 1 dari 2 itu, sama kayak aturan follow_ups: lead_id XOR
 * customer_id), berlaku untuk 1 bulan kalender (period_month = tanggal 1 di
 * bulan itu, misal 2026-08-01).
 *
 * - Progress (berapa kali sudah divisit) DIHITUNG DINAMIS dari tabel `visits`,
 *   BUKAN disimpan di kolom terpisah. Aturan hitungnya (SUDAH DIKONFIRMASI user):
 *     1) hanya visit dengan sales_id + customer_id/branch_id yang sama dengan
 *        target ini,
 *     2) visit_at jatuh di bulan period_month,
 *     3) visits.created_at >= visit_targets.created_at -- visit yang sudah ada
 *        SEBELUM target ini dibuat TIDAK ikut dihitung (progress mulai dari 0
 *        sejak target dibuat),
 *     4) visit sudah check-in (v.check_in_at IS NOT NULL) -- dianggap "1x visit"
 *        walau belum tentu sudah check-out.
 *   Karena baseline cutoff-nya pakai created_at record TARGET (bukan target_count),
 *   manager boleh edit target_count kapan saja TANPA reset progress (sudah
 *   dikonfirmasi user: "progress tetep lanjut").
 *
 * - Unique constraint TIDAK dipakai lewat $table->unique() biasa karena kolom
 *   customer_id/branch_id nullable -- di Postgres, NULL tidak dianggap "sama
 *   dengan" NULL lain di unique index biasa, jadi constraint kombinasi kolom
 *   nullable tidak akan menolak duplikat sungguhan. Makanya dipakai 2 PARTIAL
 *   UNIQUE INDEX terpisah (lihat bawah) yang masing-masing hanya berlaku saat
 *   kolom targetnya IS NOT NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_targets', function (Blueprint $table) {
            $table->id();
            $table->string('target_code', 40)->unique();
            $table->unsignedBigInteger('sales_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedInteger('target_count');
            $table->date('period_month');
            $table->unsignedBigInteger('created_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sales_id')->references('id_user')->on('ms_users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('customer_branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id_user')->on('ms_users')->onDelete('cascade');

            $table->index(['sales_id', 'period_month']);
            $table->index(['period_month']);
        });

        // exactly 1 dari customer_id/branch_id -- pola sama seperti chk_followups_owner
        DB::statement('
            ALTER TABLE visit_targets
            ADD CONSTRAINT chk_visit_targets_owner
            CHECK (
                (customer_id IS NOT NULL AND branch_id IS NULL)
                OR (customer_id IS NULL AND branch_id IS NOT NULL)
            )
        ');

        // partial unique index: cegah manager bikin target dobel buat kombinasi
        // sales+customer (atau sales+branch) yang sama di bulan yang sama.
        DB::statement('
            CREATE UNIQUE INDEX uniq_visit_targets_customer
            ON visit_targets (sales_id, customer_id, period_month)
            WHERE customer_id IS NOT NULL AND deleted_at IS NULL
        ');
        DB::statement('
            CREATE UNIQUE INDEX uniq_visit_targets_branch
            ON visit_targets (sales_id, branch_id, period_month)
            WHERE branch_id IS NOT NULL AND deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_targets');
    }
};