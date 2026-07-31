<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class LeadCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('lead_categories')->insert([
            [
                'name' => 'Develop',
                'description' => 'Customer yang memiliki potensi untuk dikembangkan melalui peningkatan hubungan,
                 frekuensi transaksi, atau peluang bisnis baru.',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Retain',
                'description' => 'Customer yang sudah aktif dan menjadi prioritas untuk dipertahankan agar tetap loyal dan terus bertransaksi.',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Acquire',
                'description' => 'Customer atau prospek baru yang menjadi target untuk mendapatkan peluang bisnis atau transaksi pertama.',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Light Touch',
                'description' => 'Customer yang hanya memerlukan komunikasi atau kunjungan berkala dengan intensitas rendah untuk menjaga hubungan dan tetap terhubung.',
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}


