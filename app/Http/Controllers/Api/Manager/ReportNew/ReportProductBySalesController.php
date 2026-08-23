<?php

namespace App\Http\Controllers\Api\Manager\ReportNew;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\ReportProductBySalesValidationIndex;
use App\Http\Resources\ReportProductBySalesResource;
use App\Http\Resources\ReportProductBySalesResourceCollection;
use App\Models\CustomerSalesAssignmentOdoo;
use App\Models\MsUsers;
use App\Models\OdooCustomer;
use App\Models\OdooCustomerPurchaseItem;
use App\Models\OdooProduct;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ============================================================================
 * REPORT PRODUCT BY SALES -- Admin, Manager & Sales
 * ----------------------------------------------------------------------------
 * Laporan "product apa aja yang paling laku, dijual oleh sales siapa" --
 * tiap baris = kombinasi 1 Sales + 1 Product, dengan total qty & omzet yang
 * ke-generate dari data yang sudah disync dari Odoo:
 *
 *   odoo_customer_purchase_items (qty, price_unit, per transaksi)
 *        |
 *        v  odoo_customer_id --> sales_id mana yang pegang customer itu
 *                         (lewat CustomerSalesAssignmentOdoo -- SAMA PERSIS
 *                         pola atribusi yang dipakai SalesTargetController,
 *                         BUKAN dari kolom sales_id di purchase item itu
 *                         sendiri, karena tabel itu emang ga punya kolom
 *                         itu)
 *        |
 *        v  di-agregasi ulang per (sales_id, odoo_product_id) -> total_qty,
 *                         total_omzet, transaction_count
 *
 * KENAPA AGREGASINYA 2 TAHAP (bukan 1 query GROUP BY langsung)?
 * odoo_customer_purchase_items TIDAK punya kolom sales_id (atribusinya
 * cuma ada di tabel assignment terpisah, per CUSTOMER bukan per transaksi).
 * Daripada JOIN manual ke tabel customer_sales_assignment_odoo (saya ga
 * pegang nama kolom pastinya di luar sales_id/odoo_customer_id yang sudah
 * kepakai di controller lain), agregasinya dipecah:
 *   1) SQL: GROUP BY odoo_customer_id + odoo_product_id (efisien, di DB)
 *   2) PHP: map odoo_customer_id -> sales_id (dari
 *      CustomerSalesAssignmentOdoo::pluck()), terus REGROUP baris hasil
 *      langkah 1 jadi per sales_id + odoo_product_id.
 * Jumlah baris hasil langkah 1 (unique customer+product per tahun) biasanya
 * ga sebesar itu, jadi regroup di PHP aman buat volume data segini.
 *
 * FILTER OPSI (dropdown Sales & Kategori) SENGAJA TIDAK bikin endpoint
 * baru -- REUSE endpoint yang sudah ada di SalesTargetController:
 *   - GET /sales-targets/options/sales
 *   - GET /sales-targets/options/categories
 * Soalnya isinya sama persis (daftar sales role=2 aktif, daftar
 * categ_id/categ_name unik dari odoo_products), ga perlu duplikat.
 *
 * AKSES: Admin/Manager (role_id 1/3) lihat semua sales. Sales (role_id 2)
 * cuma lihat baris punya dia sendiri (sales_id dipaksa = id_user dia,
 * mengabaikan sales_id yang dikirim di query kalau ada) -- pola sama
 * seperti index() di SalesTargetController.
 * ============================================================================
 */
class ReportProductBySalesController extends Controller
{
    /**
     * GET /report-product-by-sales
     * Rekap per (Sales, Product): total qty & omzet terjual, di tahun
     * tertentu. Bisa difilter per sales_id/categ_id/search, dipaginate.
     */
    public function index(ReportProductBySalesValidationIndex $request)
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            $periodYear = $validated['period_year'] ?? now()->year;
            $perPage    = $validated['per_page'] ?? 10;
            $page       = (int) $request->query('page', 1);

            $salesFilterId = $this->canViewAllSales($user)
                ? ($validated['sales_id'] ?? null)
                : $user->id_user;

            // ── 1. Peta odoo_customer_id -> sales_id, dari assignment ──
            $assignmentQuery = CustomerSalesAssignmentOdoo::query();
            if ($salesFilterId) {
                $assignmentQuery->where('sales_id', $salesFilterId);
            }
            $assignments = $assignmentQuery->get(['sales_id', 'odoo_customer_id']);

            if ($assignments->isEmpty()) {
                return ApiResponse::paginate(
                    ReportProductBySalesResourceCollection::make($this->emptyPaginator($request, $perPage, $page)),
                    'Data laporan product by sales tidak ditemukan'
                );
            }

            $customerToSales = $assignments->pluck('sales_id', 'odoo_customer_id');
            $customerIds     = $customerToSales->keys();

            // ── 2. Agregasi SQL per customer+product ──
            $itemsQuery = OdooCustomerPurchaseItem::whereIn('odoo_customer_id', $customerIds)
                ->whereYear('order_date', $periodYear);

