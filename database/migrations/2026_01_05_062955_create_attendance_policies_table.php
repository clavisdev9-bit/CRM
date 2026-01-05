<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();

            $table->string('policy_name', 100);

            $table->enum('applies_to', ['GLOBAL', 'OFFICE', 'USER'])
                  ->default('GLOBAL');

            $table->unsignedBigInteger('office_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->decimal('min_accuracy', 6, 2)
                  ->default(0)
                  ->comment('meter');

            $table->decimal('max_accuracy', 6, 2)
                  ->default(100)
                  ->comment('meter');

            $table->decimal('allowed_radius', 8, 2)
                  ->nullable()
                  ->comment('NULL = bebas lokasi (Sales)');

            $table->boolean('is_active')->default(true);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')
                  ->useCurrent()
                  ->useCurrentOnUpdate();

            $table->foreign('office_id')
                  ->references('id')
                  ->on('offices')
                  ->nullOnDelete();

            $table->foreign('user_id')
                  ->references('id_user')
                  ->on('ms_users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
