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

        $submenus = [
            // ================================
            // ADMINISTRATOR DASHBOARD
            // ================================
            [
                'id_menu' => 1,
                'url' => '/administrator-dashboard',
                'icon' => 'nav-icon fas fa-chart-line',
                'title' => 'Dashboard Admin IT',
                'noted' => 'Dashboard Administrator',
                'is_active' => true,
                'parent_id' => null,
            ],

            // ================================
            // ADMINISTRATOR MENU MANAGEMENT
            // ================================

            [
                'id_menu' => 1,
                'url' => '/administrator-menu',
                'icon' => 'nav-icon fas fa-tasks',
                'title' => 'Menu Management',
                'noted' => 'Menu Data Management',
                'is_active' => true,
                'parent_id' => null,
            ],
            
            // ================================
            // ADMINISTRATOR SUBMENU MANAGEMENT
            // ================================

            [
                'id_menu' => 1,
                'url' => '/administrator-submenu',
                'icon' => 'nav-icon fas fa-tasks',
                'title' => 'Submenu Management',
                'noted' => 'Submenu Data Management',
                'is_active' => true,
                'parent_id' => null,
            ],

             // ================================
            // ADMINISTRATOR ROLE MANAGEMENT
            // ================================
            [
                'id_menu' => 1,
                'url' => '/administrator-role',
                'icon' => 'nav-icon fas fa-rotate',
                'title' => 'Role Management',
                'noted' => 'Role Data Management',
                'is_active' => true,
                'parent_id' => null,
            ],
             // ================================
            // ADMINISTRATOR USER MANAGEMENT
            // ================================
            [
                'id_menu' => 1,
                'url' => '/administrator-users',
                'icon' => 'nav-icon fas fa-users-between-lines',
                'title' => 'User Management',
                'noted' => 'User Data Management',
                'is_active' => true,
                'parent_id' => null,
            ],

             // ================================
            // ADMINISTRATOR SETTING MANAGEMENT
            // ================================
            [
                'id_menu' => 1,
                'url' => '/setting-app',
                'icon' => 'nav-icon fa fa-cogs',
                'title' => 'Setting Application',
                'noted' => 'Setting Application',
                'is_active' => true,
                'parent_id' => null,
            ],

             [
                'id_menu' => 1,
                'url' => '/setting-app-global',
                'icon' => null,
                'title' => 'Setting App Global',
                'noted' => 'Setting Application Global',
                'is_active' => true,
                'parent_id' => 6,
            ],

             [
                'id_menu' => 1,
                'url' => '/setting-frontend-app',
                'icon' => null,
                'title' => 'Frontend Application',
                'noted' => 'Frontend Application',
                'is_active' => false,
                'parent_id' => 6,
            ],


              // ================================
            // ADMINISTRATOR MASTER
            // ================================
            [
                'id_menu' => 1,
                'url' => '/administrator-master-data',
                'icon' => 'nav-icon fas fa-folder-open',
                'title' => 'Master',
                'noted' => 'Master administrator Data Management',
                'is_active' => true,
                'parent_id' => null,
            ],

            [
                'id_menu' => 1,
                'url' => '/data-master-employee',
                'icon' => null,
                'title' => 'Master Employe',
                'noted' => 'Master Employe Page',
                'is_active' => true,
                'parent_id' => 9,
            ],


             // ================================
            // SALES HOME
            // ================================
            [
                'id_menu' => 2,
                'url' => '/sales-home',
                'icon' => 'nav-icon fas fa-home',
                'title' => 'Sales Home',
                'noted' => 'Sales Home Page',
                'is_active' => true,
                'parent_id' => null,
            ],

             [
                'id_menu' => 2,
                'url' => '/master-data',
                'icon' => 'nav-icon fas fa-server',
                'title' => 'Master Data',
                'noted' => 'Master Data Page',
                'is_active' => true,
                'parent_id' => null,
            ],
             

            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave',
                'icon' => 'nav-icon fas fa-user-clock',
                'title' => 'Timesheets & Leave',
                'noted' => 'Timesheets & Leave Page',
                'is_active' => true,
                'parent_id' => null,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-attendance',
                'icon' => null,
                'title' => 'Attendance',
                'noted' => 'Attendance Page',
                'is_active' => true,
                'parent_id' => 13,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-attendance-leave',
                'icon' => null,
                'title' => 'Leave',
                'noted' => 'Leave Page',
                'is_active' => true,
                'parent_id' => 13,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-work-routes',
                'icon' => null,
                'title' => 'Work routes',
                'noted' => 'Work routes Page',
                'is_active' => true,
                'parent_id' => 13,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-work-shift-table',
                'icon' => null,
                'title' => 'Work Shift Table',
                'noted' => 'Work Shift Table',
                'is_active' => true,
                'parent_id' => 13,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-shift',
                'icon' => null,
                'title' => 'Shift',
                'noted' => 'Shift page',
                'is_active' => true,
                'parent_id' => 13,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-shift-categories',
                'icon' => null,
                'title' => 'Shift Categories',
                'noted' => 'Shift Categories page',
                'is_active' => true,
                'parent_id' => 13,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-workplace',
                'icon' => null,
                'title' => 'Workplace',
                'noted' => 'Workplace',
                'is_active' => true,
                'parent_id' => 13,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-reports',
                'icon' => null,
                'title' => 'Reports',
                'noted' => 'Reports',
                'is_active' => true,
                'parent_id' => 13,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-timesheets-leave-settings',
                'icon' => null,
                'title' => 'Settings',
                'noted' => 'Settings',
                'is_active' => true,
                'parent_id' => 13,
            ],

             [
                'id_menu' => 2,
                'url' => '/sales-management',
                'icon' => 'nav-icon fas fa-store',
                'title' => 'Sales Management',
                'noted' => 'Sales Management Page',
                'is_active' => true,
                'parent_id' => null,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-proposals',
                'icon' => null,
                'title' => 'Proposals',
                'noted' => 'Proposals Management Page',
                'is_active' => true,
                'parent_id' => 23,
            ],
            [
                'id_menu' => 2,
                'url' => '/sales-estimates',
                'icon' => null,
                'title' => 'Estimates',
                'noted' => 'Estimates Management Page',
                'is_active' => true,
                'parent_id' => 23,
            ],
              [
                'id_menu' => 2,
                'url' => '/sales-invoices',
                'icon' => null,
                'title' => 'Invoices',
                'noted' => 'Invoices Management Page',
                'is_active' => true,
                'parent_id' => 23,
            ],

              [
                'id_menu' => 2,
                'url' => '/sales-payments',
                'icon' => null,
                'title' => 'Payments',
                'noted' => 'Payments Management Page',
                'is_active' => true,
                'parent_id' => 23,
            ],

              [
                'id_menu' => 2,
                'url' => '/sales-credit-notes',
                'icon' => null,
                'title' => 'Credit Notes',
                'noted' => 'Credit Notes Management Page',
                'is_active' => true,
                'parent_id' => 23,
            ],
            
              [
                'id_menu' => 2,
                'url' => '/sales-items',
                'icon' => null,
                'title' => 'Items',
                'noted' => 'Items Management Page',
                'is_active' => true,
                'parent_id' => 23,
            ],

              
            [
                'id_menu' => 2,
                'url' => '/sales-leads',
                'icon' => 'nav-icon fas fa-arrows-down-to-people',
                'title' => 'Leads',
                'noted' => 'Leads Management Page',
                'is_active' => true,
                'parent_id' => 23,
            ],

              [
                'id_menu' => 2,
                'url' => '/sales-subscriptions',
                 'icon' => 'nav-icon fas fa-arrow-down-up-across-line',
                'title' => 'Subscriptions',
                'noted' => 'Subscriptions Management Page',
                'is_active' => true,
                'parent_id' => null,
            ],

              [
                'id_menu' => 2,
                'url' => '/sales-expenses',
                'icon' => 'nav-icon fas fa-expand',
                'title' => 'Expenses',
                'noted' => 'Expenses Management Page',
                'is_active' => true,
                'parent_id' => null,
            ],

              [
                'id_menu' => 2,
                'url' => '/sales-contracts',
                'icon' => 'nav-icon fas fa-file-contract',
                'title' => 'Contracts',
                'noted' => 'Contracts Management Page',
                'is_active' => true,
                'parent_id' => null,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-projects',
                'icon' => 'nav-icon fas fa-arrow-up-right-dots',
                'title' => 'Projects',
                'noted' => 'Projects Management Page',
                'is_active' => true,
                'parent_id' => null,
            ],
           
            [
                'id_menu' => 2,
                'url' => '/sales-tasks',
                'icon' => 'nav-icon fas fa-bars-progress',
                'title' => 'Tasks',
                'noted' => 'Tasks Management Page',
                'is_active' => true,
                'parent_id' => null,
            ],
            
            [
                'id_menu' => 2,
                'url' => '/sales-support',
                'icon' => 'nav-icon fas fa-life-ring',
                'title' => 'Support',
                'noted' => 'Support Management Page',
                'is_active' => true,
                'parent_id' => null,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-customers',
                'icon' => 'nav-icon fas fa-users-rays',
                'title' => 'Customers',
                'noted' => 'Customers Management Page',
                'is_active' => true,
                'parent_id' => 23,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-estimate-request',
                'icon' => 'nav-icon fas fa-note-sticky',
                'title' => 'Estimate Request',
                'noted' => 'Estimate Request Management Page',
                'is_active' => true,
                'parent_id' => null,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-visit',
                'icon' => 'nav-icon fas fa-arrows-turn-to-dots',
                'title' => 'Sales Visits',
                'noted' => 'Sales Visits',
                'is_active' => true,
                'parent_id' => 23,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-follow-up',
                'icon' => 'nav-icon fas fa-tty',
                'title' => 'Follow Up',
                'noted' => 'Follow Up',
                'is_active' => true,
                'parent_id' => 23,
            ],

            [
                'id_menu' => 2,
                'url' => '/sales-reports-visits-and-follow-up',
                'icon' => 'nav-icon fas fa-tty',
                'title' => 'Sales Reports',
                'noted' => 'sales reports visits and followup',
                'is_active' => true,
                'parent_id' => 23,
            ],




             // ================================
            // MANAGER HOME
            // ================================
            [
                'id_menu' => 3,
                'url' => '/manager-home',
                'icon' => 'nav-icon fas fa-home',
                'title' => 'Manager Home',
                'noted' => 'Manager Home Page',
                'is_active' => true,
                'parent_id' => null,
            ],

             [
                'id_menu' => 3,
                'url' => '/manager-example-report',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Example Report',
                'noted' => 'Example Report Page',
                'is_active' => true,
                'parent_id' => null,
            ],

             [
                'id_menu' => 3,
                'url' => '/manager-example-report-one',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Example Report one',
                'noted' => 'Example Report one Page',
                'is_active' => true,
                'parent_id' => 43,
            ],

             [
                'id_menu' => 3,
                'url' => '/manager-example-report-two',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Example Report two',
                'noted' => 'Example Report two Page',
                'is_active' => true,
                'parent_id' => 43,
            ],
            
             [
                'id_menu' => 3,
                'url' => '/manager-example-report-theree',
                'icon' => 'nav-icon fas fa-file-lines',
                'title' => 'Example Report three',
                'noted' => 'Example Report three Page',
                'is_active' => true,
                'parent_id' => 43,
            ],


            [
                'id_menu' => 2,
                'url' => '/data-master-leads-assign',
                'icon' => null,
                'title' => 'Master Leads Admin',
                'noted' => 'Master Leads Admin Page',
                'is_active' => true,
                'parent_id' => 12,
            ],


            




            
            ];

        foreach ($submenus as $submenu) {
            DB::table('ms_submenu')->insert([
                'id_menu' => $submenu['id_menu'],
                'url' => $submenu['url'],
                'icon' => $submenu['icon'],
                'title' => $submenu['title'],
                'noted' => $submenu['noted'],
                'is_active' => $submenu['is_active'],
                'parent_id' => $submenu['parent_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}