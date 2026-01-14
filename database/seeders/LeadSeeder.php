<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('leads')->insert([
            [
                'company_name'        => 'PT Maju Jaya',
                'contact_name'        => 'Andi Saputra',
                'email'               => 'andi@majujaya.co.id',
                'phone'               => '081234567890',

                'lead_source'         => 'Website',
                'lead_status'         => 'Contacted',

                'industry_id'         => 1,   // pastikan industries.id = 1 ADA
                'lead_category_id'    => 1,   // pastikan lead_categories.id = 1 ADA

                'assigned_to'         => null,   // user/sales id
                'id_user'             => 1,   // owner lead
                'created_by'          => 1,   // creator

                'visibility_type'     => 'PRIVATE',
                'last_contacted_at'   => '2026-01-12 10:00:00',

                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'company_name'        => 'PT Sukses Makmur',
                'contact_name'        => 'Budi Santoso',
                'email'               => 'budi@suksesmakmur.co.id',
                'phone'               => '081298765432',

                'lead_source'         => 'Referral',
                'lead_status'         => 'New',

                'industry_id'         => 2,   // pastikan industries.id = 2 ADA
                'lead_category_id'    => 2,   // pastikan lead_categories.id = 2 ADA

                'assigned_to'         => 2,   // user/sales id
                'id_user'             => 4,   // owner lead
                'created_by'          => 4,   // creator

                'visibility_type'     => 'PRIVATE',
                'last_contacted_at'   => '2026-01-14 09:00:00',

                'created_at'          => now(),
                'updated_at'          => now(),
            ]
        ]);
    }
}
