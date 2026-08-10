<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_sales_assignments_odoo', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('odoo_customer_id')->unique(); // 1 customer = 1 sales aktif
            $table->unsignedBigInteger('sales_id');

            $table->timestamp('assigned_at')->nullable();

            $table->timestamps();

            $table->index('sales_id');

            $table->foreign('sales_id')
                  ->references('id_user')->on('ms_users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_sales_assignments_odoo');
    }
};