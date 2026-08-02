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


class OdooSync extends Controller
{
   

public function customerPopulation(CustomerPopulationRequest $request)
{
    $validated = $request->validated();
    $search   = $validated['search'] ?? null;
    $perPage  = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
    $sortBy   = $validated['sort_by'] ?? 'created_at';   // ← default diubah
    $sortDir  = $validated['sort_dir'] ?? 'desc';        // ← default diubah
    $filter   = $validated['filter'] ?? 'all';

    $query = OdooCustomer::query()
        ->search($search)
        ->filterPurchased($filter)
        ->sort($sortBy, $sortDir);

    $results = $query->paginate($perPage);
    $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";

    return ApiResponse::paginate(new OdooCustomerResourceCollection($results), $message);
}

public function customerPurchaseDetail($odooPartnerId)
{
    $customer = OdooCustomer::where('odoo_partner_id', $odooPartnerId)->first();

    if (!$customer) {
        return ApiResponse::error(
            'Customer not found',
            ['id' => ['Data customer dengan ID tersebut tidak ditemukan']],
            404
        );
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
    $totalCustomers   = OdooCustomer::count();
    $totalPurchased   = OdooCustomer::where('has_purchased', true)->count();
    $totalNotPurchase = $totalCustomers - $totalPurchased;
    $totalTransaksi   = OdooCustomer::sum('total_transaksi');

    $topCustomers = OdooCustomer::orderByDesc('total_transaksi')
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




// Trigger sync manual lewat API (opsional, buat testing tanpa masuk terminal)
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
