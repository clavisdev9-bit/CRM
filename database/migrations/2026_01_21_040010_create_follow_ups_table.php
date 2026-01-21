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

            // RELATION
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            // FOLLOW UP DETAIL
            $table->string('follow_up_type', 20);
            $table->string('subject')->nullable();
            $table->text('notes')->nullable();

            // SCHEDULE
            $table->timestamp('follow_up_at');

            // STATUS
            $table->string('status', 20)->default('PENDING');

            // USER
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by');

            // AUDIT
            $table->timestamps();
            $table->softDeletes();

            // FOREIGN KEY
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

            // INDEX
            $table->index('lead_id');
            $table->index('customer_id');
            $table->index('follow_up_at');
            $table->index('status');
            $table->index('assigned_to');
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
                'CANCELLED'
            ))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
