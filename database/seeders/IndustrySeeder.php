<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('lead_industries')->insert([
    ['name' => 'Manufacturing'],
    ['name' => 'Construction'],
    ['name' => 'Automotive'],
    ['name' => 'Food & Beverage'],
    ['name' => 'Energy & Utilities'],
    ['name' => 'Wholesale & Distribution'],
    ['name' => 'Logistics & Transportation'],
    ['name' => 'Real Estate & Property'],
    ['name' => 'Hospitality & Tourism'],
    ['name' => 'Retail & E-commerce'],
    ['name' => 'Consulting'],
    ['name' => 'IT Services & Software'],
    ['name' => 'Healthcare'],
    ['name' => 'Finance & Insurance'],
    ['name' => 'Banking & Financial Services'],
    ['name' => 'Telecommunications'],
    ['name' => 'Media & Entertainment'],
    ['name' => 'Agriculture & Agribusiness'],
    ['name' => 'Government & Public Sector'],
    ['name' => 'Nonprofit / NGO'],
    ['name' => 'Professional Services'],
    ['name' => 'Legal Services'],
    ['name' => 'Education'],
    ['name' => 'Other'],
]);
    }
}
