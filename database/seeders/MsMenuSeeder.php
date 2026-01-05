<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MsMenuSeeder extends Seeder
{
    
    public function run(): void
    {
        $now = Carbon::now();
        $menus = [
            'administrator',
            'sales',
            'manager',
        ];

        foreach ($menus as $menu) {
            DB::table('ms_menu')->insert([
                'menu' => $menu,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}