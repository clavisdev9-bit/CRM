<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('lead_categories')->insert([
            ['name' => 'B2B', 'description' => 'Business to Business'],
            ['name' => 'B2C', 'description' => 'Business to Consumer'],
            ['name' => 'Reseller', 'description' => 'Reseller / Distributor'],
            ['name' => 'Partner', 'description' => 'Business Partner'],
            ['name' => 'Project', 'description' => 'Project Based Lead'],
        ]);
    }
}
