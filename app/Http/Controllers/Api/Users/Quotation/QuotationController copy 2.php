<?php

namespace App\Http\Controllers\Api\Users\Quotation;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuotationValidationIndex;
use App\Http\Requests\QuotationValidationStore;
use App\Http\Requests\QuotationValidationUpdate;
use App\Http\Resources\QuotationResource;
use App\Http\Resources\QuotationResourceCollection;
use App\Models\MsCustomers;
use App\Models\OdooProduct;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\OdooService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * QUOTATIONS (Penawaran) -- Sales & Manager
 * ----------------------------------------------------------------------------
 * Sesuai blueprint "Penawaran (Quotations)": sales bikin penawaran
 * berdasarkan spesifikasi customer, bisa dikonvert ke PDF, cuma bisa dibuat
 * untuk customer yang sudah terdaftar di master data (tabel customers).
 *
 * BEDA dengan Expenses:
 *   - TIDAK ADA approval workflow. Quotation boleh diedit/dihapus bebas
 *     oleh pembuatnya (dianggap dokumen kerja sales, bukan pengajuan yang
 *     perlu di-acc orang lain).
 *   - Manager/Admin cuma bisa LIHAT SEMUA (monitoring, read-only) --
 *     tidak ada approve/reject di controller ini.
 *   - Push ke Odoo (sale.order) DIPICU MANUAL lewat tombol (bukan
 *     otomatis), karena quotation-nya sendiri masih boleh diedit bebas --
 *     kalau auto-push tiap save, bakal numpuk record duplikat di Odoo.
 *     odoo_sale_order_id dipakai buat nentuin create() vs write() pas
 *     push berikutnya.
 *   - Mapping ke Odoo: customer->partner_id pakai AUTO-MATCH BY NAME +
 *     CACHE (pola sama kayak Expense employee mapping) karena tabel
 *     customers CRM belum official synced ke res.partner Odoo. Product
 *     per baris item TIDAK perlu auto-match -- odoo_products sudah
 *     tersync 1:1 dengan ID Odoo asli (tinggal pakai odoo_product_id-nya
 *     langsung), asalkan baris itemnya memang dipilih dari katalog
 *     (bukan ketik manual).
 *
 * PDF: pakai barryvdh/laravel-dompdf, render dari view resources/views/
 * pdf/quotation.blade.php.
 * ============================================================================
 */
class QuotationController extends Controller
{
    protected OdooService $odooService;

    public function __construct(OdooService $odooService)
    {
        $this->odooService = $odooService;
    }

    /**
     * Role 1 = Admin, 3 = Manager -- pola sama persis dengan
     * ExpenseController::canViewAllExpenses().
     */
    private function canViewAllQuotations($user): bool
    {
        return in_array($user->role_id, [1, 3]);
    }

    // ════════════════════════════════════════════
    // LIST (Sales: punya sendiri, Manager/Admin: semua -- read-only monitoring)
    // ════════════════════════════════════════════
    public function index(QuotationValidationIndex $request)
    {
        $validated = $request->validated();
        $user      = auth()->user();
        $perPage   = $validated['per_page'] ?? 10;

        $query = Quotation::query()
            ->with(['sales:id_user,fullname', 'customer:id,company_name']);

        if ($this->canViewAllQuotations($user)) {
            if (!empty($validated['sales_id'])) {
                $query->where('sales_id', $validated['sales_id']);
            }
        } else {
            $query->where('sales_id', $user->id_user);
        }

        if (!empty($validated['customer_id'])) {
            $query->where('customer_id', $validated['customer_id']);
        }

        if (!empty($validated['period_year'])) {
            $query->whereYear('quotation_date', $validated['period_year']);
        }

        if (!empty($validated['period_month'])) {
            $query->whereMonth('quotation_date', $validated['period_month']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('quotation_no', 'ILIKE', "%{$search}%")
                  ->orWhere('customer_ref', 'ILIKE', "%{$search}%")
                  ->orWhere('customer_company_name', 'ILIKE', "%{$search}%")
                  ->orWhereHas('sales', function ($sq) use ($search) {
                      $sq->where('fullname', 'ILIKE', "%{$search}%");
                  });
            });
        }

        $results = $query->orderByDesc('quotation_date')->orderByDesc('id')->paginate($perPage);

