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
use App\Http\Controllers\Api\Users\Sales\Visits\Visits;
use App\Http\Controllers\Api\Home\Dashboard\DashboardController;
use App\Http\Controllers\Api\Manager\Dashboard\DashboardManagerController;
use App\Http\Controllers\Api\Manager\Approval\ApprovalCustomerController;
use App\Http\Controllers\Api\Manager\Approval\ApprovalCustomerBranchController;
use App\Http\Controllers\Api\Manager\Reassign\SalesReassign;
use App\Http\Controllers\Api\Odoo\OdooSync;
use App\Http\Controllers\Api\Users\Sales\Costumers\CustomersProductPopulation;
use App\Http\Controllers\Api\Manager\ReportNew\SalesActivityDashboardController;
use App\Http\Controllers\Api\Manager\VisitTarget\VisitTargetController;
use App\Http\Controllers\Api\Users\Sales\VisitTargets\SalesVisitTargetController;
use App\Http\Controllers\Api\Odoo\ProductController;
use App\Http\Controllers\Api\External\PopulationProductCustumers;
use Illuminate\Support\Facades\Http;



Route::post('/signIn', [SignAuth::class, 'signIn'])->name('api.sign.in');
Route::post('/forgot-password-request', [SignAuth::class, 'requestResetPassword'])->name('api.forgot.password');
Route::post('/reset-password', [SignAuth::class, 'resetPassword'])->name('api.reset.password');

