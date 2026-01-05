<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MsRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $roles = [
            'administrator',
            'sales',
            'manager',
        ];
        foreach ($roles as $role) {
            DB::table('ms_role')->insert([
                'role' => $role,
                'description' => $role . ' role',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}

