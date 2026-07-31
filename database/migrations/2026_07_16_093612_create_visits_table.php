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

            // NO. REFERENCE (CHECK OUT)
            $table->string('no_reference', 100)
                ->nullable()
                ->comment('Manual unique reference number entered by sales');
                // ->unique()

            // =========================
            // RELATION (LEAD / CUSTOMER / BRANCH)
            // =========================
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            // branch_id: null = visit ke head office,
            // terisi = visit ke branch tertentu (customer_id tetap
            // diisi sebagai referensi head company induknya)
            $table->unsignedBigInteger('branch_id')->nullable();

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

            // FILE UPLOAD SAAT CHECK OUT
            $table->string('check_out_file')->nullable()
                  ->comment('File path uploaded at check-out');

            $table->text('notes')->nullable();

            // =========================
            // VISIT RESULT
            // =========================
            $table->string('visit_result', 50)
                  ->nullable()
                  ->comment('Result of visit: lead result OR customer result');

            $table->string('visit_status', 20)
                  ->default('ONGOING')
                  ->comment('ONGOING, CHECKED_IN, DONE, CANCELED');

            // =========================
            // CUSTOMER RESPONSE
            // =========================
            $table->text('customer_response')
                  ->nullable()
                  ->comment('Response/activity during visit');

            // =========================
            // COMPLAINT
            // =========================
            $table->boolean('has_complaint')
                  ->default(false)
                  ->comment('Flag if customer has complaint');

            $table->text('complaint_detail')
                  ->nullable()
                  ->comment('Detail of complaint from customer');

            // =========================
            // POTENTIAL ORDER / UPSELL
            // =========================
            $table->boolean('has_potential_order')
                  ->default(false)
                  ->comment('Flag if there is potential order or upsell');

            $table->text('potential_order_detail')
                  ->nullable()
                  ->comment('Detail of potential order or upsell');

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
            $table->index('branch_id');
            $table->index('sales_id');
            $table->index('visit_at');
            $table->index('visit_result');
            $table->index('has_complaint');
            $table->index('has_potential_order');

            // =========================
            // FOREIGN KEY
            // =========================
            $table->foreign('lead_id')
                  ->references('id')->on('leads')
                  ->nullOnDelete();

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->nullOnDelete();

            $table->foreign('branch_id')
                  ->references('id')->on('customer_branches')
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

        // branch_id hanya boleh terisi kalau visit ke customer (bukan lead)
        DB::statement("
            ALTER TABLE visits
            ADD CONSTRAINT chk_visits_branch
            CHECK (
                branch_id IS NULL
                OR
                (branch_id IS NOT NULL AND customer_id IS NOT NULL)
            )
        ");

        // visit result untuk LEAD
        DB::statement("
            ALTER TABLE visits
            ADD CONSTRAINT chk_visits_result_lead
            CHECK (
                customer_id IS NOT NULL
                OR
                visit_result IS NULL
                OR visit_result IN (
                    'prospective_customers',
                    'consideration_stage',
                    'potential_customers',
                    'convert_to_customer',
                    'failed'
                )
            )
        ");

        // visit result untuk CUSTOMER
        DB::statement("
            ALTER TABLE visits
            ADD CONSTRAINT chk_visits_result_customer
            CHECK (
                lead_id IS NOT NULL
                OR
                visit_result IS NULL
                OR visit_result IN (
                    'maintained',
                    'improved',
                    'at_risk',
                    'complaint_handled',
                    'upsell_identified',
                    'renewal_discussed',
                    'no_progress'
                )
            )
        ");

        // visit status
        DB::statement("
            ALTER TABLE visits
            ADD CONSTRAINT chk_visits_status
            CHECK (
                visit_status IN ('ONGOING', 'CHECKED_IN', 'DONE', 'CANCELED')
            )
        ");

        // complaint detail wajib jika has_complaint = true
        DB::statement("
            ALTER TABLE visits
            ADD CONSTRAINT chk_visits_complaint
            CHECK (
                (has_complaint = false)
                OR
                (has_complaint = true AND complaint_detail IS NOT NULL)
            )
        ");

        // potential order detail wajib jika has_potential_order = true
        DB::statement("
            ALTER TABLE visits
            ADD CONSTRAINT chk_visits_potential_order
            CHECK (
                (has_potential_order = false)
                OR
                (has_potential_order = true AND potential_order_detail IS NOT NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};