Route::middleware(['jwt.auth'])->group(function () {
    
Route::get('/get-profile', [SignAuth::class, 'profile'])->name('api.get.profile');
Route::post('/signOut', [SignAuth::class, 'signOut'])->name('api.sign.out');
Route::post('/update-profile', [SignAuth::class, 'updateProfile'])->name('api.update.profile');
Route::put('/update-password', [SignAuth::class, 'updatePassword'])->name('api.update.password');


Route::get('/sidebar-access/{role_id}', [Sidebar::class, 'getMenusByRole'])->name('api.sidebar.access');
Route::get('/sidebar-access-submenu', [Sidebar::class, 'getSubmenu'])->name('api.sidebar.access.submenu');
Route::get('/permission-button', [Sidebar::class, 'getUserPermissions'])->name('api.permission.button');

Route::get('/sessions', [SignAuth::class, 'sessions']);
Route::delete('/sessions/{id}', [SignAuth::class, 'revokeSession']);


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
Route::get('/get-menu-for-form', [Administrator::class, 'selectMenu'])->name('api.get.menu.for.form');

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

// api master product
// Route::get('/master-product', [Master::class, 'Product'])->name('api.product.management');
// Route::get('/products/debug-companies', [Master::class, 'debugCompanies']);
// Route::get('/products/debug-product-companies', [Master::class, 'debugProductCompanies']);




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
 // ===== TAMBAHAN — laporan untuk sales yang login =====
Route::get('attendance/my-report',  [Attendance::class, 'myReport'])->name('api.my.report');
Route::get('attendance/my-history', [Attendance::class, 'myHistory'])->name('api.my.history');




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
Route::get('/leads/search-company-name', [Leads::class, 'searchCompanyName']);


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
Route::get('/customer-submissions', [Costumers::class, 'customerSubmission']);
Route::get('/customers/search-company', [Costumers::class, 'searchCompany']);
Route::get('/customers/{id}/branches', [Costumers::class, 'branches']);
Route::post('/customers/{id}/branches', [Costumers::class, 'storeBranch']);
Route::put('/customer-branches/{id}',[Costumers::class, 'updateBranch']);
Route::delete('/customer-branches/{id}',[Costumers::class, 'destroyBranch']);




// api sales Follow Up
Route::get('/follow-up-leads', [FollowUp::class, 'followUpSalesByLeads'])->name('api.follow.up.master');
Route::get('/follow-up-customers', [FollowUp::class, 'followUpSalesByCustomers'])->name('api.follow.up.customers');
Route::get('/follow-up/show/{id}', [FollowUp::class, 'showFollowUp'])->name('api.follow.up.show');
// Route::post('/follow-up/store', [FollowUp::class, 'storeFollowUp'])->name('api.follow.up.store');
Route::get('/follow-up/get-sales/leads', [FollowUp::class, 'getLeadsNeedFollowUp'])->name('api.get.sales.leads');
Route::get('/follow-up/get-sales/leads/direct', [FollowUp::class, 'getLeadsForDirectFollowUp'])->name('api.get.sales.leads.direct');
Route::get('/follow-up/get-sales/customers', [FollowUp::class, 'getCustomersBySales'])->name('api.get.sales.customers');
Route::put('/follow-up/update/{id}', [FollowUp::class, 'updateFollowUp'])->name('api.follow.up.update');
Route::delete('/follow-up/delete/{id}', [FollowUp::class, 'deleteFollowUp'])->name('api.follow.up.delete');
Route::get('/follow-ups/{id}/timeline', [FollowUp::class, 'timeline'])->name('api.follow.up.timeline');
Route::get('/follow-ups/{id}/customer-timeline', [FollowUp::class, 'customerTimeline']);
Route::post('/follow-ups/{id}/submit-result', [FollowUp::class, 'submitResultFollowUp'])->name('api.follow.up.submit.result');
Route::post('/follow-ups/{id}/direct-follow-up', [FollowUp::class, 'createDirectFollowUpFromLead'])->name('api.follow.up.start.follow.up');
Route::post('/follow-ups/{id}/submit-result/customer', [FollowUp::class, 'submitResultCustomers'])->name('api.follow.up.submit.result.customers');
Route::post('/follow-ups/direct-follow-up-customer', [FollowUp::class, 'storeDirectCustomer'])->name('api.follow.up.direct.customer');
Route::put('follow-ups/{id}/close', [FollowUp::class, 'closeFollowUpManually']);
Route::get('/follow-up/get-customers-direct', [FollowUp::class, 'getCustomerForDirect'])->name('api.get.customers.direct');


// api sales Visits bagian lead
Route::get('/data-visits-leads', [Visits::class, 'getVisitLead'])->name('api.data.leads.visit');
Route::get('/data-visits/detail/{id}', [Visits::class, 'getVisitDetail'])->name('api.data.visit.detail');
Route::get('/data-leads-visit', [Visits::class, 'VisitLeads'])->name('api.data.leads.visit');
Route::get('/data-customers-visit', [Visits::class, 'VisitCustomers'])->name('api.data.customers.visit');
// start visit (PAKAI LEADS ID)
Route::post('/leads/{lead}/start', [Visits::class, 'startVisit']);
// Check In (PAKAI VISIT ID)
Route::post('/visits/{visit}/check-in', [Visits::class, 'checkInVisit']);
// Check Out (PAKAI VISIT ID)
Route::post('/visits/{visit}/check-out', [Visits::class, 'checkOutVisit']);



// api sales Visits bagian customers
// start visit (PAKAI CUSTOMER ID)
Route::post('/customers/{customer}/start', [Visits::class, 'startVisitCustomer']);
// Check In (PAKAI VISIT ID)
Route::post('/visits/customers/{visit}/check-in', [Visits::class, 'checkInVisitCustomer']);
// Check Out (PAKAI VISIT ID)
Route::post('/visits/customers/{visit}/check-out', [Visits::class, 'checkOutCustomer']);


Route::prefix('customer-approval')->group(function () {
    Route::get('/', [ApprovalCustomerController::class, 'index']);
    Route::put('/{id}/approve', [ApprovalCustomerController::class, 'approve']);
    Route::put('/{id}/reject', [ApprovalCustomerController::class, 'reject']);
});



Route::prefix('customer-branch-approval')->group(function () {
    Route::get('/', [ApprovalCustomerBranchController::class, 'index']);
    Route::put('/{id}/approve', [ApprovalCustomerBranchController::class, 'approve']);
    Route::put('/{id}/reject', [ApprovalCustomerBranchController::class, 'reject']);
});


// route untuk report sales activity (manager)
Route::prefix('manager/sales-activity')->group(function () {
    Route::get('summary', [SalesActivityDashboardController::class, 'summary']);
    Route::get('activities', [SalesActivityDashboardController::class, 'activities']);
    Route::get('activities/{type}/{id}', [SalesActivityDashboardController::class, 'activityDetail']);
});


// route untuk target visit (manager)
Route::prefix('manager/visit-targets')->group(function () {
       Route::get('/support/customers', [VisitTargetController::class, 'supportCustomers']); // taruh di atas /{id}
       Route::get('/', [VisitTargetController::class, 'index']);
       Route::get('/{id}', [VisitTargetController::class, 'show']);
       Route::post('/', [VisitTargetController::class, 'store']);
       Route::put('/{id}', [VisitTargetController::class, 'update']);
       Route::delete('/{id}', [VisitTargetController::class, 'destroy']);
       Route::post('/duplicate-next-month', [VisitTargetController::class, 'duplicateToNextMonth']);
   });

// route untuk target visit (sales)
Route::get('sales/visit-targets', [SalesVisitTargetController::class, 'myTargets']);


Route::prefix('product-populations')->group(function () {
    Route::get('/', [CustomersProductPopulation::class, 'index']);
    Route::get('/counts', [CustomersProductPopulation::class, 'counts']);
    Route::get('/unassigned', [CustomersProductPopulation::class, 'unassigned']);
    Route::post('/assign', [CustomersProductPopulation::class, 'assign']);
    Route::get('/{id}', [CustomersProductPopulation::class, 'show']);
    Route::post('/', [CustomersProductPopulation::class, 'store']);
    Route::put('/{id}', [CustomersProductPopulation::class, 'update']);
    Route::delete('/{id}', [CustomersProductPopulation::class, 'destroy']);
});


Route::prefix('manager-reassign-sales')->group(function () {
    Route::get('/', [SalesReassign::class, 'index']);
    Route::get('/sales', [SalesReassign::class, 'sales']);
    Route::put('/customer/{id}', [SalesReassign::class, 'reassignCustomer']);
    Route::put('/branch/{id}', [SalesReassign::class, 'reassignBranch']);
});

// product routes, untuk GET /products (list product) dan POST /products/sync (sync manual dari Odoo)
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/sync', [ProductController::class, 'sync']);
});


