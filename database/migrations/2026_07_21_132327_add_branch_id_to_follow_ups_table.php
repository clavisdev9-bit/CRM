<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {

            // nullable: null = follow up untuk head office,
            // terisi = follow up untuk branch tertentu (customer_id
            // tetap diisi sebagai referensi head company induknya)
            $table->unsignedBigInteger('branch_id')
                ->nullable()
                ->after('customer_id');

            $table->foreign('branch_id')
                ->references('id')
                ->on('customer_branches')
                ->nullOnDelete();

            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};