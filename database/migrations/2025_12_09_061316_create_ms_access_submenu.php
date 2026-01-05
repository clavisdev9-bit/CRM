<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ms_access_submenu', function (Blueprint $table) {
            $table->unsignedBigInteger('id_access_submenu', true); 
            $table->unsignedBigInteger('id_user');                 
            $table->unsignedBigInteger('id_submenu');              
            
            // PERMISSIONS
            $table->boolean('can_view')->default(1);
            $table->boolean('can_create')->default(0);
            $table->boolean('can_update')->default(0);
            $table->boolean('can_delete')->default(0);

            $table->timestamps();
            $table->softDeletes(); 

            // FK (opsional)
            // $table->foreign('id_user')->references('id_user')->on('ms_users')->onDelete('cascade');
            // $table->foreign('id_submenu')->references('id_submenu')->on('ms_submenu')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_access_submenu');
    }
};
