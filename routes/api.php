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

});


Route::get('/reverse-geocode', [Location::class, 'reverse']);






