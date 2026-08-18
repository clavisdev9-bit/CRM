<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odoo_products', function (Blueprint $table) {
            $table->bigIncrements('id');

            // ID record product.product di Odoo -- dipakai updateOrCreate()
            // sebagai key unik pas sync (persis pola odoo_partner_id di
            // OdooCustomer/SyncOdooCustomers yang udah ada).
            $table->unsignedBigInteger('odoo_product_id')->unique();

            $table->string('name');
            $table->string('default_code', 100)->nullable()->comment('SKU / kode product di Odoo');
            $table->string('barcode', 100)->nullable();

            // categ_id/uom_id dari Odoo balik sebagai tuple [id, name].
            // id-nya disimpan buat referensi, name-nya didenormalisasi
            // langsung ke kolom terpisah biar list/search di frontend ga
            // perlu join balik ke Odoo tiap kali nampilin data.
            $table->unsignedBigInteger('categ_id')->nullable();
            $table->string('categ_name')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->string('uom_name', 50)->nullable();

            $table->decimal('list_price', 15, 2)->default(0)->comment('Harga jual');
            $table->decimal('standard_price', 15, 2)->default(0)->comment('Harga pokok / cost');
            $table->decimal('qty_available', 15, 2)->default(0)->comment('Stok tersedia di Odoo');

            // flag 'active' punya Odoo sendiri (soft-disable product di
            // Odoo) -- sync cuma ambil yang active=true dari Odoo, tapi
            // kolom ini tetap disimpan biar konsisten & bisa dipakai buat
            // filter di query lokal juga.
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index('name');
            $table->index('default_code');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_products');
    }
};