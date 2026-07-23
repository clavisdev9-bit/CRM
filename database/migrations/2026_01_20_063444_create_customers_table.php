<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // RELATION
            // =====================================================
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('lead_category_id')->nullable();
            $table->unsignedBigInteger('industry_id')->nullable();

            // =====================================================
            // CUSTOMER IDENTITY
            // =====================================================
            $table->string('customer_code', 30)->unique();

            $table->string('company_name', 150);

            $table->string('contact_name', 100)->nullable();

            $table->string('email', 100)->nullable();

            $table->string('phone', 20)->nullable();

            // =====================================================
            // OWNERSHIP
            // =====================================================
            $table->unsignedBigInteger('id_user')
                ->comment('Customer Owner');

            $table->unsignedBigInteger('assigned_to')->nullable();

            $table->unsignedBigInteger('created_by');

            // =====================================================
            // STATUS & VISIBILITY
            // =====================================================
            $table->string('customer_status', 50)
                ->default('Active');
            // Active | Dormant | Inactive | Lost | Blacklist

            $table->string('visibility_type', 50)
                ->default('PRIVATE');
            // PRIVATE | PUBLIC

            // =====================================================
            // APPROVAL
            // =====================================================
            $table->enum('approval_status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            // Waktu customer diajukan untuk approval
            $table->timestamp('submitted_for_approval_at')->nullable();

            // Manager yang melakukan approval
            $table->unsignedBigInteger('approved_by')->nullable();

            // Waktu approval
            $table->timestamp('approved_at')->nullable();

            // Catatan manager ketika reject
            $table->text('approval_note')->nullable();

            // Berapa kali customer direvisi
            $table->unsignedTinyInteger('approval_revision')
                ->default(0);

            // =====================================================
            // CLASSIFICATION
            // =====================================================
            $table->string('lead_source', 50)->nullable();
            // Cold Call
            // Website
            // Referral
            // Social Media
            // Email Campaign
            // Event
            // Partner
            // Ads
            // Other

            // =====================================================
            // INFORMATION
            // =====================================================
            $table->text('address')->nullable();

            $table->text('notes')->nullable();

            // =====================================================
            // ACTIVITY
            // =====================================================
            $table->timestamp('converted_at')->nullable();

            // =====================================================
            // AUDIT
            // =====================================================
            $table->timestamps();

            $table->softDeletes();

            // =====================================================
            // INDEX
            // =====================================================

            $table->index('lead_id');

            $table->index('lead_category_id');

            $table->index('industry_id');

            $table->index('id_user');

            $table->index('assigned_to');

            $table->index('created_by');

            $table->index('customer_status');

            $table->index('visibility_type');

            $table->index('lead_source');

            $table->index('approval_status');

            $table->index('approved_by');

            $table->index('submitted_for_approval_at');

            // =====================================================
            // FOREIGN KEY
            // =====================================================

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();

            $table->foreign('lead_category_id')
                ->references('id')
                ->on('lead_categories')
                ->nullOnDelete();

            $table->foreign('industry_id')
                ->references('id')
                ->on('lead_industries')
                ->nullOnDelete();

            $table->foreign('id_user')
                ->references('id_user')
                ->on('ms_users')
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
        // UNIQUE INDEX: company_name (case-insensitive, exact match)
        // ---------------------------------------------------
        // Dibuat manual pakai raw SQL karena butuh:
        // - LOWER(TRIM(...)) supaya "PT Maju" & "pt maju " dianggap sama
        // - Partial index (WHERE deleted_at IS NULL) supaya company_name
        //   yang sudah di-soft-delete tidak ikut dihitung sebagai duplikat
        // Laravel's $table->unique() tidak mendukung kedua hal ini,
        // jadi harus di luar Schema::create.
        // =====================================================
        DB::statement("
            CREATE UNIQUE INDEX customers_company_name_unique_idx
            ON customers (LOWER(TRIM(company_name)))
            WHERE deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};