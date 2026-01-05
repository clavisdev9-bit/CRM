<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MsUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

      DB::table('ms_users')->insert([
    [
        'fullname' => 'apregi pratayuda',
        'username' => 'administrator',
        'email' => 'administrator@example.com',
        'password' => Hash::make('password123'),
        'image' => 'default.png',
        'role_id' => 1,
        'group_id' => 1,
        'divisi_id' => 1,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ],

    [
        'fullname' => 'sputnix norwey',
        'username' => 'salesaccount1',
        'email' => 'salesaccount@example.com',
        'password' => Hash::make('password123'),
        'image' => 'default.png',
        'role_id' => 2,
        'group_id' => 1,
        'divisi_id' => 3,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ],

    [
        'fullname' => 'bortley england',
        'username' => 'salesaccount2',
        'email' => 'salesaccount2@example.com',
        'password' => Hash::make('password123'),
        'image' => 'default.png',
        'role_id' => 2,
        'group_id' => 1,
        'divisi_id' => 3,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ],

    [
        'fullname' => 'willson denmark',
        'username' => 'manangeraccount',
        'email' => 'manangeraccount@example.com',
        'password' => Hash::make('password123'),
        'image' => 'default.png',
        'role_id' => 3,
        'group_id' => 1,
        'divisi_id' => 3,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ],
]);

  
    }
}
