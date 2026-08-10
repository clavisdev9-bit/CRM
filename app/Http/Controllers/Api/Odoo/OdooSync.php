<?php

namespace App\Http\Controllers\Api\Odoo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OdooCustomer;
use App\Models\OdooCustomerPurchaseItem as CustomerPurchaseItem;
use App\Helpers\ApiResponse;
use App\Http\Resources\OdooCustomerResource;
use App\Http\Resources\OdooCustomerResourceCollection;
use App\Http\Requests\CustomerPopulationRequest;
use App\Models\CustomerSalesAssignmentOdoo;

class OdooSync extends Controller
{
    /**
     * 1 = Admin, 3 = Manager → lihat semua data customer.
     * Role lain (termasuk 2 = Sales) dibatasi hanya customer miliknya.
     */
    private function canViewAllCustomers($user): bool
    {
        return in_array($user->role_id, [1, 3]);
    }

    // public function customerPopulation(CustomerPopulationRequest $request)
    // {
    //     $validated = $request->validated();
    //     $search   = $validated['search'] ?? null;
    //     $perPage  = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
    //     $sortBy   = $validated['sort_by'] ?? 'created_at';
    //     $sortDir  = $validated['sort_dir'] ?? 'desc';
    //     $filter   = $validated['filter'] ?? 'all';

    //     $user = auth()->user();

    //     $query = OdooCustomer::query()
    //         ->search($search)
    //         ->filterPurchased($filter)
    //         ->sort($sortBy, $sortDir);

    //     if (!$this->canViewAllCustomers($user)) {
    //         $query->filterBySales($user->id_user);
    //     }

    //     $results = $query->paginate($perPage);
    //     $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";

    //     return ApiResponse::paginate(new OdooCustomerResourceCollection($results), $message);
    // }

    public function customerPopulation(CustomerPopulationRequest $request)
{
    $validated = $request->validated();
    $search   = $validated['search'] ?? null;
    $perPage  = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
    $sortBy   = $validated['sort_by'] ?? 'created_at';
    $sortDir  = $validated['sort_dir'] ?? 'desc';
    $filter   = $validated['filter'] ?? 'all';

    $user = auth()->user();

    $query = OdooCustomer::query()
        ->with('assignment.salesUser') // BARU
        ->search($search)
        ->filterPurchased($filter)
        ->sort($sortBy, $sortDir);

    if (!$this->canViewAllCustomers($user)) {
        $query->filterBySales($user->id_user);
    }

    $results = $query->paginate($perPage);
    $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";

    return ApiResponse::paginate(new OdooCustomerResourceCollection($results), $message);
}

    public function customerPurchaseDetail($odooPartnerId)
    {


    $customer = OdooCustomer::with('assignment.salesUser') // diubah dari 'sales' jadi ini
        ->where('odoo_partner_id', $odooPartnerId)
        ->first();

        // $customer = OdooCustomer::with('sales')
        //     ->where('odoo_partner_id', $odooPartnerId)
        //     ->first();

        if (!$customer) {
            return ApiResponse::error(
                'Customer not found',
                ['id' => ['Data customer dengan ID tersebut tidak ditemukan']],
                404
            );
        }

        $user = auth()->user();

        if (!$this->canViewAllCustomers($user)) {
            $ownedBySales = $customer->assignment && $customer->assignment->sales_id == $user->id_user;

            if (!$ownedBySales) {
                return ApiResponse::error('Unauthorized', [], 403);
            }
        }

        $items = CustomerPurchaseItem::where('odoo_customer_id', $odooPartnerId)
            ->orderByDesc('order_date')
            ->get([
                'order_name',
                'order_date',
                'product_name',
                'product_code',
                'qty',
                'price_unit',
            ]);

        return ApiResponse::success([
            'customer'  => new OdooCustomerResource($customer),
            'purchases' => $items,
        ], 'Success, customer purchase detail retrieved');
    }

    public function customerPopulationSummary()
    {
        $user = auth()->user();

        $baseQuery = OdooCustomer::query();

        if (!$this->canViewAllCustomers($user)) {
            $baseQuery->filterBySales($user->id_user);
        }

        $totalCustomers   = (clone $baseQuery)->count();
        $totalPurchased   = (clone $baseQuery)->where('has_purchased', true)->count();
        $totalNotPurchase = $totalCustomers - $totalPurchased;
        $totalTransaksi   = (clone $baseQuery)->sum('total_transaksi');

        $topCustomers = (clone $baseQuery)
            ->orderByDesc('total_transaksi')
            ->limit(5)
            ->get(['name', 'total_transaksi']);

        return ApiResponse::success([
            'total_customers'     => $totalCustomers,
            'total_purchased'     => $totalPurchased,
            'total_not_purchased' => $totalNotPurchase,
            'total_transaksi'     => (int) $totalTransaksi,
            'top_customers'       => $topCustomers,
        ], 'Success, customer population summary retrieved');
    }




