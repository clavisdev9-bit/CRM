<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MsUserSeeder::class,
            MsRoleSeeder::class,
            MsMenuSeeder::class,
            MsSubMenuSeeder::class,
            MsAccessMenuSeeder::class,
            MsAccessSubMenuSeeder::class,
            GroupCompanySeeder::class,
            DivisionSeeder::class,
            EmployeeSeeder::class,
            AppSettingsSeeder::class,
            OfficeSeeder::class,
            AttendanceSeeder::class,
            AttendancePolicySeeder::class,
             LeadCategorySeeder::class,
            IndustrySeeder::class,
            LeadSeeder::class,
        ]);
    }
}

