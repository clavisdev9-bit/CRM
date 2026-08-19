<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sales_id')->comment('ms_users.id_user -- sales yang dikasih target');
            $table->unsignedSmallInteger('period_year')->comment('Tahun target, misal 2026');

            // NULL = target TOTAL sales itu (semua customer digabung).
            // Diisi = target KHUSUS ke 1 customer Odoo tertentu (opsional,
            // ga wajib per customer -- lihat catatan di SalesTargetController).
            // Referensinya ke odoo_customers.odoo_partner_id (BUKAN ke tabel
            // customers CRM biasa), karena "customer" di konteks fitur ini
            // adalah customer hasil sync Odoo yang datanya dipakai buat
            // hitung realisasi (odoo_customer_purchase_items).
            $table->unsignedInteger('odoo_customer_id')->nullable();

            $table->decimal('target_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->comment('ms_users.id_user -- manager/admin yang input');

            $table->timestamps();
            $table->softDeletes();

            $table->index('sales_id');
            $table->index('period_year');
            $table->index('odoo_customer_id');

            $table->foreign('sales_id')->references('id_user')->on('ms_users')->cascadeOnDelete();
            $table->foreign('created_by')->references('id_user')->on('ms_users')->cascadeOnDelete();
            $table->foreign('odoo_customer_id')->references('odoo_partner_id')->on('odoo_customers')->nullOnDelete();
        });

        // Postgres partial unique index -- karena NULL dianggap "beda-beda"
        // sama Postgres (bukan dianggap sama kayak nilai lain), unique index
        // biasa GA CUKUP buat cegah 2 baris "target total" (odoo_customer_id
        // IS NULL) dobel buat sales+tahun yang sama. Makanya dipecah jadi
        // 2 partial index terpisah:
        DB::statement("
            CREATE UNIQUE INDEX sales_targets_total_unique
            ON sales_targets (sales_id, period_year)
            WHERE odoo_customer_id IS NULL AND deleted_at IS NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX sales_targets_per_customer_unique
            ON sales_targets (sales_id, period_year, odoo_customer_id)
            WHERE odoo_customer_id IS NOT NULL AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};