<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();

            // === Identitas Aplikasi ===
            $table->string('app_name');
            $table->string('app_short_name')->nullable();
            $table->string('app_tagline')->nullable();

            // === Logo & Icon ===
            $table->string('app_logo')->nullable();
            $table->string('app_logo_small')->nullable();
            $table->string('favicon')->nullable();

            // === Warna / Tema ===
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('sidebar_color', 20)->nullable();
            $table->string('navbar_color', 20)->nullable();

            // === Footer ===
            $table->text('footer_text')->nullable();
            $table->string('footer_license_url')->nullable();
            $table->string('footer_documentation_url')->nullable();
            $table->string('footer_support_url')->nullable();

            // === Meta ===
            $table->string('version', 20)->nullable();
            $table->string('environment', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
