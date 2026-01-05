<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendancePolicySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('attendance_policies')->insert([
            /**
             * =========================
             * GLOBAL POLICY (DEFAULT)
             * =========================
             * Berlaku untuk semua user
             * Cocok untuk Sales / FREE
             */
            [
                'policy_name'    => 'Global Default Policy',
                'applies_to'     => 'GLOBAL',
                'office_id'      => null,
                'user_id'        => null,
                'min_accuracy'   => 0,
                'max_accuracy'   => 999,
                'allowed_radius' => null, // bebas lokasi
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],

            /**
             * =========================
             * OFFICE POLICY
             * =========================
             * Berlaku untuk karyawan kantor
             * Radius dibatasi
             */
            [
                'policy_name'    => 'Office Default Policy',
                'applies_to'     => 'OFFICE',
                'office_id'      => 1, // pastikan office_id ini ada
                'user_id'        => null,
                'min_accuracy'   => 0,
                'max_accuracy'   => 50,
                'allowed_radius' => 100, // meter
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],

            /**
             * =========================
             * USER OVERRIDE (CONTOH)
             * =========================
             * Misal Manager boleh bebas lokasi
             */
            [
                'policy_name'    => 'Manager Override Policy',
                'applies_to'     => 'USER',
                'office_id'      => null,
                'user_id'        => 1, // id_user manager
                'min_accuracy'   => 0,
                'max_accuracy'   => 999,
                'allowed_radius' => null,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}
