<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GroupCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('group_companies')->insert([
            [
                'name_group' => 'PT. Duta Indomandiri',
                'description_group' => 'PT. Duta Indomandiri',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_group' => 'PT. Clavisindo Berjaya',
                'description_group' => 'PT. Clavisindo Berjaya',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_group' => 'Windu Persada Cargo',
                'description_group' => 'WPC Logistic',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_group' => 'PT Duta Indo Raya',
                'description_group' => 'PT Duta Indo Raya',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'name_group' => 'PT My Everything Indonesia',
                'description_group' => 'PT My Everything Indonesia',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
