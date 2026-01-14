<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('lead_industries')->insert([
            ['name' => 'Manufacture'],
            ['name' => 'Retail'],
            ['name' => 'IT Services'],
            ['name' => 'F&B'],
            ['name' => 'Healthcare'],
            ['name' => 'Education'],
        ]);
    }
}
