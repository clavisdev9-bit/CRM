<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        DB::table('attendances')->insert([
            /**
             * ======================
             * ATTENDANCE IN
             * ======================
             */
            [
                'user_id' => 1,
                'employee_id' => 1,

                'attendance_type' => 'IN',
                'attendance_datetime' => $today->copy()->setTime(8, 5),
                'attendance_date' => $today,
                'attendance_time' => '08:05:00',

                'photo_path' => 'attendance/photos/in_1.jpg',

                'latitude' => -6.2001000,
                'longitude' => 106.8167000,
                'accuracy' => 12.5,
                'location_name' => 'Kantor Pusat Jakarta',

                'accuracy_status' => 'HIGH',
                'policy_status' => 'ALLOWED',
                'policy_reason' => null,

                'office_latitude' => -6.2000000,
                'office_longitude' => 106.8166667,
                'distance_from_office' => 20.5,
                'allowed_radius' => 100,

                'device_type' => 'MOBILE',
                'ip_address' => '192.168.1.10',

                'attendance_status' => 'READY',
                'created_at' => now(),
            ],

            /**
             * ======================
             * ATTENDANCE OUT
             * ======================
             */
            [
                'user_id' => 1,
                'employee_id' => 1,

                'attendance_type' => 'OUT',
                'attendance_datetime' => $today->copy()->setTime(17, 15),
                'attendance_date' => $today,
                'attendance_time' => '17:15:00',

                'photo_path' => 'attendance/photos/out_1.jpg',

                'latitude' => -6.2001500,
                'longitude' => 106.8167200,
                'accuracy' => 15.0,
                'location_name' => 'Kantor Pusat Jakarta',

                'accuracy_status' => 'HIGH',
                'policy_status' => 'ALLOWED',
                'policy_reason' => null,

                'office_latitude' => -6.2000000,
                'office_longitude' => 106.8166667,
                'distance_from_office' => 30.2,
                'allowed_radius' => 100,

                'device_type' => 'MOBILE',
                'ip_address' => '192.168.1.10',

                'attendance_status' => 'READY',
                'created_at' => now(),
            ],
        ]);
    }
}
