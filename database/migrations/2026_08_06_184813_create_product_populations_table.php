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
        Schema::create('product_populations', function (Blueprint $table) {
            $table->id();

            // Relasi (sementara tanpa foreign key)
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            // Informasi Pump & Product
            $table->string('pump_serial_no');
            $table->string('product_category');
            $table->text('product_display')->nullable();
            $table->string('product_model');

            // Informasi Tag & Quantity
            $table->string('tag_no')->nullable();
            $table->unsignedInteger('qty')->default(0);

            // Informasi Seal
            $table->text('seal_plan')->nullable();
            $table->string('mechanical_seal_drawing_no')->nullable();

            $table->timestamps();

            // Index (belum foreign key)
            $table->index('customer_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_populations');
    }
};