            if (!empty($validated['categ_id'])) {
                $productIds = OdooProduct::where('categ_id', $validated['categ_id'])->pluck('odoo_product_id');
                $itemsQuery->whereIn('odoo_product_id', $productIds->isEmpty() ? [0] : $productIds);
            }

            $rows = $itemsQuery
                ->selectRaw('odoo_customer_id, odoo_product_id, product_name, product_code, COUNT(*) as transaction_count, COALESCE(SUM(qty), 0) as total_qty, COALESCE(SUM(qty * price_unit), 0) as total_omzet')
                ->groupBy('odoo_customer_id', 'odoo_product_id', 'product_name', 'product_code')
                ->get();

            // ── 3. Regroup PHP: (sales_id, odoo_product_id) ──
            $grouped = [];
            foreach ($rows as $row) {
                $salesId = $customerToSales->get($row->odoo_customer_id);
                if (!$salesId) {
                    continue; // jaga-jaga, harusnya ga kejadian (sudah di-filter whereIn)
                }

                $key = $salesId . '-' . $row->odoo_product_id;

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'sales_id'          => $salesId,
                        'odoo_product_id'   => $row->odoo_product_id,
                        'product_name'      => $row->product_name,
                        'product_code'      => $row->product_code,
                        'transaction_count' => 0,
                        'total_qty'         => 0.0,
                        'total_omzet'       => 0.0,
                    ];
                }

                $grouped[$key]['transaction_count'] += (int) $row->transaction_count;
                $grouped[$key]['total_qty']         += (float) $row->total_qty;
                $grouped[$key]['total_omzet']       += (float) $row->total_omzet;
            }

            $collection = collect(array_values($grouped));

            if ($collection->isEmpty()) {
                return ApiResponse::paginate(
                    ReportProductBySalesResourceCollection::make($this->emptyPaginator($request, $perPage, $page)),
                    'Data laporan product by sales tidak ditemukan'
                );
            }

            // ── 4. Lengkapi nama sales & info kategori product ──
            $salesNames = MsUsers::whereIn('id_user', $collection->pluck('sales_id')->unique())
                ->pluck('fullname', 'id_user');

            $productMeta = OdooProduct::whereIn('odoo_product_id', $collection->pluck('odoo_product_id')->unique())
                ->get(['odoo_product_id', 'categ_id', 'categ_name'])
                ->keyBy('odoo_product_id');

            $collection = $collection->map(function ($row) use ($salesNames, $productMeta) {
                $meta = $productMeta->get($row['odoo_product_id']);
                $row['sales_name'] = $salesNames->get($row['sales_id']) ?? ('#' . $row['sales_id']);
                $row['categ_id']   = $meta->categ_id ?? null;
                $row['categ_name'] = $meta->categ_name ?? null;
                return $row;
            });

            // ── 5. Search (nama product / nama sales) -- di PHP, soalnya
            //      agregasinya juga sudah di PHP di titik ini ──
            if (!empty($validated['search'])) {
                $search = mb_strtolower($validated['search']);
                $collection = $collection->filter(function ($row) use ($search) {
                    return str_contains(mb_strtolower($row['sales_name'] ?? ''), $search)
                        || str_contains(mb_strtolower($row['product_name'] ?? ''), $search);
                })->values();
            }

            // ── 6. Sort default: omzet terbesar duluan ──
            $collection = $collection->sortByDesc('total_omzet')->values();

            // ── 7. Pagination manual (agregasi kelar di PHP, bukan di DB) ──
            $paginator = new LengthAwarePaginator(
                $collection->forPage($page, $perPage)->values(),
                $collection->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return ApiResponse::paginate(
                ReportProductBySalesResourceCollection::make($paginator),
                $paginator->isEmpty() ? 'Data laporan product by sales tidak ditemukan' : 'Success'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load report product by sales', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /report-product-by-sales/summary?period_year=2026
     * Kartu ringkasan di atas tabel (total qty, total omzet, total
     * transaksi, jumlah product & sales yang kepakai) -- ngitung dari
     * SELURUH baris yang cocok filter (BUKAN cuma yang tampil di 1
     * halaman pagination).
     */
    public function summary(Request $request)
    {
        try {
            $user = $request->user();
            $periodYear = (int) ($request->query('period_year') ?? now()->year);
            $categId    = $request->query('categ_id');

            $salesFilterId = $this->canViewAllSales($user)
                ? $request->query('sales_id')
                : $user->id_user;

            $assignmentQuery = CustomerSalesAssignmentOdoo::query();
            if ($salesFilterId) {
                $assignmentQuery->where('sales_id', $salesFilterId);
            }
            $assignments = $assignmentQuery->get(['sales_id', 'odoo_customer_id']);

            if ($assignments->isEmpty()) {
                return ApiResponse::success([
                    'period_year'        => $periodYear,
                    'total_qty'          => 0,
                    'total_omzet'        => 0,
                    'total_transactions' => 0,
                    'product_count'      => 0,
                    'sales_count'        => 0,
                ], 'Success');
            }

            $customerIds = $assignments->pluck('odoo_customer_id');

            $itemsQuery = OdooCustomerPurchaseItem::whereIn('odoo_customer_id', $customerIds)
                ->whereYear('order_date', $periodYear);

            if (!empty($categId)) {
                $productIds = OdooProduct::where('categ_id', $categId)->pluck('odoo_product_id');
                $itemsQuery->whereIn('odoo_product_id', $productIds->isEmpty() ? [0] : $productIds);
            }

            $agg = (clone $itemsQuery)->selectRaw('
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(qty), 0) as total_qty,
                    COALESCE(SUM(qty * price_unit), 0) as total_omzet,
                    COUNT(DISTINCT odoo_product_id) as product_count
                ')->first();

            return ApiResponse::success([
                'period_year'        => $periodYear,
                'total_qty'          => (float) $agg->total_qty,
                'total_omzet'        => (float) $agg->total_omzet,
                'total_transactions' => (int) $agg->total_transactions,
                'product_count'      => (int) $agg->product_count,
                'sales_count'        => $assignments->pluck('sales_id')->unique()->count(),
            ], 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load report product by sales summary', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /report-product-by-sales/{salesId}/{odooProductId}/detail?period_year=2026
     * Rincian transaksi di balik 1 baris rekap (diklik dari ikon detail) --
     * daftar tiap order yang nyumbang ke total qty/omzet baris itu, plus
     * nama customer-nya (1 product bisa dibeli banyak customer beda).
     */
    public function detail(Request $request, $salesId, $odooProductId)
    {
        try {
            $user = $request->user();

            if (!$this->canViewAllSales($user) && (int) $salesId !== (int) $user->id_user) {
                return ApiResponse::error('Unauthorized.', [], 403);
            }

            $periodYear = (int) ($request->query('period_year') ?? now()->year);

            $assignedCustomerIds = CustomerSalesAssignmentOdoo::where('sales_id', $salesId)
                ->pluck('odoo_customer_id');

            $salesName   = MsUsers::where('id_user', $salesId)->value('fullname');
            $productInfo = OdooProduct::where('odoo_product_id', $odooProductId)
                ->first(['name', 'default_code', 'categ_name']);

            if ($assignedCustomerIds->isEmpty()) {
                return ApiResponse::success([
                    'sales_id'     => (int) $salesId,
                    'sales_name'   => $salesName ?? ('#' . $salesId),
                    'product_name' => $productInfo->name ?? null,
                    'product_code' => $productInfo->default_code ?? null,
                    'categ_name'   => $productInfo->categ_name ?? null,
                    'period_year'  => $periodYear,
                    'total_qty'    => 0.0,
                    'total_omzet'  => 0.0,
                    'transactions' => [],
                ], 'Success');
            }

            $transactions = OdooCustomerPurchaseItem::whereIn('odoo_customer_id', $assignedCustomerIds)
                ->where('odoo_product_id', $odooProductId)
                ->whereYear('order_date', $periodYear)
                ->orderByDesc('order_date')
                ->get(['order_name', 'order_date', 'odoo_customer_id', 'product_name', 'product_code', 'qty', 'price_unit']);

            $customerNames = OdooCustomer::whereIn('odoo_partner_id', $transactions->pluck('odoo_customer_id')->unique())
                ->pluck('name', 'odoo_partner_id');

            return ApiResponse::success([
                'sales_id'     => (int) $salesId,
                'sales_name'   => $salesName ?? ('#' . $salesId),
                'product_name' => $productInfo->name ?? null,
                'product_code' => $productInfo->default_code ?? null,
                'categ_name'   => $productInfo->categ_name ?? null,
                'period_year'  => $periodYear,
                'total_qty'    => (float) $transactions->sum('qty'),
                'total_omzet'  => (float) $transactions->sum(fn ($t) => $t->qty * $t->price_unit),
                'transactions' => $transactions->map(fn ($t) => [
                    'order_name'    => $t->order_name,
                    'order_date'    => optional($t->order_date)->format('Y-m-d'),
                    'customer_name' => $customerNames->get($t->odoo_customer_id) ?? ('#' . $t->odoo_customer_id),
                    'qty'           => (float) $t->qty,
                    'price_unit'    => (float) $t->price_unit,
                    'subtotal'      => (float) $t->qty * (float) $t->price_unit,
                ])->values(),
            ], 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load report product by sales detail', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Paginator kosong -- dipakai pas assignment/hasil agregasi kosong,
     * biar response envelope-nya TETAP konsisten (data:[] + pagination
     * lengkap), bukan bentuk lain yang bikin frontend perlu handle 2 shape.
     */
    private function emptyPaginator(Request $request, int $perPage, int $page): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage, $page, [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);
    }

    /**
     * Admin (1) & Manager (3) -- pola persis canManageTargets() di
     * SalesTargetController / canViewAllCustomers() di OdooSync.php.
     */
    private function canViewAllSales($user): bool
    {
        return $user && in_array($user->role_id, [1, 3]);
    }
}