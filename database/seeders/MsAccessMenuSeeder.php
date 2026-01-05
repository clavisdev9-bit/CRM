<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MsAccessMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Menu yang boleh diakses role Administrator
        $menuIds = [1, 2, 3];

        foreach ($menuIds as $menuId) {
            DB::table('ms_access_menu')->insert([
                'id_role' => 1, // Administrator
                'id_menu' => $menuId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
