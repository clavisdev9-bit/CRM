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
        Schema::create('group_companies', function (Blueprint $table) {
            $table->unsignedBigInteger('id_group', true); // auto increment
            $table->string('name_group');                 // nama group
            $table->string('description_group')->nullable();    // deskripsi company
            $table->boolean('is_active')->default(1);     // aktif / nonaktif
            $table->timestamps();                         // created_at & updated_at
            $table->softDeletes();                        // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_companies');
    }
};
