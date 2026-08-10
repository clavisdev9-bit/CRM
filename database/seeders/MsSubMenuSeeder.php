<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MsSubMenuSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Ganti 'id_submenu' di bawah ini kalau nama primary key tabel
        // ms_submenu kamu berbeda (cek migration-nya).
        $primaryKey = 'id_submenu';

        // Helper kecil supaya tiap insert otomatis dapat created_at/updated_at
        $insert = function (array $data) use ($now, $primaryKey) {
            return DB::table('ms_submenu')->insertGetId(array_merge($data, [
                'created_at' => $now,
                'updated_at' => $now,
            ]), $primaryKey);
        };

        $insertMany = function (array $rows) use ($now) {
            foreach ($rows as $row) {
                DB::table('ms_submenu')->insert(array_merge($row, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        };

        // ================================
        // ADMINISTRATOR DASHBOARD
        // ================================
        $insert([
            'id_menu' => 1,
            'url' => '/administrator-dashboard',
            'icon' => 'nav-icon fas fa-chart-line',
            'title' => 'Dashboard Admin IT',
            'noted' => 'Dashboard Administrator',
            'is_active' => true,
            'parent_id' => null,
        ]);

        // ================================
        // ADMINISTRATOR MENU MANAGEMENT
        // ================================
        $insert([
            'id_menu' => 1,
            'url' => '/administrator-menu',
            'icon' => 'nav-icon fas fa-tasks',
            'title' => 'Menu Management',
            'noted' => 'Menu Data Management',
            'is_active' => true,
            'parent_id' => null,
        ]);

        // ================================
        // ADMINISTRATOR SUBMENU MANAGEMENT
        // ================================
        $insert([
            'id_menu' => 1,
            'url' => '/administrator-submenu',
            'icon' => 'nav-icon fas fa-tasks',
            'title' => 'Submenu Management',
            'noted' => 'Submenu Data Management',
            'is_active' => true,
            'parent_id' => null,
        ]);

        // ================================
        // ADMINISTRATOR ROLE MANAGEMENT
        // ================================
        $insert([
            'id_menu' => 1,
            'url' => '/administrator-role',
            'icon' => 'nav-icon fas fa-rotate',
            'title' => 'Role Management',
            'noted' => 'Role Data Management',
            'is_active' => true,
            'parent_id' => null,
        ]);

        // ================================
        // ADMINISTRATOR USER MANAGEMENT
        // ================================
        $insert([
            'id_menu' => 1,
            'url' => '/administrator-users',
            'icon' => 'nav-icon fas fa-users-between-lines',
            'title' => 'User Management',
            'noted' => 'User Data Management',
            'is_active' => true,
            'parent_id' => null,
        ]);

        // ================================
        // ADMINISTRATOR SETTING MANAGEMENT
        // ================================
        $settingApp = $insert([
            'id_menu' => 1,
            'url' => '/setting-app',
            'icon' => 'nav-icon fa fa-cogs',
            'title' => 'Setting Application',
            'noted' => 'Setting Application',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insertMany([
            [
                'id_menu' => 1,
                'url' => '/setting-app-global',
                'icon' => null,
                'title' => 'Setting App Global',
                'noted' => 'Setting Application Global',
                'is_active' => true,
                'parent_id' => $settingApp,
            ],
            [
                'id_menu' => 1,
                'url' => '/setting-frontend-app',
                'icon' => null,
                'title' => 'Frontend Application',
                'noted' => 'Frontend Application',
                'is_active' => false,
                'parent_id' => $settingApp,
            ],
        ]);

        // ================================
        // ADMINISTRATOR MASTER
        // ================================
        $master = $insert([
            'id_menu' => 1,
            'url' => '/administrator-master-data',
            'icon' => 'nav-icon fas fa-folder-open',
            'title' => 'Master',
            'noted' => 'Master administrator Data Management',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insert([
            'id_menu' => 1,
            'url' => '/data-master-employee',
            'icon' => null,
            'title' => 'Master Employe',
            'noted' => 'Master Employe Page',
            'is_active' => true,
            'parent_id' => $master,
        ]);

        // ================================
        // SALES HOME
        // ================================
        $insert([
            'id_menu' => 2,
            'url' => '/sales-home',
            'icon' => 'nav-icon fas fa-home',
            'title' => 'Sales Home',
            'noted' => 'Sales Home Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $masterData = $insert([
            'id_menu' => 2,
            'url' => '/master-data',
            'icon' => 'nav-icon fas fa-server',
            'title' => 'Master Data',
            'noted' => 'Master Data Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $timesheetsLeave = $insert([
            'id_menu' => 2,
            'url' => '/sales-timesheets-leave',
            'icon' => 'nav-icon fas fa-user-clock',
            'title' => 'Timesheets',
            'noted' => 'Timesheets Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insertMany([
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-attendance',
                'icon' => null,
                'title' => 'Attendance',
                'noted' => 'Attendance Page',
                'is_active' => true,
                'parent_id' => $timesheetsLeave,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-attendance-leave',
                'icon' => null,
                'title' => 'Leave',
                'noted' => 'Leave Page',
                'is_active' => true,
                'parent_id' => $timesheetsLeave,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-work-routes',
                'icon' => null,
                'title' => 'Work routes',
                'noted' => 'Work routes Page',
                'is_active' => true,
                'parent_id' => $timesheetsLeave,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-work-shift-table',
                'icon' => null,
                'title' => 'Work Shift Table',
                'noted' => 'Work Shift Table',
                'is_active' => true,
                'parent_id' => $timesheetsLeave,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-shift',
                'icon' => null,
                'title' => 'Shift',
                'noted' => 'Shift page',
                'is_active' => true,
                'parent_id' => $timesheetsLeave,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-shift-categories',
                'icon' => null,
                'title' => 'Shift Categories',
                'noted' => 'Shift Categories page',
                'is_active' => true,
                'parent_id' => $timesheetsLeave,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-workplace',
                'icon' => null,
                'title' => 'Workplace',
                'noted' => 'Workplace',
                'is_active' => true,
                'parent_id' => $timesheetsLeave,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-reports',
                'icon' => null,
                'title' => 'Reports',
                'noted' => 'Reports',
                'is_active' => true,
                'parent_id' => $timesheetsLeave,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-settings',
                'icon' => null,
                'title' => 'Settings',
                'noted' => 'Settings',
                'is_active' => true,
                'parent_id' => $timesheetsLeave,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-reports-history',
                'icon' => null,
                'title' => 'History Attendance',
                'noted' => 'Reports History Attendance',
                'is_active' => true,
                'parent_id' => $timesheetsLeave,
            ],
        ]);

        $salesManagement = $insert([
            'id_menu' => 2,
            'url' => '/sales-management',
            'icon' => 'nav-icon fas fa-store',
            'title' => 'Sales Management',
            'noted' => 'Sales Management Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insertMany([
            [
                'id_menu' => 2,
                'url' => '/sales-proposals',
                'icon' => null,
                'title' => 'Proposals',
                'noted' => 'Proposals Management Page',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-estimates',
                'icon' => null,
                'title' => 'Estimates',
                'noted' => 'Estimates Management Page',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-invoices',
                'icon' => null,
                'title' => 'Invoices',
                'noted' => 'Invoices Management Page',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-payments',
                'icon' => null,
                'title' => 'Payments',
                'noted' => 'Payments Management Page',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-credit-notes',
                'icon' => null,
                'title' => 'Credit Notes',
                'noted' => 'Credit Notes Management Page',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-items',
                'icon' => null,
                'title' => 'Items',
                'noted' => 'Items Management Page',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-leads',
                'icon' => 'nav-icon fas fa-arrows-down-to-people',
                'title' => 'Leads',
                'noted' => 'Leads Management Page',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
        ]);

        $insert([
            'id_menu' => 2,
            'url' => '/sales-subscriptions',
            'icon' => 'nav-icon fas fa-arrow-down-up-across-line',
            'title' => 'Subscriptions',
            'noted' => 'Subscriptions Management Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insert([
            'id_menu' => 2,
            'url' => '/sales-expenses',
            'icon' => 'nav-icon fas fa-expand',
            'title' => 'Expenses',
            'noted' => 'Expenses Management Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insert([
            'id_menu' => 2,
            'url' => '/sales-contracts',
            'icon' => 'nav-icon fas fa-file-contract',
            'title' => 'Contracts',
            'noted' => 'Contracts Management Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insert([
            'id_menu' => 2,
            'url' => '/sales-projects',
            'icon' => 'nav-icon fas fa-arrow-up-right-dots',
            'title' => 'Projects',
            'noted' => 'Projects Management Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insert([
            'id_menu' => 2,
            'url' => '/sales-tasks',
            'icon' => 'nav-icon fas fa-bars-progress',
            'title' => 'Tasks',
            'noted' => 'Tasks Management Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insert([
            'id_menu' => 2,
            'url' => '/sales-support',
            'icon' => 'nav-icon fas fa-life-ring',
            'title' => 'Support',
            'noted' => 'Support Management Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insertMany([
            [
                'id_menu' => 2,
                'url' => '/sales-customers',
                'icon' => 'nav-icon fas fa-users-rays',
                'title' => 'Customers',
                'noted' => 'Customers Management Page',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
        ]);

        $insert([
            'id_menu' => 2,
            'url' => '/sales-estimate-request',
            'icon' => 'nav-icon fas fa-note-sticky',
            'title' => 'Estimate Request',
            'noted' => 'Estimate Request Management Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insertMany([
            [
                'id_menu' => 2,
                'url' => '/sales-visit',
                'icon' => 'nav-icon fas fa-arrows-turn-to-dots',
                'title' => 'Sales Visits',
                'noted' => 'Sales Visits',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-follow-up',
                'icon' => 'nav-icon fas fa-tty',
                'title' => 'Follow Up',
                'noted' => 'Follow Up',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-reports-visits-and-follow-up',
                'icon' => 'nav-icon fas fa-tty',
                'title' => 'Sales Reports',
                'noted' => 'sales reports visits and followup',
                'is_active' => true,
                'parent_id' => $salesManagement,
            ],
            [
                'id_menu' => 2,
                'url' => '/data-master-leads-assign',
                'icon' => null,
                'title' => 'Master Leads Admin',
                'noted' => 'Master Leads Admin Page',
                'is_active' => true,
                'parent_id' => $masterData,
            ],
        ]);

        // ================================
        // MANAGER HOME
        // ================================
        $insert([
            'id_menu' => 3,
            'url' => '/manager-home',
            'icon' => 'nav-icon fas fa-home',
            'title' => 'Manager Home',
            'noted' => 'Manager Home Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $managerReport = $insert([
            'id_menu' => 3,
            'url' => '/manager-report',
            'icon' => 'nav-icon fas fa-file-lines',
            'title' => 'Manager Report',
            'noted' => 'Manager Report Page',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $insertMany([
            [
                'id_menu' => 3,
                'url' => '/manager-executive-summary-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Executive Summary Report',
                'noted' => 'Executive Summary Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],
            [
                'id_menu' => 3,
                'url' => '/manager-sales-performance-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Sales Performance Report',
                'noted' => 'Sales Performance Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],
            [
                'id_menu' => 3,
                'url' => '/manager-follow-up-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Follow Up Report',
                'noted' => 'Follow Up Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],
            [
                'id_menu' => 3,
                'url' => '/manager-visit-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Visit Report',
                'noted' => 'Visit Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],
            [
                'id_menu' => 3,
                'url' => '/manager-customer-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Customer Report',
                'noted' => 'Customer Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],
            [
                'id_menu' => 3,
                'url' => '/manager-lead-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Lead Report',
                'noted' => 'Lead Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],
            [
                'id_menu' => 3,
                'url' => '/manager-activity-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Activity Report',
                'noted' => 'Activity Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],
            // NOTE: Entry "Activity Report" sebelumnya duplikat 2x di data asli,
            // sudah dihapus salinan keduanya di sini.
            [
                'id_menu' => 3,
                'url' => '/manager-sales-pipeline-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Sales Pipeline Report',
                'noted' => 'Sales Pipeline Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],
            [
                'id_menu' => 3,
                'url' => '/manager-conversion-report',
                'icon' => 'nav-icon fas fa-file-lines',
                // Typo "Conversion Report Report" pada data asli sudah diperbaiki
                'title' => 'Conversion Report',
                'noted' => 'Conversion Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],
            [
                'id_menu' => 3,
                'url' => '/manager-complaint-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Complaint Report',
                'noted' => 'Complaint Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],
            [
                'id_menu' => 3,
                'url' => '/manager-potential-order-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Potential Order Report',
                'noted' => 'Potential Order Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],

            [
                'id_menu' => 3,
                'url' => '/manager-kpi-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'KPI Report',
                'noted' => 'KPI Report Page',
                'is_active' => true,
                'parent_id' => $managerReport,
            ],

             
        ]);

         $insert([
                'id_menu' => 3,
                'url' => '/approval-customers',
                'icon' => 'nav-icon fas fa-building',
                'title' => 'Approval Customers',
                'noted' => 'Approval Customers Page',
                'is_active' => true,
                'parent_id' => null,
            ]);

            $insert([
                'id_menu' => 3,
                'url' => '/approval-branch-customers',
                'icon' => 'nav-icon fas fa-building',
                'title' => 'Approval Customers Branch',
                'noted' => 'Approval Customers Branch Page',
                'is_active' => true,
                'parent_id' => null,
            ]);

            $insert([
                'id_menu' => 3,
                'url' => '/reassignment-sales',
                'icon' => 'nav-icon fas fa-people-arrows',
                'title' => 'Customer Reassignment',
                'noted' => 'Customer Reassignment Page',
                'is_active' => true,
                'parent_id' => null,
            ]);

            $insert([
                'id_menu' => 2,
                'url' => '/customer-history',
                'icon' => 'nav-icon fas fa-money-bill-transfer',
                'title' => 'Customer History',
                'noted' => 'Customer History Page',
                'is_active' => true,
                'parent_id' => null,
            ]);

            $insert([
                'id_menu' => 2,
                'url' => '/customer-population',
                'icon' => 'nav-icon fas fa-map-location-dot',
                'title' => 'Customer-Product Population',
                'noted' => 'Customer-Product Population Page',
                'is_active' => true,
                'parent_id' => null,
            ]);

            

             $insert([
                'id_menu' => 2,
                'url' => '/master-principle',
                'icon' => 'nav-icon fas fa-layer-group',
                'title' => 'Master Brand',
                'noted' => 'Master Brand Page',
                'is_active' => true,
                'parent_id' => $masterData,
            ]);

            $insert([
                'id_menu' => 2,
                'url' => '/master-product',
                'icon' => 'nav-icon fas fa-table-list',
                'title' => 'Master Product',
                'noted' => 'Master Product Page',
                'is_active' => true,
                'parent_id' => $masterData,
            ]);

    }
}