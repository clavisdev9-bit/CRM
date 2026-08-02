<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odoo_customers', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('odoo_partner_id')->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_company')->default(false);
            $table->unsignedInteger('company_id')->nullable();

            $table->boolean('has_purchased')->default(false);
            $table->unsignedInteger('total_transaksi')->default(0);

            $table->timestamps();

            $table->index('company_id');
            $table->index('has_purchased');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_customers');
    }
};