<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $divisions = [
            ['name_division' => 'IT Development', 'kode_division' => 'IT'],
            ['name_division' => 'Marketing', 'kode_division' => 'MKT'],
            ['name_division' => 'Sales', 'kode_division' => 'SLS'],
            ['name_division' => 'Finance', 'kode_division' => 'FIN'],
            ['name_division' => 'Human Resource Development', 'kode_division' => 'HRD'],
        ];

        foreach ($divisions as $division) {
            DB::table('ms_division')->insert([
                'name_division' => $division['name_division'],
                'kode_division' => $division['kode_division'],
                'description_division' => $division['name_division'] . ' division',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
