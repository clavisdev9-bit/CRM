<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ==========================================================================
 * TARGET PENJUALAN -- breakdown tambahan by BRAND & by KATEGORI
 * --------------------------------------------------------------------------
 * Sebelum migration ini, sales_targets cuma bisa dipecah 2 cara: TOTAL
 * (semua kolom dimensi NULL) atau per CUSTOMER (odoo_customer_id diisi).
 * Sekarang ditambah 2 cara lagi, referensinya dari odoo_products (sudah ada
 * dari fitur Product Population, hasil sync command odoo:sync-products):
 *
 * - BRAND    -> odoo_product_id diisi, referensi ke satu product Odoo
 *   tertentu. Di project ini "Brand" itu maksudnya PRODUCT itu sendiri,
 *   cuma beda penamaan bisnis -- bukan field baru/terpisah di Odoo.
 * - KATEGORI -> categ_id (+ categ_name didenormalisasi) diisi, referensi ke
 *   product.category Odoo (kolom odoo_products.categ_id/categ_name yang
 *   sudah disync). categ_id SENGAJA TIDAK dikasih foreign key ke
 *   odoo_products.categ_id (bukan primary/unique key di situ -- satu
 *   categ_id dipunyai banyak product), makanya categ_name juga
 *   didenormalisasi langsung ke sales_targets biar ga perlu join balik ke
 *   odoo_products cuma buat nampilin nama kategorinya.
 *
 * ATURAN: 1 baris sales_targets cuma boleh isi SALAH SATU dari
 * odoo_customer_id / odoo_product_id / categ_id (atau kosong semua = TOTAL)
 * -- dijaga di 2 level:
 *   1) CHECK constraint chk_sales_targets_single_dimension di bawah.
 *   2) Validasi tambahan di SalesTargetValidationStore::withValidator()
 *      (biar pesan error-nya jelas ketimbang nunggu DB reject).
 *
 * 4 partial unique index (sebelumnya cuma 2) supaya tiap kombinasi
 * sales+tahun+dimensi cuma boleh ada 1 baris aktif:
 *   - total       : sales_id + period_year, WHERE semua dimensi NULL
 *                   (index lama di-DROP & dibuat ULANG dengan predicate
 *                   baru, karena "total" sekarang artinya SEMUA dimensi
 *                   kosong, bukan cuma odoo_customer_id doang)
 *   - per customer : sales_id + period_year + odoo_customer_id (TETAP)
 *   - per brand    : sales_id + period_year + odoo_product_id (BARU)
 *   - per kategori : sales_id + period_year + categ_id (BARU)
 * ==========================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_targets', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_product_id')->nullable()->after('odoo_customer_id')
                ->comment('odoo_products.odoo_product_id -- diisi kalau target ini per BRAND (= per product)');
            $table->unsignedBigInteger('categ_id')->nullable()->after('odoo_product_id')
                ->comment('odoo_products.categ_id -- diisi kalau target ini per KATEGORI');
            $table->string('categ_name')->nullable()->after('categ_id')
                ->comment('Denormalisasi nama kategori pas target dibuat, biar ga perlu join balik ke odoo_products');

            $table->index('odoo_product_id');
            $table->index('categ_id');

            $table->foreign('odoo_product_id')->references('odoo_product_id')->on('odoo_products')->nullOnDelete();
        });

        // Ganti definisi index "total" lama (cuma cek odoo_customer_id) jadi
        // versi baru yang juga mensyaratkan odoo_product_id & categ_id NULL.
        DB::statement('DROP INDEX IF EXISTS sales_targets_total_unique');

        DB::statement("
            CREATE UNIQUE INDEX sales_targets_total_unique
            ON sales_targets (sales_id, period_year)
            WHERE odoo_customer_id IS NULL
              AND odoo_product_id IS NULL
              AND categ_id IS NULL
              AND deleted_at IS NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX sales_targets_per_brand_unique
            ON sales_targets (sales_id, period_year, odoo_product_id)
            WHERE odoo_product_id IS NOT NULL AND deleted_at IS NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX sales_targets_per_category_unique
            ON sales_targets (sales_id, period_year, categ_id)
            WHERE categ_id IS NOT NULL AND deleted_at IS NULL
        ");

        // CHECK constraint: maksimal 1 dari 3 kolom dimensi yang boleh keisi
        // dalam 1 baris (0 keisi = TOTAL, itu valid juga).
        DB::statement("
            ALTER TABLE sales_targets
            ADD CONSTRAINT chk_sales_targets_single_dimension
            CHECK (
                (CASE WHEN odoo_customer_id IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN odoo_product_id IS NOT NULL THEN 1 ELSE 0 END) +
                (CASE WHEN categ_id IS NOT NULL THEN 1 ELSE 0 END)
                <= 1
            )
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sales_targets DROP CONSTRAINT IF EXISTS chk_sales_targets_single_dimension');
        DB::statement('DROP INDEX IF EXISTS sales_targets_per_category_unique');
        DB::statement('DROP INDEX IF EXISTS sales_targets_per_brand_unique');
        DB::statement('DROP INDEX IF EXISTS sales_targets_total_unique');

        Schema::table('sales_targets', function (Blueprint $table) {
            $table->dropForeign(['odoo_product_id']);
            $table->dropIndex(['odoo_product_id']);
            $table->dropIndex(['categ_id']);
            $table->dropColumn(['odoo_product_id', 'categ_id', 'categ_name']);
        });

        // kembalikan index total versi lama (cuma cek odoo_customer_id)
        DB::statement("
            CREATE UNIQUE INDEX sales_targets_total_unique
            ON sales_targets (sales_id, period_year)
            WHERE odoo_customer_id IS NULL AND deleted_at IS NULL
        ");
    }
};