<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->bigIncrements('id');

            // =====================
            // IDENTIFIER (PUBLIC)
            // =====================
            $table->string('follow_up_code', 30)->unique();

            // =====================
            // RELATION
            // =====================
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            // =====================
            // FOLLOW UP DETAIL
            // =====================
            $table->string('follow_up_type', 20);
            $table->string('subject')->nullable();
            $table->text('notes')->nullable();

            // =====================
            // SCHEDULE
            // =====================
            $table->timestamp('follow_up_at');

            // =====================
            // STATUS & RESULT
            // =====================
            $table->string('status', 50)->default('PENDING');
            $table->string('result', 50)->nullable();
            $table->timestamp('completed_at')->nullable();

            // =====================
            // AUTO CLOSE (SYSTEM)
            // =====================
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason', 100)->nullable();

            // =====================
            // USER
            // =====================
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by');

            // =====================
            // AUDIT
            // =====================
            $table->timestamps();
            $table->softDeletes();

            // =====================
            // FOREIGN KEY
            // =====================
            $table->foreign('lead_id')
                ->references('id')->on('leads')
                ->onDelete('cascade');

            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->onDelete('cascade');

            $table->foreign('assigned_to')
                ->references('id_user')->on('ms_users');

            $table->foreign('created_by')
                ->references('id_user')->on('ms_users');

            // =====================
            // INDEX
            // =====================
            $table->index('follow_up_code'); 
            $table->index('lead_id');
            $table->index('customer_id');
            $table->index('follow_up_at');
            $table->index('status');
            $table->index('result');
            $table->index('assigned_to');
            $table->index('closed_at');
        });

        /**
         * CHECK CONSTRAINTS (PostgreSQL)
         */

        DB::statement("
            ALTER TABLE follow_ups
            ADD CONSTRAINT chk_followups_owner
            CHECK (
                (lead_id IS NOT NULL AND customer_id IS NULL)
                OR
                (lead_id IS NULL AND customer_id IS NOT NULL)
            )
        ");

        DB::statement("
            ALTER TABLE follow_ups
            ADD CONSTRAINT chk_followups_type
            CHECK (follow_up_type IN (
                'CALL',
                'EMAIL',
                'WHATSAPP',
                'MEETING',
                'VISIT',
                'OTHER'
            ))
        ");

        DB::statement("
            ALTER TABLE follow_ups
            ADD CONSTRAINT chk_followups_status
            CHECK (status IN (
                'PENDING',
                'DONE',
                'CANCELLED',
                'CLOSED'
            ))
        ");

        DB::statement("
            ALTER TABLE follow_ups
            ADD CONSTRAINT chk_followups_result
            CHECK (
                result IS NULL OR result IN (
                    'NO_RESPONSE',
                    'STILL_CONSIDERING',
                    'INTERESTED',
                    'NOT_INTERESTED',
                    'DEAL'
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};