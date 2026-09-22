<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel ini menyimpan file PDF & video milik sebuah produk.
     * 1 produk bisa punya banyak PDF dan banyak video sekaligus.
     */
    public function up(): void
    {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products_catalog')
                ->cascadeOnDelete();

            $table->enum('media_type', ['pdf', 'video']);
            $table->enum('source_type', ['youtube', 'vimeo', 'upload', 'external_link']);

            $table->string('url', 500);
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['product_id', 'media_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogs');
    }
};