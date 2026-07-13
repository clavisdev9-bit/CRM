<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan hanya 1 data
        if (DB::table('app_settings')->exists()) {
            return;
        }

        DB::table('app_settings')->insert([
            'app_name' => 'Duta Indomandiri',
            'app_short_name' => 'DIM',
            'app_tagline' => 'Grow Faster with Smarter Sales....',

            'app_logo' => 'logo.png',
            'app_logo_small' => 'logo.png',
            'favicon' => 'favicon.ico',

            'primary_color' => '#4f46e5',
            'secondary_color' => '#22c55e',
            'sidebar_color' => '#ffffff',
            'navbar_color' => '#ffffff',

            'footer_text' => '© Developed ❤️ by Apregi Pratayuda',
            'footer_license_url' => '#',
            'footer_documentation_url' => '#',
            'footer_support_url' => '#',

            'version' => '0.0.0',
            'environment' => 'development',

            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
