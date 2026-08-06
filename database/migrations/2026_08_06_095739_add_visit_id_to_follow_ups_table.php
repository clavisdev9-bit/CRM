<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->unsignedBigInteger('visit_id')
                ->nullable()
                ->after('branch_id')
                ->comment('Visit asal follow up ini dibuat (kalau ada)');

            $table->foreign('visit_id')
                ->references('id')
                ->on('visits')
                ->nullOnDelete();

            $table->index('visit_id');
        });
    }

    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropForeign(['visit_id']);
            $table->dropIndex(['visit_id']);
            $table->dropColumn('visit_id');
        });
    }
};