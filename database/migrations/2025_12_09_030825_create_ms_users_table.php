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
        Schema::create('ms_users', function (Blueprint $table) {
            $table->unsignedBigInteger('id_user', true);        // auto increment primary key
            $table->string('fullname');                         // nama lengkap
            $table->string('username')->unique();               // username
            $table->string('email')->unique();                  // email unik
            $table->timestamp('email_verified_at')->nullable(); // waktu email diverifikasi

            // Tambahan untuk JWT / Email Verification
            $table->string('email_verification_token')->nullable();
            $table->timestamp('email_verification_expires_at')->nullable();

            $table->string('password');                         // password hash
            $table->string('image')->default('default.png');    // default image
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('divisi_id')->nullable();
            $table->boolean('is_active')->default(true);        // aktif / tidak
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Optional foreign keys
            // $table->foreign('role_id')->references('id_role')->on('roles')->onDelete('set null');
            // $table->foreign('group_id')->references('id_group')->on('group_companies')->onDelete('set null');
            // $table->foreign('divisi_id')->references('id')->on('divisis')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ms_users');
    }
};
