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
        Schema::create('ms_division', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true); // auto increment
            $table->string('name_division');                 // nama divisi
            $table->string('kode_division');                 // kode divisi
            $table->text('description_division')->nullable(); // deskripsi divisi
            $table->timestamps();                   // created_at & updated_at
            $table->softDeletes();                  // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ms_division');
    }
};
