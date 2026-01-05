<?php

// namespace Database\Seeders;

// use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\DB;
// use Carbon\Carbon;

// class MsAccessSubMenuSeeder extends Seeder
// {
//     /**
//      * Run the database seeds.
//      */
//     public function run(): void
//     {
//         $now = Carbon::now();

//         // Semua submenu (1 sampai --)
//         $submenuIds = range(1, 47);

//         foreach ($submenuIds as $submenuId) {
//             DB::table('ms_access_submenu')->insert([
//                 'id_user'    => 1, // user admin
//                 'id_submenu' => $submenuId,
//                 'created_at' => $now,
//                 'updated_at' => $now,
//             ]);
//         }
//     }
// }



namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MsAccessSubMenuSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $userId = 1;

        // Semua submenu (misal 1–47)
        $submenuIds = range(1, 49);

        // Ambil submenu yang SUDAH ADA untuk user ini
        $existingSubmenuIds = DB::table('ms_access_submenu')
            ->where('id_user', $userId)
            ->pluck('id_submenu')
            ->toArray();

        // Cari yang BELUM ADA
        $missingSubmenuIds = array_diff($submenuIds, $existingSubmenuIds);

        $data = [];
        foreach ($missingSubmenuIds as $submenuId) {
            $data[] = [
                'id_user'    => $userId,
                'id_submenu' => $submenuId,
                'can_view'   => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($data)) {
            DB::table('ms_access_submenu')->insert($data);
        }
    }
}
