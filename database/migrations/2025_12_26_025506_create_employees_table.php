<?php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
    // public function up(): void
    // {
    //     Schema::create('employees', function (Blueprint $table) {
    //         $table->id('id_employee');
            
    //         // Relasi ke ms_users (Foreign Key)
    //         // Pastikan tipe datanya sama dengan id_user di ms_users
    //         $table->unsignedBigInteger('user_id')->unique(); 
            
    //         $table->string('nik', 20)->unique();
    //         $table->string('tempat_lahir', 100)->nullable();
    //         $table->date('tanggal_lahir')->nullable();
    //         $table->enum('jenis_kelamin', ['L', 'P']);
    //         $table->text('alamat')->nullable();
    //         $table->string('no_hp', 15)->nullable();
    //         $table->date('tanggal_masuk')->nullable();
    //         $table->enum('status_karyawan', ['Tetap', 'Kontrak', 'Magang'])->default('Kontrak');
            
    //         $table->timestamps();
    //         $table->softDeletes(); // Menyesuaikan dengan tabel user Anda

            // Definisi Foreign Key
//             $table->foreign('user_id')
//                   ->references('id_user')
//                   ->on('ms_users')
//                   ->onDelete('cascade');
//         });
//     }

//     public function down(): void
//     {
//         Schema::dropIfExists('employees');
//     }
// };


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id('id_employee');

            // ===== RELATION =====
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('office_id')->nullable();

            // ===== IDENTITY =====
            $table->string('nik', 50)->unique();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();

            $table->enum('jenis_kelamin', ['L', 'P'])
                  ->nullable()
                  ->comment('L = Laki-laki, P = Perempuan');

            // ===== CONTACT =====
            $table->text('alamat')->nullable();
            $table->string('no_hp', 20)->nullable();

            // ===== EMPLOYMENT =====
            $table->date('tanggal_masuk')->nullable();
            $table->enum('status_karyawan', ['PERMANENT', 'CONTRACT', 'INTERNSHIP'])
                  ->default('AKTIF');

            // ===== ATTENDANCE CONFIG =====
            $table->enum('attendance_mode', ['FREE', 'OFFICE', 'WFH', 'HYBRID'])
                  ->default('OFFICE')
                  ->comment('FREE=Sales, OFFICE=Kantor, WFH=Remote, HYBRID=Campuran');

            // ===== TIMESTAMP =====
            $table->timestamps();
            $table->softDeletes();

            // ===== FOREIGN KEY =====
            $table->foreign('user_id')
                  ->references('id_user')
                  ->on('ms_users')
                  ->onDelete('cascade');

            // $table->foreign('office_id')
            //       ->references('id')
            //       ->on('offices')
            //       ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};