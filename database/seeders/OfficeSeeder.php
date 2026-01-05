<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('offices')->insert([
            [
                'office_name' => 'Kantor Pusat Jakarta',
                'latitude'    => -6.2000000,
                'longitude'   => 106.8166667,
                'radius'      => 100, // meter
                'is_active'   => true,
                'created_at'  => now(),
            ],
            [
                'office_name' => 'Cabang Bandung',
                'latitude'    => -6.9174639,
                'longitude'   => 107.6191228,
                'radius'      => 150, // meter
                'is_active'   => true,
                'created_at'  => now(),
            ],
        ]);
    }
}
