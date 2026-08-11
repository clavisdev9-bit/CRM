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
        'username' => 'administrator12345',
        'email' => 'apregi.pratayuda@clavis.co.id',
        'password' => Hash::make('password123'),
        'image' => 'default.png',
        'role_id' => 1,
        'group_id' => 1,
        'divisi_id' => 1,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ],

    // [
    //     'fullname' => 'sputnix norwey',
    //     'username' => 'salesaccount1',
    //     'email' => 'salesaccount@example.com',
    //     'password' => Hash::make('password123'),
    //     'image' => 'default.png',
    //     'role_id' => 2,
    //     'group_id' => 1,
    //     'divisi_id' => 3,
    //     'is_active' => true,
    //     'created_at' => $now,
    //     'updated_at' => $now,
    // ],

    // [
    //     'fullname' => 'bortley england',
    //     'username' => 'salesaccount2',
    //     'email' => 'salesaccount2@example.com',
    //     'password' => Hash::make('password123'),
    //     'image' => 'default.png',
    //     'role_id' => 2,
    //     'group_id' => 1,
    //     'divisi_id' => 3,
    //     'is_active' => true,
    //     'created_at' => $now,
    //     'updated_at' => $now,
    // ],


    [
        'fullname' => 'Nana',
        'username' => 'nana12345',
        'email' => 'nana@dutaindo.co.id',
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
        'fullname' => 'Soni',
        'username' => 'soni12345',
        'email' => 'soni@dutaindo.co.id',
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
        'fullname' => 'jimmy',
        'username' => 'jimmy12345',
        'email' => 'jimmy@dutaindo.co.id',
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
        'fullname' => 'Bagir',
        'username' => 'bagir12345',
        'email' => 'sales3@dutaindomandiri.com',
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
        'fullname' => 'Hanif',
        'username' => 'hanif12345',
        'email' => 'hanif@dutaindomandiri.com',
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
        'fullname' => 'Alfian',
        'username' => 'alfian12345',
        'email' => 'engineersby@dutaindomandiri.com',
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
        'fullname' => 'Imam',
        'username' => 'imam12345',
        'email' => 'salessby1@dutaindomandiri.com',
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
        'fullname' => 'Evandra Alvarizky',
        'username' => 'evandra12345',
        'email' => 'evandra.alvarizky@dutaindomandiri.com',
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
        'fullname' => 'Budhi',
        'username' => 'budhi12345',
        'email' => 'budhi@dutaindo.co.id',
        'password' => Hash::make('password123'),
        'image' => 'default.png',
        'role_id' => 3,
        'group_id' => 1,
        'divisi_id' => 3,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ],

    // [
    //     'fullname' => 'willson denmark',
    //     'username' => 'manangeraccount',
    //     'email' => 'manangeraccount@example.com',
    //     'password' => Hash::make('password123'),
    //     'image' => 'default.png',
    //     'role_id' => 3,
    //     'group_id' => 1,
    //     'divisi_id' => 3,
    //     'is_active' => true,
    //     'created_at' => $now,
    //     'updated_at' => $now,
    // ],
]);
    }
}
