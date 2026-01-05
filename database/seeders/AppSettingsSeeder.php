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
            'app_name' => 'CLAVIS',
            'app_short_name' => 'Clavis',
            'app_tagline' => 'We Deliver More...',

            'app_logo' => 'logo.png',
            'app_logo_small' => 'logo.png',
            'favicon' => 'favicon.ico',

            'primary_color' => '#4f46e5',
            'secondary_color' => '#22c55e',
            'sidebar_color' => '#ffffff',
            'navbar_color' => '#ffffff',

            'footer_text' => '© develoved ❤️ by Apregi Pratyuda',
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
