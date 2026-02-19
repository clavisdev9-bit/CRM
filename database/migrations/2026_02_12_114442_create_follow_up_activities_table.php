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
       Schema::create('follow_up_activities', function (Blueprint $table) {
    $table->id();

    $table->foreignId('follow_up_id')
        ->constrained('follow_ups')
        ->cascadeOnDelete();

    $table->string('activity_type'); 
    // CREATED | CONTACTED | MEETING | REMINDER | COMPLETED | FAILED | RESCHEDULED

    $table->string('title'); 
    // text pendek untuk timeline

    $table->text('description')->nullable();

    $table->timestamp('activity_at'); // kapan kejadian

    $table->timestamp('scheduled_for')->nullable(); // untuk reminder atau jadwal follow up berikutnya

  $table->foreign('created_by')
        ->references('id_user')   // ← ini yang benar
        ->on('ms_users')
        ->nullOnDelete();  
        
    $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_activities');
    }
};
