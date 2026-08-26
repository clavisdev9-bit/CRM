<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baris item (DESCRIPTION, QUANTITY, SATUAN, UNIT PRICE, TOTAL) per
 * quotation -- 1 quotation bisa punya BANYAK baris.
 *
 * odoo_product_id di sini adalah FK ke odoo_products.id (PK lokal tabel
 * hasil sync product dari Odoo, BUKAN kolom odoo_products.odoo_product_id
 * itu sendiri) -- opsional, dipakai buat auto-fill description/satuan/
 * unit_price pas milih product dari katalog, dan buat nentuin
 * product_id yang dikirim ke Odoo pas push sale.order.line (ambil dari
 * relasi odooProduct->odoo_product_id). Kalau kosong (null), berarti
 * baris ini item custom/ketik manual (BUKAN dari katalog product Odoo)
 * -- boleh tetap disimpan di CRM, tapi TIDAK BISA di-push ke Odoo
 * (sale.order.line normal WAJIB ada product_id) sampai sales pilih
 * product yang sesuai dari katalog.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('quotation_id');
            $table->foreign('quotation_id')->references('id')->on('quotations')->cascadeOnDelete();

            $table->unsignedBigInteger('odoo_product_id')->nullable();
            $table->foreign('odoo_product_id')->references('id')->on('odoo_products')->nullOnDelete();

            $table->text('description');  // DESCRIPTION (bisa multi-baris/spek teknis)
            $table->decimal('quantity', 15, 2);
            $table->string('unit');        // SATUAN
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2); // quantity * unit_price

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('quotation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};