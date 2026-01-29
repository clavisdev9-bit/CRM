<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->bigIncrements('id');

            // KODE KUNJUNGAN
          $table->string('visit_code', 30)
          ->unique()
          ->comment('Unique visit code (VIS-YYYYMM-XXXXX)');

            // =========================
            // RELATION (LEAD / CUSTOMER)
            // =========================
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            // =========================
            // SALES
            // =========================
            $table->unsignedBigInteger('sales_id')
                  ->comment('Sales who perform the visit');

            // =========================
            // VISIT TIME
            // =========================
            $table->timestamp('visit_at')
                  ->comment('Visit date');

            $table->timestamp('check_in_at')
                  ->nullable()
                  ->comment('Check-in time');

            $table->timestamp('check_out_at')
                  ->nullable()
                  ->comment('Check-out time');

            // =========================
            // LOCATION (GPS)
            // =========================
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('gps_snapshot')->nullable()
                  ->comment('GPS proof / snapshot');

            // =========================
            // DOCUMENTATION
            // =========================
            $table->string('photo')->nullable()
                  ->comment('Visit documentation photo');

            $table->text('notes')->nullable();

            // =========================
            // VISIT RESULT
            // =========================
            $table->string('visit_result', 50)
                  ->comment('Result of visit');

            /*
              PROSPECTIVE
              CONSIDERATION
              POTENTIAL
              CONVERTED
              FAILED
            */

            $table->text('customer_response')->nullable();

            // =========================
            // AUDIT
            // =========================
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            // =========================
            // INDEX
            // =========================
            $table->index('lead_id');
            $table->index('customer_id');
            $table->index('sales_id');
            $table->index('visit_at');
            $table->index('visit_result');

            // =========================
            // FOREIGN KEY
            // =========================
            $table->foreign('lead_id')
                  ->references('id')->on('leads')
                  ->nullOnDelete();

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->nullOnDelete();

            $table->foreign('sales_id')
                  ->references('id_user')->on('ms_users')
                  ->cascadeOnDelete();

            $table->foreign('created_by')
                  ->references('id_user')->on('ms_users')
                  ->cascadeOnDelete();
        });

        // =========================
        // CHECK CONSTRAINTS (POSTGRESQL)
        // =========================

        // visit hanya boleh ke lead ATAU customer
        DB::statement("
            ALTER TABLE visits
            ADD CONSTRAINT chk_visits_owner
            CHECK (
                (lead_id IS NOT NULL AND customer_id IS NULL)
                OR
                (lead_id IS NULL AND customer_id IS NOT NULL)
            )
        ");

        // valid visit result
        DB::statement("
            ALTER TABLE visits
            ADD CONSTRAINT chk_visits_result
            CHECK (visit_result IN (
                'prospective_customers',
                'consideration_stage',
                'potential_customers',
                'convert_to_customer',
                'failed'
            ))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