        return ApiResponse::paginate(
            QuotationResourceCollection::make($results),
            $results->isEmpty() ? 'Data quotation tidak ditemukan' : 'Success'
        );
    }

    // ════════════════════════════════════════════
    // DETAIL
    // ════════════════════════════════════════════
    public function show($id)
    {
        $user      = auth()->user();
        $quotation = Quotation::with(['sales:id_user,fullname', 'customer:id,company_name', 'items.odooProduct'])
            ->find($id);

        if (!$quotation) {
            return ApiResponse::error('Data quotation tidak ditemukan', 404);
        }

        if (!$this->canViewAllQuotations($user) && (int) $quotation->sales_id !== (int) $user->id_user) {
            return ApiResponse::error('Anda tidak memiliki akses ke data quotation ini', 403);
        }

        return ApiResponse::success(QuotationResource::make($quotation), 'Success');
    }

    // ════════════════════════════════════════════
    // STORE (Sales bikin quotation baru)
    // ════════════════════════════════════════════
    public function store(QuotationValidationStore $request)
    {
        $validated = $request->validated();
        $user      = auth()->user();

        $quotation = DB::transaction(function () use ($validated, $user) {
            $quotation = Quotation::create([
                'sales_id'              => $user->id_user,
                'customer_id'           => $validated['customer_id'],
                'customer_company_name' => $validated['customer_company_name'],
                'customer_address'      => $validated['customer_address'],
                'customer_pic_name'     => $validated['customer_pic_name'],
                'quotation_no'          => $validated['quotation_no'],
                'customer_ref'          => $validated['customer_ref'],
                'payment_terms'         => $validated['payment_terms'],
                'quotation_date'        => $validated['quotation_date'],
                'pages'                 => $validated['pages'] ?? null,
                'validity'              => $validated['validity'],
                'delivery_time'         => $validated['delivery_time'],
                'term'                  => $validated['term'] ?? null,
                'ppn'                   => $validated['ppn'],
                'signature'             => $validated['signature'] ?? null,
                'created_by'            => $user->id_user,
            ]);

            $this->syncItems($quotation, $validated['items']);
            $quotation->recalculateTotals();

            return $quotation;
        });

        return ApiResponse::success(
            QuotationResource::make($quotation->load(['sales:id_user,fullname', 'customer:id,company_name', 'items.odooProduct'])),
            'Quotation berhasil dibuat'
        );
    }

    // ════════════════════════════════════════════
    // UPDATE (cuma pembuatnya sendiri -- TIDAK ADA approval, boleh edit bebas)
    // ════════════════════════════════════════════
    public function update(QuotationValidationUpdate $request, $id)
    {
        $user      = auth()->user();
        $quotation = Quotation::find($id);

        if (!$quotation) {
            return ApiResponse::error('Data quotation tidak ditemukan', 404);
        }

        if ((int) $quotation->sales_id !== (int) $user->id_user) {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengubah quotation ini', 403);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($quotation, $validated) {
            $quotation->update([
                'customer_id'            => $validated['customer_id'],
                'customer_company_name'  => $validated['customer_company_name'],
                'customer_address'       => $validated['customer_address'],
                'customer_pic_name'      => $validated['customer_pic_name'],
                'quotation_no'           => $validated['quotation_no'],
                'customer_ref'           => $validated['customer_ref'],
                'payment_terms'          => $validated['payment_terms'],
                'quotation_date'         => $validated['quotation_date'],
                'pages'                  => $validated['pages'] ?? null,
                'validity'               => $validated['validity'],
                'delivery_time'          => $validated['delivery_time'],
                'term'                   => $validated['term'] ?? null,
                'ppn'                    => $validated['ppn'],
                'signature'              => $validated['signature'] ?? null,
            ]);

            $this->syncItems($quotation, $validated['items']);
            $quotation->recalculateTotals();
        });

        return ApiResponse::success(
            QuotationResource::make($quotation->fresh(['sales:id_user,fullname', 'customer:id,company_name', 'items.odooProduct'])),
            'Quotation berhasil diperbarui -- kalau sebelumnya sudah pernah di-push ke Odoo, jangan lupa klik "Push ke Odoo" lagi supaya datanya ikut ke-update di sana.'
        );
    }

    /**
     * Replace SEMUA baris item quotation (hapus lama, insert baru) --
     * pola full-replace, lebih simpel & konsisten daripada nge-track
     * item mana yang diedit/ditambah/dihapus satu-satu.
     */
    private function syncItems(Quotation $quotation, array $items): void
    {
        $quotation->items()->delete();

        foreach ($items as $index => $item) {
            $qty   = (float) $item['quantity'];
            $price = (float) $item['unit_price'];

            QuotationItem::create([
                'quotation_id'    => $quotation->id,
                'odoo_product_id' => $item['odoo_product_id'] ?? null,
                'description'     => $item['description'],
                'quantity'        => $qty,
                'unit'            => $item['unit'],
                'unit_price'      => $price,
                'total'           => $qty * $price,
                'sort_order'      => $index,
            ]);
        }
    }

    // ════════════════════════════════════════════
    // DESTROY (cuma pembuatnya sendiri, boleh kapan saja -- tidak ada approval)
    // ════════════════════════════════════════════
    public function destroy($id)
    {
        $user      = auth()->user();
        $quotation = Quotation::find($id);

        if (!$quotation) {
            return ApiResponse::error('Data quotation tidak ditemukan', 404);
        }

        if ((int) $quotation->sales_id !== (int) $user->id_user) {
            return ApiResponse::error('Anda tidak memiliki akses untuk menghapus quotation ini', 403);
        }

        $quotation->delete();

        return ApiResponse::success(null, 'Quotation berhasil dihapus');
    }

    // ════════════════════════════════════════════
    // SUMMARY (kartu ringkasan)
    // ════════════════════════════════════════════
    public function summary(Request $request)
    {
        $user = auth()->user();

        $base = Quotation::query();
        if (!$this->canViewAllQuotations($user)) {
            $base->where('sales_id', $user->id_user);
        }

        $totalQuotations  = (clone $base)->count();
        $totalNetAmount   = (clone $base)->sum('net_amount');
        $totalPushed      = (clone $base)->where('odoo_push_status', Quotation::ODOO_PUSH_PUSHED)->count();
        $totalFailedPush  = (clone $base)->where('odoo_push_status', Quotation::ODOO_PUSH_FAILED)->count();

        return ApiResponse::success([
            'total_quotations'   => $totalQuotations,
            'total_net_amount'   => (float) $totalNetAmount,
            'total_pushed'       => $totalPushed,
            'total_failed_push'  => $totalFailedPush,
        ], 'Success');
    }

    // ════════════════════════════════════════════
    // OPTIONS: dropdown-search customer (Master Business Partner)
    // ════════════════════════════════════════════
    // Sama persis polanya dengan ExpenseController::customerOptions()
    // (COALESCE assigned_to/created_by = sales login), cuma di sini
    // dibuat method sendiri (bukan cross-call ke controller Expense)
    // supaya fitur ini tetap self-contained -- plus ikut balikin
    // address & contact_name buat auto-fill ALAMAT/PIC PERUSAHAAN di
    // form.
    public function customerOptions(Request $request)
    {
        $user   = auth()->user();
        $search = $request->input('search');

        $query = DB::table('customers as c')
            ->select(['c.id', 'c.company_name', 'c.address', 'c.contact_name'])
            ->whereRaw('COALESCE(c.assigned_to, c.created_by) = ?', [$user->id_user])
            ->whereNull('c.deleted_at')
            ->whereNotNull('c.company_name')
            ->where('c.company_name', '!=', '')
            ->when($search, function ($q) use ($search) {
                $q->where('c.company_name', 'ILIKE', "%{$search}%");
            })
            ->orderBy('c.company_name')
            ->limit(20);

        $results = $query->get()->map(fn ($row) => [
            'id'           => $row->id,
            'label'        => $row->company_name,
            'address'      => $row->address,
            'contact_name' => $row->contact_name,
        ]);

        return ApiResponse::success($results, 'Success');
    }

    // ════════════════════════════════════════════
    // OPTIONS: dropdown-search product (dari katalog odoo_products yang
    // sudah tersync -- lihat ProductController/SyncOdooProducts)
    // ════════════════════════════════════════════
    public function productOptions(Request $request)
    {
        $search = $request->input('search');

        $query = OdooProduct::query()
            ->where('active', true)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'ILIKE', "%{$search}%")
                       ->orWhere('default_code', 'ILIKE', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(20);

        $results = $query->get()->map(fn ($p) => [
            'id'         => $p->id,
            'label'      => $p->name . ($p->default_code ? " ({$p->default_code})" : ''),
            'name'       => $p->name,
            'unit'       => $p->uom_name,
            'unit_price' => (float) $p->list_price,
        ]);

        return ApiResponse::success($results, 'Success');
    }

    // ════════════════════════════════════════════
    // DOWNLOAD PDF
    // ════════════════════════════════════════════
    public function downloadPdf($id)
    {
        $user      = auth()->user();
        $quotation = Quotation::with(['sales:id_user,fullname', 'customer:id,company_name', 'items.odooProduct'])
            ->find($id);

        if (!$quotation) {
            return ApiResponse::error('Data quotation tidak ditemukan', 404);
        }

        if (!$this->canViewAllQuotations($user) && (int) $quotation->sales_id !== (int) $user->id_user) {
            return ApiResponse::error('Anda tidak memiliki akses ke data quotation ini', 403);
        }

        $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $quotation]);

        return $pdf->download($this->buildPdfFilename($quotation));
    }

    /**
     * Nama file PDF dasarnya pakai customer_ref (BUKAN quotation_no lagi --
     * quotation_no sekarang opsional/sering masih kosong pas quotation baru
     * dibuat, lihat migration make_quotation_no_nullable, jadi kurang cocok
     * dipakai sebagai nama file). customer_ref WAJIB diisi (lihat
     * QuotationValidationStore), jadi selalu ada isinya.
     *
     * Tetap disanitize dari "/" dan "\" (siapa tau customer_ref ada karakter
     * itu juga) -- karakter itu TIDAK BOLEH ada di nama file pada header
     * Content-Disposition, Symfony bakal lempar InvalidArgumentException
     * ("The filename and the fallback cannot contain the "/" and "\"
     * characters.") kalau dibiarkan apa adanya.
     */
    private function buildPdfFilename(Quotation $quotation): string
    {
        $safeRef = str_replace(['/', '\\'], '-', $quotation->customer_ref);

        return "Quotation-{$safeRef}.pdf";
    }

    // ════════════════════════════════════════════════════════════════════
    // ── PUSH KE ODOO (sale.order) -- DIPICU MANUAL, bukan otomatis ──
    // ════════════════════════════════════════════════════════════════════
    public function pushToOdoo($id)
    {
        $user      = auth()->user();
        $quotation = Quotation::with(['customer', 'items.odooProduct'])->find($id);

        if (!$quotation) {
            return ApiResponse::error('Data quotation tidak ditemukan', 404);
        }

        $isOwner = (int) $quotation->sales_id === (int) $user->id_user;
        if (!$this->canViewAllQuotations($user) && !$isOwner) {
            return ApiResponse::error('Anda tidak memiliki akses ke quotation ini', 403);
        }

        $this->pushQuotationToOdoo($quotation);

        return ApiResponse::success(
            QuotationResource::make($quotation->fresh(['sales:id_user,fullname', 'customer:id,company_name', 'items.odooProduct'])),
            $quotation->odoo_push_status === Quotation::ODOO_PUSH_PUSHED
                ? 'Berhasil dikirim ke Odoo'
                : 'Gagal dikirim ke Odoo (lihat catatan error) -- perlu ditindaklanjuti manual'
        );
    }

    /**
     * Push (create) ATAU update (write) quotation ini sebagai sale.order
     * di Odoo. Semua error ditangkap & dicatat ke kolom odoo_push_* --
     * TIDAK melempar exception ke caller.
     *
     * CATATAN keterbatasan (belum dipetakan ke Odoo, dimasukkan sebagai
     * teks di field `note` aja): PAYMENT TERMS, VALIDITY, DELIVERY TIME,
     * TERM. Field-field itu masing-masing idealnya map ke
     * payment_term_id (Many2one account.payment.term) dan validity_date
     * (Date) di Odoo, tapi itu perlu auto-match/parsing tambahan yang
     * belum dikonfirmasi ke user -- kalau nanti dibutuhkan, tinggal
     * ditambah sama pola auto-match+cache seperti employee/kategori di
     * fitur Expenses.
     */
    private function pushQuotationToOdoo(Quotation $quotation): void
    {
        try {
            $quotation->loadMissing(['customer', 'items.odooProduct']);

            $partnerId = $this->resolveOdooPartnerId($quotation->customer);
            if (!$partnerId) {
                throw new \Exception(
                    "Tidak ditemukan partner Odoo dengan nama persis \"{$quotation->customer->company_name}\" (atau namanya ambigu/lebih dari 1 match). "
                    . 'Silakan cek manual data Contact/Customer di Odoo, lalu isi kolom odoo_partner_id di customer ini.'
                );
            }

            $orderLines = [];
            foreach ($quotation->items as $item) {
                $odooProductId = $item->odooProduct?->odoo_product_id;

                if (!$odooProductId) {
                    throw new \Exception(
                        "Baris item \"{$item->description}\" belum terhubung ke product dari katalog Odoo. "
                        . 'Pilih product dari katalog (bukan ketik manual) buat baris ini supaya bisa di-push ke Odoo.'
                    );
                }

                $orderLines[] = [0, 0, [
                    'product_id'      => $odooProductId,
                    'name'            => $item->description,
                    'product_uom_qty' => (float) $item->quantity,
                    'price_unit'      => (float) $item->unit_price,
                ]];
            }

            // ── Fix "company crossover" (sama seperti bug yang sudah
            // diperbaiki di fitur Expenses): kalau company_id sale.order
            // tidak di-set eksplisit, Odoo pakai company default dari
            // context API user, yang bisa saja beda company dengan
            // company pemilik PRODUCT di baris item-nya -> Odoo nolak
            // dengan error "no company crossover is allowed". Solusinya
            // sama: ambil company_id ASLI dari product-nya di Odoo (fresh
            // searchRead, bukan asumsi), lalu di-set eksplisit di
            // payload. Diasumsikan semua baris item 1 quotation dari
            // company Odoo yang sama -- kalau ternyata beda-beda, kita
            // lempar error yang jelas daripada diam-diam salah pilih. ──
            $odooProductIds = collect($quotation->items)
                ->pluck('odooProduct.odoo_product_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $companyId = null;
            if (!empty($odooProductIds)) {
                $productRows = $this->odooService->searchRead(
                    'product.product', [['id', 'in', $odooProductIds]], ['id', 'company_id'], 0
                );

                $companyIds = collect($productRows)
                    ->map(fn ($p) => $p['company_id'][0] ?? null)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (count($companyIds) > 1) {
                    throw new \Exception(
                        'Baris item quotation ini berasal dari lebih dari 1 company Odoo yang berbeda (product-nya tidak satu company). '
                        . 'Sale order tidak bisa dibuat lintas company -- pisahkan jadi quotation terpisah per company.'
                    );
                }

                $companyId = $companyIds[0] ?? null;
            }

            $values = [
                'partner_id'       => $partnerId,
                'client_order_ref' => $quotation->customer_ref,
                'date_order'       => optional($quotation->quotation_date)->toDateTimeString(),
                'note'             => $this->buildOdooNote($quotation),
            ];

            if ($companyId) {
                $values['company_id'] = $companyId;
            }

            if ($quotation->odoo_sale_order_id) {
                // Update record yang sudah ada -- [5,0,0] = unlink SEMUA
                // order_line lama dulu, baru diisi ulang dari data
                // terbaru di CRM (full replace, konsisten sama
                // syncItems() di sisi CRM).
                $values['order_line'] = array_merge([[5, 0, 0]], $orderLines);
                $this->odooService->write('sale.order', (int) $quotation->odoo_sale_order_id, $values);
                $odooSaleOrderId = (int) $quotation->odoo_sale_order_id;
            } else {
                $values['order_line'] = $orderLines;
                $odooSaleOrderId = $this->odooService->create('sale.order', $values);
            }

            $quotation->update([
                'odoo_sale_order_id' => $odooSaleOrderId,
                'odoo_push_status'   => Quotation::ODOO_PUSH_PUSHED,
                'odoo_push_error'    => null,
                'odoo_pushed_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal push quotation #' . $quotation->id . ' ke Odoo: ' . $e->getMessage());

            $quotation->update([
                'odoo_push_status' => Quotation::ODOO_PUSH_FAILED,
                'odoo_push_error'  => $e->getMessage(),
                'odoo_pushed_at'   => now(),
            ]);
        }
    }

    private function buildOdooNote(Quotation $quotation): string
    {
        return implode("\n", array_filter([
            $quotation->payment_terms ? "Payment Terms: {$quotation->payment_terms}" : null,
            $quotation->validity ? "Validity: {$quotation->validity}" : null,
            $quotation->delivery_time ? "Delivery Time: {$quotation->delivery_time}" : null,
            $quotation->term ? "Term: {$quotation->term}" : null,
        ]));
    }

    /**
     * AUTO-MATCH BY NAME + CACHE -- resolve partner_id Odoo (res.partner)
     * buat 1 customer CRM. Cache-nya di customers.odoo_partner_id
     * (mirip ms_users.odoo_employee_id di fitur Expenses).
     */
    private function resolveOdooPartnerId(MsCustomers $customer): ?int
    {
        if ($customer->odoo_partner_id) {
            return (int) $customer->odoo_partner_id;
        }

        $matches = $this->odooService->searchRead(
            'res.partner',
            [['name', '=', $customer->company_name]],
            ['id', 'name'],
            2
        );

        if (count($matches) !== 1) {
            return null;
        }

        $partnerId   = (int) $matches[0]['id'];
        $partnerName = $matches[0]['name'];

        $customer->update([
            'odoo_partner_id'   => $partnerId,
            'odoo_partner_name' => $partnerName,
        ]);

        return $partnerId;
    }
}