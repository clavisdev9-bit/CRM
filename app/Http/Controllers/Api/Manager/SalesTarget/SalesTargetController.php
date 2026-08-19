<?php

namespace App\Http\Controllers\Api\Manager\SalesTarget;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\SalesTargetValidationIndex;
use App\Http\Requests\SalesTargetValidationStore;
use App\Http\Resources\SalesTargetResource;
use App\Http\Resources\SalesTargetResourceCollection;
use App\Models\SalesTarget;
use App\Models\CustomerSalesAssignmentOdoo;
use App\Models\OdooCustomerPurchaseItem;
use App\Models\MsUsers;
use App\Models\OdooCustomer;
use Illuminate\Http\Request;

/**
 * ============================================================================
 * SALES TARGET (target penjualan) -- Admin & Manager
 * ----------------------------------------------------------------------------
 * Manager/Admin kasih sales sebuah target penjualan tahunan (misal "Bagir:
 * 500 juta untuk 2026"), boleh total (odoo_customer_id NULL) atau dipecah
 * per customer Odoo tertentu (odoo_customer_id diisi). Angka "sudah tercapai"
 * (achieved_amount) TIDAK disimpan di DB -- dihitung dinamis tiap request
 * dari data yang udah di-sync dari Odoo:
 *
 *   sales_targets (target_amount, per sales+tahun[+customer])
 *        |
 *        v  sales_id --> odoo_customer_id mana aja yang di-assign ke sales
 *                         ini (lewat CustomerSalesAssignmentOdoo, BUKAN
 *                         lewat pattern "sales efektif" customers/
 *                         customer_branches yang dipakai modul CRM lain)
 *        |
 *        v  odoo_customer_id --> SUM(qty * price_unit) di
 *                         odoo_customer_purchase_items, difilter tahun
 *                         order_date-nya (+ difilter ke 1 customer kalau
 *                         target-nya per-customer)
 *
 * CATATAN AKSES: controller ini pakai `role_id in [1, 3]` (Admin, Manager)
 * -- BUKAN helper isManager() yang dipakai ProductController/
 * SalesActivityDashboardController (yang ngecek nama role di tabel ms_role).
 * Ini SENGAJA disamakan dengan pola canViewAllCustomers() di OdooSync.php,
 * karena fitur ini satu ekosistem data sama customer/purchase Odoo (dan
 * OdooSync.php sudah lebih dulu pakai role_id 1/3 buat itu). Kalau ternyata
 * project ini maunya konsisten pakai isManager() di semua tempat, kasih tau
 * ya biar diseragamkan.
 *
 * CATATAN LAIN: nama model `OdooCustomerPurchaseItem` di bawah ini saya
 * tebak dari nama tabel odoo_customer_purchase_items (ngikutin pola
 * OdooProduct <-> odoo_products). Kalau model asli kamu namanya beda
 * (misal cuma "CustomerPurchaseItem" kayak alias yang dipakai di
 * OdooSync.php), tinggal disesuaikan use statement-nya di atas.
 * ============================================================================
 */
class SalesTargetController extends Controller
{
    /**
     * GET /sales-targets
     * Admin/Manager: lihat semua target (bisa difilter sales_id/period_year).
     * Sales: cuma lihat target dia sendiri.
     */
    public function index(SalesTargetValidationIndex $request)
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            $query = SalesTarget::with([
                'salesUser:id_user,fullname',
                'odooCustomer:odoo_partner_id,name',
                'creator:id_user,fullname',
            ]);

            if ($this->canManageTargets($user)) {
                if (!empty($validated['sales_id'])) {
                    $query->where('sales_id', $validated['sales_id']);
                }
            } else {
                $query->where('sales_id', $user->id_user);
            }

            $periodYear = $validated['period_year'] ?? now()->year;
            $query->where('period_year', $periodYear);

