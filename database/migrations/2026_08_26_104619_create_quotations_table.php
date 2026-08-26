<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel header fitur Penawaran (Quotations), sesuai blueprint "Penawaran
 * (Quotations)":
 *   - User (sales) bikin penawaran berdasarkan spesifikasi dari customer,
 *     bisa dikonvert ke PDF (barryvdh/laravel-dompdf).
 *   - Customer (NAMA/ALAMAT/PIC PERUSAHAAN) WAJIB dari master data
 *     customers CRM (dropdown-search, bukan ketik bebas) -- field
 *     customer_company_name/customer_address/customer_pic_name di sini
 *     adalah SNAPSHOT hasil auto-fill dari customer_id pas quotation
 *     dibuat (boleh diedit manual setelahnya), supaya histori quotation
 *     tidak ikut berubah kalau data customer di master berubah belakangan
 *     -- pola SAMA seperti expenses.location_name (snapshot, bukan
 *     live-join).
 *   - NO QUOTATION diisi manual oleh sales (bukan auto-generate), WAJIB
 *     unik.
 *   - Tidak ada approval workflow (beda dengan Expenses) -- quotation
 *     boleh diedit/dihapus bebas oleh pembuatnya. Manager/Admin cuma bisa
 *     LIHAT semua (read-only monitoring), tidak approve/reject.
 *   - SUB_TOTAL dihitung dari SUM(quotation_items.total), PPN diisi
 *     manual (nominal, bukan persen, sesuai spek), NET_AMOUNT =
 *     SUB_TOTAL + PPN.
 *   - Push ke Odoo (sale.order) dipicu MANUAL oleh sales (tombol
 *     "Push/Update ke Odoo"), BUKAN otomatis tiap save -- karena
 *     quotation-nya sendiri masih boleh diedit bebas, auto-push tiap
 *     save akan bikin banyak record duplikat di Odoo. odoo_sale_order_id
 *     dipakai buat tentuin create vs update (write) pas di-push ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            // ── SALES YANG BIKIN ──
            $table->unsignedBigInteger('sales_id');
            $table->foreign('sales_id')->references('id_user')->on('ms_users');

            // ── CUSTOMER (Master Business Partner -- WAJIB dari tabel
            //    customers, field di bawah snapshot hasil auto-fill) ──
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->string('customer_company_name'); // NAMA PERUSAHAAN
            $table->text('customer_address');         // ALAMAT PERUSAHAAN
            $table->string('customer_pic_name');       // NAMA PIC PERUSAHAAN

            // ── DATA UTAMA (sesuai blueprint) ──
            $table->string('quotation_no')->unique(); // NO QUOTATION (manual, unik)
            $table->string('customer_ref');            // CUSTOMER REF
            $table->string('payment_terms');            // PAYMENT TERMS
            $table->date('quotation_date');              // TANGGAL PENAWARAN
            $table->string('pages')->nullable();          // HALAMAN
            $table->string('validity');                    // VALIDITY
            $table->string('delivery_time');                 // DELIVERY TIME
            $table->text('term')->nullable();                 // TERM

            // ── TOTAL (dihitung dari quotation_items, disimpan juga di
            //    sini biar gampang ditampilkan di list tanpa perlu SUM
            //    tiap request) ──
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('ppn', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);

            $table->string('signature')->nullable(); // SIGNATURE (nama penandatangan)

            // ── PUSH KE ODOO (sale.order), dipicu manual oleh sales ──
            $table->unsignedBigInteger('odoo_sale_order_id')->nullable();
            $table->string('odoo_push_status')->nullable(); // pushed | failed | null (belum dicoba)
            $table->text('odoo_push_error')->nullable();
            $table->timestamp('odoo_pushed_at')->nullable();

            // ── META ──
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id_user')->on('ms_users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sales_id', 'quotation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};