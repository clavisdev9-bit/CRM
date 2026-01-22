<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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
   $table->unsignedBigInteger('id_user')
                  ->comment('Lead owner (default owner)');
    $table->unsignedBigInteger('assigned_to')->nullable();
    $table->unsignedBigInteger('created_by');

    // =========================
    // STATUS
    // =========================
    $table->string('customer_status', 20)->default('Active');

      // VISIBILITY
      $table->string('visibility_type', 10)->default('PRIVATE');

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
    $table->string('lead_source', 30)->default('Website');
    $table->index('assigned_to');
    $table->index('customer_status');
      // New
      // Contacted
      // Qualified
      // Unqualified
      // Converted
      // Lost

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
