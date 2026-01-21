<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\SignAuth;
use App\Http\Controllers\Api\Sidebar\Sidebar;
use App\Http\Controllers\Api\Administrator\Administrator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\Master\Master;
use App\Http\Controllers\Api\GeoLocation\Location;
use App\Http\Controllers\Api\Users\Attendance\Attendance;
use App\Http\Controllers\Api\Users\Sales\Leads\Leads;
use App\Http\Controllers\Api\Users\Sales\Costumers\Costumers;
use App\Http\Controllers\Api\Users\Sales\FollowUp\FollowUp;

Route::post('/signIn', [SignAuth::class, 'signIn'])->name('api.sign.in');
Route::post('/forgot-password-request', [SignAuth::class, 'requestResetPassword'])->name('api.forgot.password');
Route::post('/reset-password', [SignAuth::class, 'resetPassword'])->name('api.reset.password');

Route::middleware(['jwt.auth'])->group(function () {
    
Route::get('/get-profile', [SignAuth::class, 'profile'])->name('api.get.profile');
Route::post('/signOut', [SignAuth::class, 'signOut'])->name('api.sign.out');


Route::get('/sidebar-access/{role_id}', [Sidebar::class, 'getMenusByRole'])->name('api.sidebar.access');
Route::get('/sidebar-access-submenu', [Sidebar::class, 'getSubmenu'])->name('api.sidebar.access.submenu');
Route::get('/permission-button', [Sidebar::class, 'getUserPermissions'])->name('api.permission.button');



// api administrator role management 
Route::get('/role-management', [Administrator::class, 'Role'])->name('api.role.management');
Route::get('/role-management-show/{id}', [Administrator::class, 'showRole'])->name('api.show.role.management');
Route::post('/store-role-management', [Administrator::class, 'storeRole'])->name('api.store.role.management');
Route::put('/update-role-management/{id}', [Administrator::class, 'updateRole'])->name('api.update.role.management');
Route::delete('/delete-role-management/{id}', [Administrator::class, 'destroyRole'])->name('api.delete.role.management');

// api administrator menu management
Route::get('/menu-management', [Administrator::class, 'Menu'])->name('api.menu.management');
Route::get('/menu-management-show/{id}', [Administrator::class, 'showMenu'])->name('api.show.menu.management');
Route::post('/store-menu-management', [Administrator::class, 'storeMenu'])->name('api.store.menu.management');
Route::put('/update-menu-management/{id}', [Administrator::class, 'updateMenu'])->name('api.update.menu.management');
Route::delete('/delete-menu-management/{id}', [Administrator::class, 'destroyMenu'])->name('api.delete.menu.management');

// api administrator submenu management
Route::get('/submenu-management', [Administrator::class, 'Submenu'])->name('api.submenu.management');
Route::get('/submenu-management-show/{id}', [Administrator::class, 'showSubmenu'])->name('api.show.submenu.management');
Route::post('/store-submenu-management', [Administrator::class, 'storeSubmenu'])->name('api.store.submenu.management');
Route::put('/update-submenu-management/{id}', [Administrator::class, 'updateSubMenu'])->name('api.update.submenu.management');
Route::delete('/delete-submenu-management/{id}', [Administrator::class, 'destroySubmenu'])->name('api.delete.submenu.management');

// api administrator access role to menu management
Route::get('/access-role-to-menu/{id_role}', [Administrator::class, 'AccessRoleToMenu'])->name('api.access.role.to.menu');
Route::post('/access-menu/change',[Administrator::class, 'changeAccessMenu'])->name('api.access.menu.change');


// api administrator users management
Route::get('/users-management', [Administrator::class, 'Users'])->name('api.users.management');
Route::post('/store-users-management', [Administrator::class, 'storeUser'])->name('api.store.users.management');
Route::get('/users-management/show/{id}', [Administrator::class, 'showUser'])->name('api.show.users.management');
// untuk prod
Route::put('/update-users-management/{id}', [Administrator::class, 'updateUser'])->name('api.update.users.management');
// untuk local
// Route::post('/update-users-management/{id}', [Administrator::class, 'updateUser'])->name('api.update.users.management');
Route::delete('/delete-users-management/{id}', [Administrator::class, 'deleteUser'])->name('api.delete.users.management');
Route::get('/submenu-select', [Administrator::class, 'selectSubmenu'])->name('api.submenu.select');
Route::get('/division-select', [Administrator::class, 'selectDivision'])->name('api.division.select');
Route::get('/group-select', [Administrator::class, 'selectGroup'])->name('api.group.select');
Route::get('/role-select', [Administrator::class, 'selectRole'])->name('api.role.select');

// access user to submenu 
Route::get('/users/{id_user}/submenu-access', [Administrator::class, 'submenuAccess']);
Route::put('/users/{id_user}/submenu-access/{id_submenu}',[Administrator::class, 'updateSubmenuAccess']);

// api administrator setting app
Route::get('/setting-app-management', [Administrator::class, 'SettingApp'])->name('api.setting.app.management');
Route::post('/setting-app-store-management', [Administrator::class, 'storeSetting'])->name('api.store.setting.app.management');
Route::put('/update-setting-app-management/{id}', [Administrator::class, 'updateSetting'])->name('api.update.setting.app.management');
Route::delete('/delete-setting-app-management/{id}', [Administrator::class, 'deleteSetting'])->name('api.delete.setting.management');
Route::get('/setting-app-show/{id}', [Administrator::class, 'showAppSetting'])->name('api.show.app.setting.management');



// api master sales
Route::get('/employee-management', [Master::class, 'Employee'])->name('api.employee.management');
Route::get('/employee-management-show/{id}', [Master::class, 'showEmployee'])->name('api.show.employee.management');
Route::post('/employee-store-management', [Master::class, 'storeEmployee'])->name('api.store.employee.management');
Route::put('/employee-update-management/{id}', [Master::class, 'updateEmployee'])->name('api.update.employee.management');
Route::delete('/employee-delete-management/{id}', [Master::class, 'deleteEmployee'])->name('api.delete.employee.management');
Route::put('/employee-restore-management/{id}',[Master::class, 'restoreEmployee'])->name('api.restore.employee.management');
Route::get('/employee-available-users',[Master::class, 'getAvailableUsers'])->name('api.employee.available.users');
Route::get('/select-office-for-employee', [Master::class, 'selectOffice'])->name('api.select.api');


// api User Attendance app
Route::get('/attendance-management', [Attendance::class, 'GetAttendanceById'])->name('api.attendance.management');
Route::get('/attendance/check-today', [Attendance::class, 'checkToday'])->name('api.attendance.check.today');
Route::get('/attendance/show/{id}', [Attendance::class, 'showAttendance'])->name('api.attendance.show');

//ini untuk sales 
Route::post('/attendance/process-free-location', [Attendance::class, 'storeAttendanceFreeLocation'])->name('api.attendance.process.free.location');
//ini untuk office 
Route::post('/attendance/process-office-location', [Attendance::class, 'storeAttendanceForOffice'])->name('api.attendance.process.office.location');
Route::delete('/attendance/delete/{id_attendance}', [Attendance::class, 'deleteAttendance'])->name('api.delete.attendance');
// Route::put('/attendance/update/{id}', [Attendance::class, 'updateAttendance'])->name('api.update.attendance');
Route::Post('/attendance/update/{id}', [Attendance::class, 'updateAttendance'])->name('api.update.attendance');


// api Sales Leads
Route::get('/leads/show/{id}', [Leads::class, 'showLead'])->name('api.leads.show');
Route::get('/leads-master', [Leads::class, 'Leads'])->name('api.leads.master');
Route::get('/all-leads-master-created-by-admin', [Leads::class, 'leadsAssignByAdminCreated'])->name('api.all.leads.master');
Route::get('/leads-assigned-to-me', [Leads::class, 'leadsAssignByAdminOrManager'])->name('api.leads.assigned.to.me');
Route::post('/leads-store', [Leads::class, 'storeLead'])->name('api.leads.store');
Route::post('/leads-store-bulk', [Leads::class, 'storeBulkLead'])->name('api.leads.store.bulk');
Route::put('/employee-restore-management/{id}',[Master::class, 'restoreEmployee'])->name('api.restore.employee.management');
Route::put('/leads-update/{id}', [Leads::class, 'updateLead'])->name('api.leads.update');
Route::delete('/leads-delete/{id}', [Leads::class, 'deleteLead'])->name('api.leads.delete');

Route::get('/leads/select/category', [Leads::class, 'selectCategoryLead'])->name('api.leads.select.category');
Route::get('/leads/select/industry', [Leads::class, 'selectIndustryLead'])->name('api.leads.select.industry');
Route::get('/leads/select/user-sales', [Leads::class, 'selectUserByDivision'])->name('api.leads.select.user.sales');


Route::post('/leads/import-excel', [Leads::class, 'importLeads'])->name('api.leads.import.excel');//belum selesai
/* ================= LEAD FOLLOW UP TIMELINE ================= */
Route::get('/leads/{leadId}/follow-ups',[Leads::class, 'leadFollowUpTimeline'])->name('api.leads.follow.up.timeline');



// api sales Customers
Route::get('/customers-masters', [Costumers::class, 'customers'])->name('api.customers.master'); 
Route::post('/customers/store', [Costumers::class, 'storeCostumers'])->name('api.customers.store');
Route::put('/customers/update/{id}', [Costumers::class, 'updateCostumers'])->name('api.customers.update');
Route::delete('/customers/delete/{id}', [Costumers::class, 'destroyCostumers'])->name('api.customers.delete');

Route::get('/customers/show/{id}', [Costumers::class, 'showCostumers'])->name('api.customers.show');
Route::get('/customers/select/industry', [Leads::class, 'selectIndustry'])->name('api.customers.select.lead.industry');
Route::get('/customers/select/lead-category', [Leads::class, 'selectLeadCategory'])->name('api.customers.select.lead.category');
Route::get('/customers/select/user-sales', [Leads::class, 'selectUserByDivision'])->name('api.leads.select.user.sales');
 /* ================= CUSTOMER FOLLOW UP TIMELINE ================= */
Route::get('/customers/{customerId}/follow-ups',[Costumers::class, 'customerFollowUpTimeline'])->name('api.customers.follow.up.timeline');




// api sales Follow Up
Route::get('/follow-up-masters', [FollowUp::class, 'followUpSales'])->name('api.follow.up.master');
Route::get('/follow-up/show/{id}', [FollowUp::class, 'showFollowUp'])->name('api.follow.up.show');
Route::post('/follow-up/store', [FollowUp::class, 'storeFollowUp'])->name('api.follow.up.store');
Route::get('/follow-up/get-sales/leads', [FollowUp::class, 'getLeadsBySales'])->name('api.get.sales.leads');
Route::get('/follow-up/get-sales/customers', [FollowUp::class, 'getCustomersBySales'])->name('api.get.sales.customers');
Route::put('/follow-up/update/{id}', [FollowUp::class, 'updateFollowUp'])->name('api.follow.up.update');
Route::delete('/follow-up/delete/{id}', [FollowUp::class, 'deleteFollowUp'])->name('api.follow.up.delete');
});



Route::get('/reverse-geocode', [Location::class, 'reverse']);





