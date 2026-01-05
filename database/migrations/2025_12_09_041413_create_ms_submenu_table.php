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
        Schema::create('ms_submenu', function (Blueprint $table) {
            $table->unsignedBigInteger('id_submenu', true); // auto increment
            $table->unsignedBigInteger('id_menu');          // relasi ke menu
            $table->string('url');
            $table->string('icon')->nullable();
            $table->string('title');
            $table->string('noted')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('parent_id')->nullable(); // untuk submenu anak
            $table->timestamps();   // created_at & updated_at
            $table->softDeletes();  // deleted_at

            // Relasi foreign key ke menu (opsional tapi direkomendasikan)
            // $table->foreign('id_menu')->references('id_menu')->on('ms_menu')->onDelete('cascade');
            // $table->foreign('parent_id')->references('id_submenu')->on('ms_menu')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ms_submenu');
    }
};
