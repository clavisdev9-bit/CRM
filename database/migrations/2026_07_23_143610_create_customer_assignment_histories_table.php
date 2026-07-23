<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_assignment_histories', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // RELATION
            // =====================================================
            $table->unsignedBigInteger('customer_id');

            // Sales yang PEGANG customer SEBELUM dipindah.
            // Nullable karena bisa saja customer belum pernah
            // di-assign sebelumnya (assigned_to masih null).
            $table->unsignedBigInteger('previous_sales_id')->nullable();

            // Sales yang PEGANG customer SETELAH dipindah.
            $table->unsignedBigInteger('new_sales_id');

            // Manager/user yang melakukan aksi reassignment.
            $table->unsignedBigInteger('changed_by');

            $table->text('reason')->nullable();

            $table->timestamp('changed_at');

            $table->timestamps();

            // =====================================================
            // INDEX
            // =====================================================
            $table->index('customer_id');
            $table->index('previous_sales_id');
            $table->index('new_sales_id');
            $table->index('changed_by');

            // =====================================================
            // FOREIGN KEY
            // =====================================================
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
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
        Schema::dropIfExists('customer_assignment_histories');
    }
};