    /**
 * ======================================================
 * ASSIGN / REASSIGN customer Odoo ke sales.
 * Hanya Admin & Manager yang boleh akses.
 * ======================================================
 */
public function assignCustomerSales(Request $request)
{
    $user = auth()->user();

    if (!$this->canViewAllCustomers($user)) {
        return ApiResponse::error('Unauthorized', [], 403);
    }

    $data = $request->validate([
        'odoo_customer_id' => 'required|integer|exists:odoo_customers,odoo_partner_id',
        'sales_id'         => 'required|integer|exists:ms_users,id_user',
    ]);

    $assignment = CustomerSalesAssignmentOdoo::updateOrCreate(
        ['odoo_customer_id' => $data['odoo_customer_id']],
        [
            'sales_id'    => $data['sales_id'],
            'assigned_at' => now(),
        ]
    );

    $assignment->load(['salesUser:id_user,fullname', 'customer:odoo_partner_id,name']);

    return ApiResponse::success($assignment, 'Customer berhasil di-assign ke sales.');
}

/**
 * ======================================================
 * UNASSIGN customer Odoo (hapus dari sales manapun).
 * Hanya Admin & Manager yang boleh akses.
 * ======================================================
 */
public function unassignCustomerSales(Request $request)
{
    $user = auth()->user();

    if (!$this->canViewAllCustomers($user)) {
        return ApiResponse::error('Unauthorized', [], 403);
    }

    $data = $request->validate([
        'odoo_customer_id' => 'required|integer',
    ]);

    $deleted = CustomerSalesAssignmentOdoo::where('odoo_customer_id', $data['odoo_customer_id'])->delete();

    if (!$deleted) {
        return ApiResponse::error('Assignment tidak ditemukan.', [], 404);
    }

    return ApiResponse::success(null, 'Assignment berhasil dihapus.');
}

/**
 * ======================================================
 * LIST semua assignment (untuk tabel monitoring di admin).
 * ======================================================
 */
// public function listCustomerSalesAssignments(Request $request)
// {
//     $user = auth()->user();

//     if (!$this->canViewAllCustomers($user)) {
//         return ApiResponse::error('Unauthorized', [], 403);
//     }

//     $perPage = $request->query('per_page', 10);

//     $assignments = CustomerSalesAssignmentOdoo::with([
//             'salesUser:id_user,fullname',
//             'customer:odoo_partner_id,name',
//         ])
//         ->latest('assigned_at')
//         ->paginate($perPage);

//     return ApiResponse::paginate($assignments, $assignments->isEmpty() ? 'Belum ada assignment' : 'Success');
// }

public function listCustomerSalesAssignments(Request $request)
{
    $user = auth()->user();

    if (!$this->canViewAllCustomers($user)) {
        return ApiResponse::error('Unauthorized', [], 403);
    }

    $perPage = $request->query('per_page', 10);

    $assignments = CustomerSalesAssignmentOdoo::with([
            'salesUser:id_user,fullname',
            'customer:odoo_partner_id,name',
        ])
        ->latest('assigned_at')
        ->paginate($perPage);

    $formatted = [
        'data' => $assignments->items(),
        'pagination' => [
            'total'         => $assignments->total(),
            'per_page'      => $assignments->perPage(),
            'current_page'  => $assignments->currentPage(),
            'last_page'     => $assignments->lastPage(),
            'next_page_url' => $assignments->nextPageUrl(),
            'prev_page_url' => $assignments->previousPageUrl(),
        ],
    ];

    return ApiResponse::success(
        $formatted,
        $assignments->isEmpty() ? 'Belum ada assignment' : 'Success'
    );
}



/**
 * ======================================================
 * LIST SALES (untuk dropdown assign di frontend).
 * Hanya Admin & Manager yang boleh akses.
 * ======================================================
 */
public function salesList()
{
    $user = auth()->user();

    if (!$this->canViewAllCustomers($user)) {
        return ApiResponse::error('Unauthorized', [], 403);
    }

    $sales = \App\Models\MsUsers::where('role_id', 2) // 2 = Sales
        ->where('is_active', true)
        ->orderBy('fullname')
        ->get(['id_user', 'fullname', 'email']);

    return ApiResponse::success($sales, $sales->isEmpty() ? 'Belum ada data sales' : 'Success');
}

    public function syncCustomers()
    {
        \Artisan::call('odoo:sync-customers');
        return response()->json(['message' => 'Sync customer selesai', 'output' => \Artisan::output()]);
    }

    public function syncCustomerPurchases()
    {
        \Artisan::call('odoo:sync-customer-purchases');
        return response()->json(['message' => 'Sync pembelian selesai', 'output' => \Artisan::output()]);
    }
}