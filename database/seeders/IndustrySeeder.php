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
            ['name' => 'Supplier'],
            ['name' => 'Mining'],
            ['name' => 'Palm Oil'],
            ['name' => 'Contractor'],
            ['name' => 'General Industry'],
            ['name' => 'Chemical'],
            ['name' => 'Petrochemical'],
            ['name' => 'Epchem'],
            ['name' => 'Pulp & Paper'],
            ['name' => 'Sugar'],
            ['name' => 'Textile'],
            ['name' => 'Food & Beverage'],
            ['name' => 'Plantation'],
            ['name' => 'Fertilizer'],
            ['name' => 'Construction'],
            ['name' => 'Automotive'],
            ['name' => 'Agriculture & Agribusiness'],
            ['name' => 'Oil & Gas'],
            ['name' => 'Chemical & Petrochemical'],
            ['name' => 'Energy & Utilities'],
            ['name' => 'Pharmaceutical'],
            ['name' => 'Healthcare'],
            ['name' => 'Textile & Garment'],
            ['name' => 'Paper & Packaging'],
            ['name' => 'Plastic & Rubber'],
            ['name' => 'Steel & Metal'],
            ['name' => 'Electronics & Semiconductor'],
            ['name' => 'Wholesale & Distribution'],
            ['name' => 'Retail & E-commerce'],
            ['name' => 'Logistics & Transportation'],
            ['name' => 'Shipping & Maritime'],
            ['name' => 'Import & Export'],
            ['name' => 'Warehousing'],
            ['name' => 'Real Estate & Property'],
            ['name' => 'Hospitality & Tourism'],
            ['name' => 'Consulting'],
            ['name' => 'IT Services & Software'],
            ['name' => 'Finance & Insurance'],
            ['name' => 'Banking & Financial Services'],
            ['name' => 'Telecommunications'],
            ['name' => 'Media & Entertainment'],
            ['name' => 'Government & Public Sector'],
            ['name' => 'Education'],
            ['name' => 'Professional Services'],
            ['name' => 'Legal Services'],
            ['name' => 'Nonprofit / NGO'],
            ['name' => 'Trading Company'],
            ['name' => 'FMCG'],
            ['name' => 'Consumer Goods'],
            ['name' => 'Furniture'],
            ['name' => 'Marine & Offshore'],
            ['name' => 'Aviation'],
            ['name' => 'Others'],
        ]);
    }
}