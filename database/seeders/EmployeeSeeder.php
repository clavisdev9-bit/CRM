<?php


// namespace Database\Seeders;

// use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\DB;

// class EmployeeSeeder extends Seeder
// {
//     public function run(): void
//     {
        // Opsional: Kosongkan tabel employee sebelum mengisi agar tidak duplikat saat di-seed ulang
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('employees')->truncate();
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

//         DB::table('employees')->insert([
//             'user_id' => 1, // Pastikan id_user 1 sudah ada di tabel ms_users
//             'nik' => 'EMP' . date('Ymd') . '001',
//             'tempat_lahir' => 'Jakarta',
//             'tanggal_lahir' => '1995-05-20',
//             'jenis_kelamin' => 'L',
//             'alamat' => 'Jl. Merdeka No. 123, Jakarta Pusat',
//             'no_hp' => '081234567890',
//             'tanggal_masuk' => now(),
//             'status_karyawan' => 'Tetap',
//             'created_at' => now(),
//             'updated_at' => now(),
//         ]);
//     }
// }



namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('employees')->insert([
            [
                'user_id' => 1, // pastikan ms_users.id_user = 1 ada
                'office_id' => 1, // NULL jika Sales / WFH

                'nik' => 'EMP-' . date('Ymd') . '-001',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1999-05-20',
                'jenis_kelamin' => 'L',

                'alamat' => 'Jl. Merdeka No. 123, Jakarta Pusat',
                'no_hp' => '081234567890',

                'tanggal_masuk' => Carbon::now()->toDateString(),
                'status_karyawan' => 'AKTIF',

                // ===== ATTENDANCE =====
                'attendance_mode' => 'OFFICE', // OFFICE | FREE | WFH | HYBRID

                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
