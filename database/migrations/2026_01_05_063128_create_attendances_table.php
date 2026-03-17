<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // ===== RELATION =====
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('employee_id');

            // ===== MODE =====
            $table->enum('attendance_mode', ['OFFICE', 'FREE', 'WFH', 'HYBRID']);

            // ===== ATTENDANCE CORE =====
            $table->enum('attendance_type', ['IN', 'OUT']);
            $table->dateTime('attendance_datetime');
            $table->date('attendance_date');
            $table->time('attendance_time');

            // ===== EVIDENCE =====
            $table->string('photo_path', 255);

            // ===== LOCATION =====
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 6, 2)->comment('meter');
            $table->text('location_name');

            // ===== VALIDATION RESULT =====
            $table->enum(
                'accuracy_status',
                ['HIGH', 'MEDIUM', 'LOW', 'IGNORED']
            )->nullable();

            $table->enum(
                'policy_status',
                ['ALLOWED', 'WARNING', 'REJECTED']
            )->nullable();

            $table->string('policy_reason', 100)->nullable();

            // ===== OFFICE SNAPSHOT =====
            $table->decimal('office_latitude', 10, 7)->nullable();
            $table->decimal('office_longitude', 10, 7)->nullable();
            $table->decimal('distance_from_office', 8, 2)->nullable()->comment('meter');
            $table->decimal('allowed_radius', 8, 2)->nullable()->comment('meter');

            // ===== DEVICE =====
            $table->enum('device_type', ['DESKTOP', 'MOBILE', 'WEB','ANDROID','IOS']); 
            $table->string('ip_address', 45)->nullable();
            $table->text('noted')->nullable();

            
            $table->enum(
                'attendance_status',
                [
                    'DRAFT',      // data sementara / belum final
                    'READY',      // siap diproses / validasi
                    'COMPLETED',  // absensi sukses (tepat waktu / normal)
                    'LATE',       // terlambat (khusus IN)
                    'REJECTED',    // ditolak (policy / accuracy / dll)
                    'ONTIME' 
                ]
            )->default('READY');


            $table->timestamps();

            // ===== FK =====
            $table->foreign('user_id')
                  ->references('id_user')
                  ->on('ms_users')
                  ->onDelete('cascade');

            $table->foreign('employee_id')
                  ->references('id_employee')
                  ->on('employees')
                  ->onDelete('cascade');

            // ===== INDEX =====
            $table->index(['user_id', 'attendance_date']);
            $table->unique(['user_id', 'attendance_date', 'attendance_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

