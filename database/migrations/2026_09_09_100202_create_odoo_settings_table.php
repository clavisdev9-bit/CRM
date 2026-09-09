<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel config koneksi Odoo GLOBAL -- menggantikan pembacaan langsung
 * dari .env (config/odoo.php) supaya kredensial bisa diubah lewat menu
 * Odoo Settings tanpa perlu edit file server / restart.
 *
 * Sengaja dibuat SATU baris saja (bukan per company) -- semua company
 * CRM memang connect ke satu instance Odoo yang sama (satu url/db/
 * username/api_key). Yang beda per company cuma company_id di sisi
 * Odoo (lihat migration add_odoo_company_id_to_group_companies_table
 * dan OdooService::companyIdFor()).
 *
 * Baris pertama di-seed langsung dari config('odoo.*') (nilai .env yang
 * sudah ada sekarang) supaya OdooService tetap jalan seperti biasa
 * begitu migration ini dijalankan -- admin tidak perlu buru-buru isi
 * ulang lewat UI sebelum sistem bisa dipakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odoo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('url')->nullable();
            $table->string('db')->nullable();
            $table->string('username')->nullable();
            $table->text('api_key')->nullable();
            $table->unsignedBigInteger('default_company_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('odoo_settings')->insert([
            'url'                => config('odoo.url'),
            'db'                 => config('odoo.db'),
            'username'           => config('odoo.username'),
            'api_key'            => config('odoo.api_key'),
            'default_company_id' => config('odoo.default_company_id'),
            'updated_by'         => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_settings');
    }
};