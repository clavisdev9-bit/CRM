<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odoo_customer_purchase_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('odoo_order_line_id')->unique();

            $table->unsignedInteger('odoo_order_id');
            $table->string('order_name')->nullable();
            $table->date('order_date')->nullable();

            $table->unsignedInteger('odoo_customer_id');

            $table->unsignedInteger('odoo_product_id');
            $table->string('product_name')->nullable();
            $table->string('product_code')->nullable();

            $table->decimal('qty', 12, 2)->default(0);
            $table->decimal('price_unit', 15, 2)->default(0);

            $table->unsignedInteger('company_id')->nullable();

            $table->timestamps();

            $table->index('odoo_customer_id');
            $table->index('odoo_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_purchase_items');
    }
};