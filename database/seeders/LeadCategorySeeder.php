<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class LeadCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('lead_categories')->insert([
            [
                'name' => 'Hot Lead',
                'description' => 'Prospek dengan peluang tinggi',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Warm Lead',
                'description' => 'Prospek potensial tapi perlu follow up',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Cold Lead',
                'description' => 'Prospek dengan peluang rendah',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Partner',
                'description' => 'Partner atau referral',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}


