<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ganti sumber field "Kunjungan" di form Ajukan Expense: dari visit_id
 * (harus visit yang SUDAH check-out) jadi customer_id + location_name,
 * pola yang sama persis dengan yang sudah dipakai di fitur Rencana
 * Kunjungan (sales_visit_plans.customer_id + title):
 *   - customer_id diisi -> location_name AUTO disinkron dari
 *     customers.company_name (lihat ExpenseController::store()).
 *   - customer_id kosong -> location_name dipakai apa adanya (isi manual,
 *     buat kasus customer/tempat yang belum ada di sistem).
 *
 * visit_id DIHAPUS TOTAL (bukan cuma dikosongkan) -- fitur Expenses baru
 * dibuat minggu ini, belum ada data produksi yang bergantung ke kolom
 * ini, jadi aman langsung diganti tanpa migrasi data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['visit_id']);
            $table->dropColumn('visit_id');

            $table->unsignedBigInteger('customer_id')->nullable()->after('sales_id');
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();

            $table->string('location_name')->nullable()->after('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'location_name']);

            $table->unsignedBigInteger('visit_id')->nullable()->after('sales_id');
            $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
        });
    }
};