            // Search by nama sales ATAU nama customer -- dua-duanya relasi,
            // jadi pakai whereHas (bukan kolom langsung di sales_targets).
            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereHas('salesUser', fn ($sq) => $sq->where('fullname', 'ILIKE', "%{$search}%"))
                        ->orWhereHas('odooCustomer', fn ($sq) => $sq->where('name', 'ILIKE', "%{$search}%"));
                });
            }

            $perPage = $validated['per_page'] ?? 10;

            $results = $query
                ->orderByDesc('period_year')
                ->orderBy('sales_id')
                ->orderByRaw('odoo_customer_id IS NULL DESC') // target total duluan, baru per-customer
                ->paginate($perPage);

            // achieved_amount dihitung dinamis per baris (ga ada di DB)
            $results->getCollection()->transform(function (SalesTarget $target) {
                $target->achieved_amount = $this->computeAchievedAmount(
                    $target->sales_id,
                    $target->period_year,
                    $target->odoo_customer_id
                );
                return $target;
            });

            return ApiResponse::paginate(
                SalesTargetResourceCollection::make($results),
                $results->isEmpty() ? 'Data sales target not found' : 'Success'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load sales targets', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /sales-targets/{id}/detail
     * Breakdown di balik angka "Tercapai" -- diklik dari ikon detail di
     * Daftar Target. Bentuknya beda tergantung tipe target-nya:
     *
     * - Target PER-CUSTOMER (odoo_customer_id diisi): nampilin daftar
     *   TRANSAKSI customer itu di tahun target-nya (mirip modal "Customer
     *   Purchase Detail" yang udah ada di halaman Customer History).
     * - Target TOTAL (odoo_customer_id NULL): nampilin breakdown PER
     *   CUSTOMER -- semua customer yang di-assign ke sales itu, masing-
     *   masing sama jumlah tercapainya di tahun ini (biar manager tau
     *   customer mana yang paling nyumbang ke angka total-nya).
     */
    public function detail(Request $request, $id)
    {
        try {
            $user = $request->user();

            $target = SalesTarget::with(['salesUser:id_user,fullname', 'odooCustomer:odoo_partner_id,name'])
                ->find($id);

            if (!$target) {
                return ApiResponse::error('Sales target not found.', [], 404);
            }

            if (!$this->canManageTargets($user) && $target->sales_id !== $user->id_user) {
                return ApiResponse::error('Unauthorized.', [], 403);
            }

            // ── TARGET PER-CUSTOMER: daftar transaksi customer itu ──
            if (!empty($target->odoo_customer_id)) {
                $transactions = OdooCustomerPurchaseItem::where('odoo_customer_id', $target->odoo_customer_id)
                    ->whereYear('order_date', $target->period_year)
                    ->orderByDesc('order_date')
                    ->get(['order_name', 'order_date', 'product_name', 'product_code', 'qty', 'price_unit']);

                $target->achieved_amount = (float) $transactions->sum(fn ($t) => $t->qty * $t->price_unit);

                return ApiResponse::success([
                    'type'         => 'customer',
                    'target'       => new SalesTargetResource($target),
                    'transactions' => $transactions->map(fn ($t) => [
                        'order_name'   => $t->order_name,
                        'order_date'   => optional($t->order_date)->format('Y-m-d'),
                        'product_name' => $t->product_name,
                        'product_code' => $t->product_code,
                        'qty'          => (float) $t->qty,
                        'price_unit'   => (float) $t->price_unit,
                        'subtotal'     => (float) $t->qty * (float) $t->price_unit,
                    ])->values(),
                ], 'Success');
            }

            // ── TARGET TOTAL: breakdown per customer assignment sales ini ──
            $assignedCustomerIds = CustomerSalesAssignmentOdoo::where('sales_id', $target->sales_id)
                ->pluck('odoo_customer_id');

            if ($assignedCustomerIds->isEmpty()) {
                $target->achieved_amount = 0.0;
                return ApiResponse::success([
                    'type'      => 'total',
                    'target'    => new SalesTargetResource($target),
                    'customers' => [],
                ], 'Success');
            }

            $sums = OdooCustomerPurchaseItem::whereIn('odoo_customer_id', $assignedCustomerIds)
                ->whereYear('order_date', $target->period_year)
                ->selectRaw('odoo_customer_id, COUNT(*) as transaction_count, COALESCE(SUM(qty * price_unit), 0) as achieved_amount')
                ->groupBy('odoo_customer_id')
                ->get()
                ->keyBy('odoo_customer_id');

            $customerNames = OdooCustomer::whereIn('odoo_partner_id', $assignedCustomerIds)
                ->pluck('name', 'odoo_partner_id');

            $customerBreakdown = $assignedCustomerIds->map(function ($custId) use ($sums, $customerNames) {
                $row = $sums->get($custId);
                return [
                    'odoo_customer_id'  => $custId,
                    'customer_name'     => $customerNames->get($custId) ?? ('#' . $custId),
                    'transaction_count' => (int) ($row->transaction_count ?? 0),
                    'achieved_amount'   => (float) ($row->achieved_amount ?? 0),
                ];
            })->sortByDesc('achieved_amount')->values();

            $target->achieved_amount = (float) $customerBreakdown->sum('achieved_amount');

            return ApiResponse::success([
                'type'      => 'total',
                'target'    => new SalesTargetResource($target),
                'customers' => $customerBreakdown,
            ], 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load sales target detail', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /sales-targets/summary?period_year=2026
     * Rekap per sales: total target VS total tercapai (dipakai buat
     * dashboard/progress bar). Cuma hitung target TOTAL (odoo_customer_id
     * NULL) per sales, biar ga dobel-hitung sama target per-customer-nya.
     */
    public function summary(Request $request)
    {
        try {
            $user = $request->user();
            $periodYear = (int) ($request->query('period_year') ?? now()->year);

            $query = SalesTarget::whereNull('odoo_customer_id')
                ->where('period_year', $periodYear)
                ->with('salesUser:id_user,fullname');

            if ($this->canManageTargets($user)) {
                if ($request->filled('sales_id')) {
                    $query->where('sales_id', $request->query('sales_id'));
                }
            } else {
                $query->where('sales_id', $user->id_user);
            }

            $targets = $query->get();

            $summary = $targets->map(function (SalesTarget $target) {
                $achieved = $this->computeAchievedAmount($target->sales_id, $target->period_year, null);
                $targetAmount = (float) $target->target_amount;

                return [
                    'sales_id'            => $target->sales_id,
                    'sales_name'          => $target->salesUser?->fullname ?? '-',
                    'period_year'         => $target->period_year,
                    'target_amount'       => $targetAmount,
                    'achieved_amount'     => $achieved,
                    'achievement_percent' => $targetAmount > 0 ? round(($achieved / $targetAmount) * 100, 2) : 0,
                ];
            })->values();

            return ApiResponse::success([
                'period_year' => $periodYear,
                'rows'        => $summary,
                'grand_total' => [
                    'target_amount'   => (float) $summary->sum('target_amount'),
                    'achieved_amount' => (float) $summary->sum('achieved_amount'),
                ],
            ], 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load sales target summary', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /sales-targets/options/sales
     * Dipakai buat dropdown "Sales" di form Tambah/Edit Target -- pola
     * SAMA kayak dropdown "Pilih Sales" di form Target Visit (di-load
     * PENUH sekali pas halaman/modal dibuka, difilter di FRONTEND pas
     * user ngetik di kotak cari dalam dropdown-nya, BUKAN nge-hit
     * endpoint ini tiap ngetik). Query-nya niru persis salesList() di
     * OdooSync.php (role_id 2 = Sales, is_active = true).
     *
     * ?search= tetap didukung buat jaga-jaga kalau nanti mau dipanggil
     * sebagai server-search juga, tapi limit-nya sengaja digedein
     * (bukan 20 kayak dropdown customer) soalnya endpoint ini emang
     * dimaksudkan buat balikin "semua sales", bukan sebagian hasil cari.
     */
    public function salesOptions(Request $request)
    {
        try {
            $search = $request->query('search');

            $query = MsUsers::where('role_id', 2)->where('is_active', true);

            if (!empty($search)) {
                $query->where('fullname', 'ILIKE', "%{$search}%");
            }

            $sales = $query->orderBy('fullname')->limit(500)->get(['id_user', 'fullname']);

            return ApiResponse::success(
                $sales->map(fn ($s) => ['value' => $s->id_user, 'label' => $s->fullname])->values(),
                'Success'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load sales options', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /sales-targets/options/customers?sales_id=12&search=sinar
     * Dropdown-search "Customer Odoo" di form Tambah/Edit Target (opsional,
     * buat target per-customer).
     *
     * DI-SCOPE ke sales_id yang lagi dipilih di form -- SAMA PERSIS pola
     * "Pilih Customer" di form Target Visit (customer BARU keisi setelah
     * Sales dipilih, lewat loadCustomersForSales() di visitTargetView.vue).
     * Ini bukan cuma soal UX: achieved_amount ($this->computeAchievedAmount())
     * cuma ngitung dari customer yang di-assign ke sales yang sama kayak
     * target-nya (lewat CustomerSalesAssignmentOdoo). Kalau manager sampai
     * milih customer yang BUKAN assignment sales itu, target per-customer-nya
     * mustahil kesentuh (achieved_amount bakal permanen 0) -- makanya
     * dropdown ini sengaja dibatasi biar kombinasi yang salah itu ga
     * mungkin kepilih dari awal.
     *
     * Kalau sales_id belum dikirim (misal user belum milih Sales), balikin
     * list kosong -- frontend juga nge-disable tombol ini sebelum Sales
     * dipilih.
     *
     * UPDATE: dipindah pakai scopeFilterBySales() & scopeSearch() yang UDAH
     * ADA di model OdooCustomer (whereHas('assignment', ...)) -- BUKAN
     * nulis ulang query manual ke CustomerSalesAssignmentOdoo kayak versi
     * sebelumnya. Efeknya sama, tapi ini pakai jalur yang emang udah
     * disediain modelnya sendiri (sama kayak customerPopulation() di
     * OdooSync.php yang juga pakai scope-scope ini).
     *
     * CATATAN: filter `is_company = true` yang sempet ditambahin buat buang
     * child-contact ber-nama "." udah DILEPAS -- soalnya malah ikut mbuang
     * customer beneran (kolom is_company kayaknya ga konsisten ke-set true
     * pas sync). Cukup jaga-jaga buang nama kosong/"." aja.
     */
    public function customerOptions(Request $request)
    {
        try {
            $salesId = $request->query('sales_id');

            if (empty($salesId)) {
                return ApiResponse::success([], 'Success');
            }

            $search = $request->query('search');

            $customers = OdooCustomer::query()
                ->filterBySales((int) $salesId)
                ->search($search)
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->where('name', '!=', '.')
                ->orderBy('name')
                ->limit(50)
                ->get(['odoo_partner_id', 'name']);

            return ApiResponse::success(
                $customers->map(fn ($c) => ['value' => $c->odoo_partner_id, 'label' => $c->name])->values(),
                'Success'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load customer options', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /sales-targets -- Admin/Manager only.
     */
    public function store(SalesTargetValidationStore $request)
    {
        if (!$this->canManageTargets($request->user())) {
            return ApiResponse::error('Unauthorized. Hanya Admin/Manager yang bisa membuat target penjualan.', [], 403);
        }

        try {
            $data = $request->validated();

            if ($this->targetAlreadyExists($data['sales_id'], $data['period_year'], $data['odoo_customer_id'] ?? null)) {
                return ApiResponse::error(
                    empty($data['odoo_customer_id'])
                        ? 'Target total sales ini untuk tahun tersebut sudah ada.'
                        : 'Target untuk customer ini di tahun tersebut sudah ada.',
                    [],
                    422
                );
            }

            $data['created_by'] = $request->user()->id_user;

            $target = SalesTarget::create($data);
            $target->load(['salesUser:id_user,fullname', 'odooCustomer:odoo_partner_id,name', 'creator:id_user,fullname']);
            $target->achieved_amount = $this->computeAchievedAmount($target->sales_id, $target->period_year, $target->odoo_customer_id);

            return ApiResponse::success(new SalesTargetResource($target), 'Target penjualan berhasil dibuat.');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to create sales target', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * PUT /sales-targets/{id} -- Admin/Manager only.
     */
    public function update(SalesTargetValidationStore $request, $id)
    {
        if (!$this->canManageTargets($request->user())) {
            return ApiResponse::error('Unauthorized. Hanya Admin/Manager yang bisa mengubah target penjualan.', [], 403);
        }

        try {
            $target = SalesTarget::find($id);
            if (!$target) {
                return ApiResponse::error('Sales target not found.', [], 404);
            }

            $data = $request->validated();

            if ($this->targetAlreadyExists($data['sales_id'], $data['period_year'], $data['odoo_customer_id'] ?? null, $target->id)) {
                return ApiResponse::error('Kombinasi sales + tahun + customer ini sudah dipakai target lain.', [], 422);
            }

            $target->update($data);
            $target->load(['salesUser:id_user,fullname', 'odooCustomer:odoo_partner_id,name', 'creator:id_user,fullname']);
            $target->achieved_amount = $this->computeAchievedAmount($target->sales_id, $target->period_year, $target->odoo_customer_id);

            return ApiResponse::success(new SalesTargetResource($target), 'Target penjualan berhasil diupdate.');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to update sales target', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * DELETE /sales-targets/{id} -- Admin/Manager only. Soft delete.
     */
    public function destroy(Request $request, $id)
    {
        if (!$this->canManageTargets($request->user())) {
            return ApiResponse::error('Unauthorized. Hanya Admin/Manager yang bisa menghapus target penjualan.', [], 403);
        }

        try {
            $target = SalesTarget::find($id);
            if (!$target) {
                return ApiResponse::error('Sales target not found.', [], 404);
            }

            $target->delete();

            return ApiResponse::success(null, 'Target penjualan berhasil dihapus.');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to delete sales target', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Hitung realisasi (achieved_amount) buat 1 sales, di 1 tahun, opsional
     * dibatasi ke 1 customer Odoo tertentu. Dihitung dari
     * odoo_customer_purchase_items, di-scope lewat customer-customer yang
     * di-assign ke sales itu (CustomerSalesAssignmentOdoo) -- BUKAN dari
     * kolom sales_id di purchase item, karena tabel itu emang ga punya
     * kolom itu (sales attribution-nya emang lewat assignment terpisah).
     */
    private function computeAchievedAmount(int $salesId, int $periodYear, ?int $odooCustomerId = null): float
    {
        $assignedCustomerIds = CustomerSalesAssignmentOdoo::where('sales_id', $salesId)
            ->pluck('odoo_customer_id');

        if ($assignedCustomerIds->isEmpty()) {
            return 0.0;
        }

        $query = OdooCustomerPurchaseItem::whereIn('odoo_customer_id', $assignedCustomerIds)
            ->whereYear('order_date', $periodYear);

        if (!empty($odooCustomerId)) {
            $query->where('odoo_customer_id', $odooCustomerId);
        }

        return (float) $query->selectRaw('COALESCE(SUM(qty * price_unit), 0) as total')->value('total');
    }

    /**
     * Cek duplikat target sebelum insert/update -- ini validasi tambahan di
     * level aplikasi (biar error message-nya jelas), yang sebenarnya sudah
     * dijamin juga di level DB lewat 2 partial unique index di migration
     * (sales_targets_total_unique / sales_targets_per_customer_unique).
     */
    private function targetAlreadyExists(int $salesId, int $periodYear, ?int $odooCustomerId, ?int $exceptId = null): bool
    {
        $query = SalesTarget::where('sales_id', $salesId)
            ->where('period_year', $periodYear);

        if (empty($odooCustomerId)) {
            $query->whereNull('odoo_customer_id');
        } else {
            $query->where('odoo_customer_id', $odooCustomerId);
        }

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Admin (1) & Manager (3) -- pola persis canViewAllCustomers() di
     * OdooSync.php.
     */
    private function canManageTargets($user): bool
    {
        return $user && in_array($user->role_id, [1, 3]);
    }
}