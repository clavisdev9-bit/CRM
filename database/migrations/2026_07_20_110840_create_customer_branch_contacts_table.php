<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_contacts', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // RELATION
            // =====================================================
            $table->unsignedBigInteger('branch_id');

            // =====================================================
            // CONTACT IDENTITY
            // =====================================================
            $table->string('name', 100);

            $table->string('position', 100)->nullable();
            // Contoh: Purchasing Manager, Finance, Owner, dst

            $table->string('email', 100)->nullable();

            $table->string('phone', 20)->nullable();

            // =====================================================
            // FLAGS
            // =====================================================
            $table->boolean('is_primary')->default(false)
                ->comment('Kontak utama branch, hanya boleh 1 per branch');

            $table->string('status', 50)->default('Active');
            // Active | Inactive

            // =====================================================
            // INFORMATION
            // =====================================================
            $table->text('notes')->nullable();

            // =====================================================
            // OWNERSHIP
            // =====================================================
            $table->unsignedBigInteger('created_by');

            // =====================================================
            // AUDIT
            // =====================================================
            $table->timestamps();

            $table->softDeletes();

            // =====================================================
            // INDEX
            // =====================================================

            $table->index('branch_id');

            $table->index('is_primary');

            $table->index('status');

            $table->index('created_by');

            // =====================================================
            // FOREIGN KEY
            // =====================================================

            $table->foreign('branch_id')
                ->references('id')
                ->on('customer_branches')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id_user')
                ->on('ms_users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_contacts');
    }
};