// route terbaru untuk population cust list sell
Route::prefix('customers')->group(function () {
    Route::get('/population/summary', [OdooSync::class, 'customerPopulationSummary']);
    Route::get('/population', [OdooSync::class, 'customerPopulation']);
    Route::get('/{id}/purchase-detail', [OdooSync::class, 'customerPurchaseDetail']);

     //assign/reassign sales untuk see customer 
    Route::post('/assign-sales', [OdooSync::class, 'assignCustomerSales']);
    Route::post('/unassign-sales', [OdooSync::class, 'unassignCustomerSales']);
    Route::get('/sales-assignments', [OdooSync::class, 'listCustomerSalesAssignments']);

    Route::get('/sales-list', [OdooSync::class, 'salesList']);
    });


    // route terbaru untuk population cust (sync)
    Route::prefix('odoo')->group(function () {
        Route::post('/sync-customers', [OdooSync::class, 'syncCustomers']);
        Route::post('/sync-customer-purchases', [OdooSync::class, 'syncCustomerPurchases']);
    });
});



// UNTUK GEO LOCATION
Route::get('/reverse-geocode', [Location::class, 'reverse']);
// untuk data map external
Route::get('/data-visits-leads-map', [Visits::class, 'getVisitTargetMap'])->name('api.data.visits.map');
// untuk data visit all data 
Route::get('/data-visits-all-data', [Visits::class, 'getVisitAllData'])->name('api.data.visits.all');

// untuk popoukation product custumers external
Route::get('/data-population-product-customers', [PopulationProductCustumers::class, 'index'])->name('api.data.population.product.customers');


Route::middleware(['jwt.auth'])->group(function () {
// Dashboard Manager
Route::prefix('dashboard/manager/')->group(function () {
Route::get('/executive-summary',  [DashboardManagerController::class, 'summary']);
Route::get('/sales-performance',  [DashboardManagerController::class, 'salesPerformance']);
Route::get('/follow-up',  [DashboardManagerController::class, 'followUp']);
Route::get('/visit',  [DashboardManagerController::class, 'visit']);
Route::get('/pipeline',  [DashboardManagerController::class, 'pipeline']);
Route::get('/activity',  [DashboardManagerController::class, 'activity']);
Route::get('/conversion',  [DashboardManagerController::class, 'conversion']);
Route::get('/complaint',  [DashboardManagerController::class, 'complaint']);
Route::get('/potential-order',  [DashboardManagerController::class, 'potentialOrder']);
Route::get('/customers',  [DashboardManagerController::class, 'customers']);
Route::get('/kpi',  [DashboardManagerController::class, 'kpi']);
Route::get('/lead-analytics',  [DashboardManagerController::class, 'lead']);
});
});



// Dashboard
Route::prefix('dashboard')->group(function () {
    Route::get('/summary',         [DashboardController::class, 'summary']);
    Route::get('/visit-chart',     [DashboardController::class, 'visitChart']);
    Route::get('/top-sales',       [DashboardController::class, 'topSales']);
    Route::get('/conversion-rate', [DashboardController::class, 'conversionRate']);
    Route::get('/visit-status',    [DashboardController::class, 'visitStatus']);
    Route::get('/recent-activity', [DashboardController::class, 'recentActivity']);
    Route::get('/conversion-rate-customers', [DashboardController::class, 'conversionRateCustomers']);
    Route::get('/home-stats', [DashboardController::class, 'homeStats']);
    Route::get('/activity-visits',     [DashboardController::class, 'activityVisits']);
    Route::get('/activity-follow-ups', [DashboardController::class, 'activityFollowUps']);
     Route::get('/sales', [DashboardController::class, 'salesDashboard']);
     Route::get('/it', [DashboardController::class, 'itDashboard']);
     Route::get('/manager', [DashboardController::class, 'managerDashboard']);
});

Route::get('/asset-version', function () {
    $file = public_path('images/logo.png');
    return response()->json([
        'v' => file_exists($file) ? filemtime($file) : time()
    ]);
});



Route::get('/holidays', function (Request $request) {
    return Http::withoutVerifying() 
        ->get('https://libur.deno.dev/api', [
            'year' => $request->year,
            'month' => $request->month
        ])
        ->json();
});



