<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            // =====================
            // EXTERNAL REFERENCE (ERP)
            // =====================
            // Diisi manual oleh sales, referensi ke nomor
            // transaksi/dokumen di sistem ERP supaya bisa di-track.
            $table->string('no_reference', 50)
                ->nullable()
                ->after('follow_up_code');

            $table->index('no_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropIndex(['no_reference']);
            $table->dropColumn('no_reference');
        });
    }
};