<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mencatat setiap kali user (admin/sales) mengirim katalog (PDF)
     * ke email tertentu. Field product_name & catalog_title disimpan
     * sebagai snapshot supaya histori tetap terbaca meski produk/
     * catalog aslinya sudah diubah atau dihapus.
     */
    public function up(): void
    {
        Schema::create('catalog_send_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_id')
                ->nullable()
                ->constrained('catalogs')
                ->nullOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products_catalog')
                ->nullOnDelete();

            $table->foreignId('sender_id')
                ->nullable()
                ->constrained('ms_users', 'id_user')
                ->nullOnDelete();

            $table->string('sender_name')->nullable();
            $table->string('sender_email');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email');

            // Snapshot, supaya histori tetap utuh walau produk/catalog berubah
            $table->string('product_name')->nullable();
            $table->string('catalog_title')->nullable();

            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['recipient_email']);
            $table->index(['sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_send_logs');
    }
};