<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // =========================
            // DATA PROSPEK
            // =========================
            $table->string('company_name', 150);
            $table->string('contact_name', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();

            // =========================
            // SEGMENTASI
            // =========================
            $table->foreignId('lead_category_id')
                  ->nullable()
                  ->constrained('lead_categories')
                  ->nullOnDelete();

            $table->foreignId('industry_id')
                  ->nullable()
                  ->constrained('lead_industries')
                  ->nullOnDelete();

            // =========================
            // OWNERSHIP & ASSIGNMENT
            // =========================
            $table->unsignedBigInteger('id_user')
                  ->comment('Lead owner (default owner)');

            $table->unsignedBigInteger('assigned_to')
                  ->nullable()
                  ->comment('Sales currently handling this lead');

            $table->unsignedBigInteger('created_by')
                  ->comment('User who created the lead');

            // FK ke ms_users (PK = id_user)
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

            $table->string('visibility_type', 50)
                  ->default('PRIVATE');

            // =========================
            // STATUS & SOURCE
            // =========================
            $table->string('lead_source', 50)->default('Website');
            $table->string('lead_status', 50)->default('New');
            // New
            // Contacted
            // Qualified
            // Unqualified
            // Converted
            // Lost
            //Blacklist


            // =========================
            // ACTIVITY TRACKING
            // =========================
            $table->text('notes')->nullable();
            $table->text('address')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();

            // =========================
            // AUDIT
            // =========================
            $table->timestamps();
            $table->softDeletes();

            // =========================
            // INDEX
            // =========================
            $table->index('email');
            $table->index('lead_status');
            $table->index('visibility_type');
            $table->index('id_user');      
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
