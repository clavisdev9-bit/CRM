<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_assignment_histories', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // RELATION
            // =====================================================
            $table->unsignedBigInteger('branch_id');

            // =====================================================
            // ASSIGNMENT CHANGE
            // =====================================================
            $table->unsignedBigInteger('previous_sales_id')->nullable()
                ->comment('Null jika ini assignment pertama kali');

            $table->unsignedBigInteger('new_sales_id');

            // Manager yang melakukan pemindahan
            $table->unsignedBigInteger('changed_by');

            $table->text('reason')->nullable();

            $table->timestamp('changed_at');

            // =====================================================
            // AUDIT
            // =====================================================
            $table->timestamps();

            // =====================================================
            // INDEX
            // =====================================================

            $table->index('branch_id');

            $table->index('previous_sales_id');

            $table->index('new_sales_id');

            $table->index('changed_by');

            $table->index('changed_at');

            // =====================================================
            // FOREIGN KEY
            // =====================================================

            $table->foreign('branch_id')
                ->references('id')
                ->on('customer_branches')
                ->cascadeOnDelete();

            $table->foreign('previous_sales_id')
                ->references('id_user')
                ->on('ms_users')
                ->nullOnDelete();

            $table->foreign('new_sales_id')
                ->references('id_user')
                ->on('ms_users')
                ->cascadeOnDelete();

            $table->foreign('changed_by')
                ->references('id_user')
                ->on('ms_users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_assignment_histories');
    }
};