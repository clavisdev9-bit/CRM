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
        Schema::create('ms_menu', function (Blueprint $table) {
            $table->unsignedBigInteger('id_menu', true); // auto increment
            $table->string('menu');                       // nama menu
            $table->timestamps();                         // created_at & updated_at
            $table->softDeletes();                        // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ms_menu');
    }
};
