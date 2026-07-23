<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_branches', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // RELATION
            // =====================================================
            $table->unsignedBigInteger('customer_id');

            // =====================================================
            // BRANCH IDENTITY
            // =====================================================
            $table->string('branch_code', 30)->unique()->nullable();

            $table->string('branch_name', 150);
            // Contoh: Solo, Surabaya, Lampung

            $table->boolean('is_main_branch')->default(false);

            // =====================================================
            // OWNERSHIP (SALES YANG PEGANG CABANG INI SEKARANG)
            // =====================================================
            $table->unsignedBigInteger('assigned_to')->nullable()
                ->comment('Sales yang saat ini memegang cabang ini');

            $table->unsignedBigInteger('created_by');

            // =====================================================
            // STATUS
            // =====================================================
            $table->string('status', 50)->default('Active');
            // Active | Inactive

            // =====================================================
            // APPROVAL
            // =====================================================
            $table->enum('approval_status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            // Waktu cabang diajukan untuk approval
            $table->timestamp('submitted_for_approval_at')->nullable();

            // Manager yang melakukan approval
            $table->unsignedBigInteger('approved_by')->nullable();

            // Waktu approval
            $table->timestamp('approved_at')->nullable();

            // Catatan manager ketika reject
            $table->text('approval_note')->nullable();

            // Berapa kali cabang direvisi
            $table->unsignedTinyInteger('approval_revision')->default(0);

            // =====================================================
            // CONTACT INFO (opsional, override dari customer induk)
            // =====================================================
            $table->string('contact_name', 100)->nullable();

            $table->string('email', 100)->nullable();

            $table->string('phone', 20)->nullable();

            // =====================================================
            // INFORMATION
            // =====================================================
            $table->text('address')->nullable();

            $table->string('city', 100)->nullable();

            $table->text('notes')->nullable();

            // =====================================================
            // AUDIT
            // =====================================================
            $table->timestamps();

            $table->softDeletes();

            // =====================================================
            // INDEX
            // =====================================================

            $table->index('customer_id');

            $table->index('assigned_to');

            $table->index('created_by');

            $table->index('is_main_branch');

            $table->index('status');

            $table->index('approval_status');

            $table->index('approved_by');

            $table->index('submitted_for_approval_at');

            // =====================================================
            // FOREIGN KEY
            // =====================================================

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();

            $table->foreign('assigned_to')
                ->references('id_user')
                ->on('ms_users')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id_user')
                ->on('ms_users')
                ->cascadeOnDelete();

            $table->foreign('approved_by')
                ->references('id_user')
                ->on('ms_users')
                ->nullOnDelete();
        });

        // =====================================================
        // UNIQUE INDEX: branch_name UNIK PER CUSTOMER
        // ---------------------------------------------------
        // Beda dengan company_name di tabel customers (unik global),
        // branch_name di sini hanya perlu unik DALAM SATU customer_id.
        // Jadi "Cabang Bandung" milik PT A dan "Cabang Bandung" milik
        // PT B tetap boleh sama — yang tidak boleh adalah PT A punya
        // 2 cabang bernama "Cabang Bandung" sekaligus.
        //
        // - LOWER(TRIM(...)) => case-insensitive, anti spasi nyasar
        // - WHERE deleted_at IS NULL => branch yang sudah dihapus
        //   tidak dihitung sebagai duplikat
        // - composite (customer_id, branch_name) => scoping per customer
        // =====================================================
        DB::statement("
            CREATE UNIQUE INDEX customer_branches_name_unique_idx
            ON customer_branches (customer_id, LOWER(TRIM(branch_name)))
            WHERE deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_branches');
    }
};