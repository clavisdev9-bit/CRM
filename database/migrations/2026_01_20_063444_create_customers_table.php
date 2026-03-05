<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // =========================
            // RELATION
            // =========================
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('lead_category_id')->nullable();
            $table->unsignedBigInteger('industry_id')->nullable();

            // =========================
            // CUSTOMER IDENTITY
            // =========================
            $table->string('customer_code', 30)->unique();
            $table->string('company_name', 150);
            $table->string('contact_name', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();

            // =========================
            // OWNERSHIP
            // =========================
            $table->unsignedBigInteger('id_user')->comment('Customer owner');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by');

            // =========================
            // STATUS & VISIBILITY
            // =========================
            $table->string('customer_status', 50)->default('Active');
            // Active | Dormant | Inactive | Lost | Blacklist

            $table->string('visibility_type', 50)->default('PRIVATE');
            // PRIVATE | PUBLIC

            // =========================
            // CLASSIFICATION
            // =========================
            $table->string('lead_source', 50)->nullable();
            // Cold Call | Website | Referral | Social Media |
            // Email Campaign | Event | Partner | Ads | Other

            // =========================
            // INFO
            // =========================
            $table->text('address')->nullable();
            $table->text('notes')->nullable();

            // =========================
            // ACTIVITY
            // =========================
            $table->timestamp('converted_at')->nullable();

            // =========================
            // AUDIT
            // =========================
            $table->timestamps();
            $table->softDeletes();

            // =========================
            // INDEX
            // =========================
            $table->index('lead_id');
            $table->index('lead_category_id');
            $table->index('industry_id');
            $table->index('assigned_to');
            $table->index('customer_status');
            $table->index('lead_source');

            // =========================
            // FOREIGN KEY
            // =========================
            $table->foreign('lead_id')
                  ->references('id')->on('leads')
                  ->nullOnDelete();

            $table->foreign('lead_category_id')
                  ->references('id')->on('lead_categories')
                  ->nullOnDelete();

            $table->foreign('industry_id')
                  ->references('id')->on('lead_industries')
                  ->nullOnDelete();

            $table->foreign('assigned_to')
                  ->references('id_user')->on('ms_users')
                  ->nullOnDelete();

            $table->foreign('created_by')
                  ->references('id_user')->on('ms_users')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};