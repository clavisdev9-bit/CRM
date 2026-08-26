<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel utama fitur Expenses -- catatan pengeluaran dari suatu kegiatan
 * (visit, project, atau pengeluaran lain), sesuai spek "6.13 Ekspenses":
 *   DATE, EXPENSES AMOUNT, KATEGORI, KETERANGAN, KUNJUNGAN (opsional,
 *   link ke visits), ATTACHMENT.
 *
 * Alur (hasil diskusi):
 *   1. Sales submit expense -> status = pending.
 *   2. Manager/Admin approve/reject dari halaman approval.
 *   3. Begitu status = approved, sistem otomatis push ke Odoo sebagai
 *      hr.expense (employee_id & product_id di-resolve lewat auto-match +
 *      cache -- lihat kolom baru ms_users.odoo_employee_id dan tabel
 *      expense_category_odoo_products). Hasil push (sukses/gagal) dicatat
 *      di kolom odoo_push_status/odoo_push_error supaya Manager bisa lihat
 *      kalau ada yang perlu ditindaklanjuti manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // ── SALES YANG MENGAJUKAN ──
            $table->unsignedBigInteger('sales_id');
            $table->foreign('sales_id')->references('id_user')->on('ms_users');

            // ── KUNJUNGAN (opsional) ──
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();

            // ── DATA UTAMA (sesuai spek) ──
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
            $table->string('category'); // salah satu dari Expense::CATEGORIES
            $table->text('description')->nullable(); // KETERANGAN
            $table->string('attachment')->nullable(); // path file (foto bill/struk)

            // ── APPROVAL WORKFLOW ──
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id_user')->on('ms_users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // ── PUSH KE ODOO (hr.expense), dilakukan otomatis saat approved ──
            $table->unsignedBigInteger('odoo_expense_id')->nullable(); // ID record hr.expense di Odoo
            $table->string('odoo_push_status')->nullable(); // pushed | failed | null (belum dicoba)
            $table->text('odoo_push_error')->nullable(); // pesan error terakhir kalau push gagal
            $table->timestamp('odoo_pushed_at')->nullable();

            // ── META ──
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id_user')->on('ms_users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sales_id', 'status']);
            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};