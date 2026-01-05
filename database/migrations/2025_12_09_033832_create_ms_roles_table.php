<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ms_role', function (Blueprint $table) {
            $table->id('id_role');       // id_role auto increment
            $table->string('role');      // nama role
            $table->string('description')->nullable(); // deskripsi role
            $table->timestamps();        // created_at & updated_at
            $table->softDeletes();       // deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_role');
    }
};
