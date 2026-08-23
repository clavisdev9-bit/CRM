<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ==========================================================================
 * PLANNING KUNJUNGAN SALES (CALENDAR VIEW) -- FITUR BARU
 * --------------------------------------------------------------------------
 * Beda dari `visits` (catatan kunjungan yang BENERAN terjadi, ada check-in/
 * check-out & GPS) dan `visit_targets` (target BULANAN yang di-assign
 * Manager) -- tabel ini murni CATATAN RENCANA milik Sales sendiri:
 * "tanggal berapa mau ke customer/tempat mana", ditampilkan di kalender.
 * TIDAK terhubung ke alur Visit Check-In manapun -- Sales tandain sendiri
 * statusnya (planned/done/cancelled) secara manual lewat kalender ini.
 *
 * customer_id NULLABLE dan sengaja BUKAN wajib -- Sales boleh bikin rencana
 * ke customer yang SUDAH terdaftar di sistem (pilih lewat autocomplete,
 * customer_id keisi) ATAU ke target yang belum tentu ada di database
 * (misal "Survey calon customer baru area Cilegon") lewat kolom `title`
 * bebas. Kalau customer_id keisi, `title` di-sinkronkan otomatis ke
 * company_name customer itu oleh controller (SalesVisitPlanController) --
 * jadi `title` SELALU ada isinya buat ditampilkan di kalender, apapun
 * skenarionya.
 *
 * TIDAK pakai soft delete -- ini catatan planning pribadi Sales, hapus
 * beneran hilang aja (konsisten sama product_populations yang juga hard
 * delete), gak perlu jejak audit sekompleks visits/visit_targets.
 * ==========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_visit_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title', 255)
                ->comment('Nama target kunjungan -- disinkron dari customers.company_name kalau customer_id keisi, atau diisi manual kalau belum ada di sistem');
            $table->date('plan_date');
            $table->string('status', 20)->default('planned')
                ->comment('planned | done | cancelled -- ditandain manual sama sales, gak otomatis nyambung ke tabel visits manapun');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sales_id')->references('id_user')->on('ms_users')->onDelete('cascade');
            // onDelete('set null') -- kalau customer-nya dihapus dari master,
            // catatan rencana kunjungan Sales TIDAK ikut hilang, cuma
            // link-nya yang lepas (title yang sudah tersimpan tetap ada).
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');

            $table->index(['sales_id', 'plan_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_visit_plans');
    }
};