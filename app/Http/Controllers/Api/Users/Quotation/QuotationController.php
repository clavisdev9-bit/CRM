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
use App\Models\MsUsers;
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
 *
 * COMPANY SCOPING (ditambahkan belakangan): Manager (role_id 3) sebelumnya
 * lihat/monitoring quotation dari SEMUA sales lintas company (cuma "lihat
 * semua vs lihat punya sendiri" yang dibedain, company sama sekali belum
 * dibedain) -- sama persis bug yang sudah diperbaiki di
 * SalesTargetController/ReportProductBySalesController/ExpenseController.
 * Sekarang di-scope lewat group_id di ms_users (dicocokin LANGSUNG ke
 * $user->group_id, TANPA mapping Odoo -- pola sama persis
 * salesInSameCompany() di ExpenseController/SalesTargetController).
 * Administrator/IT (role_id 1) tetap full akses lintas company di semua
 * method. productOptions() (katalog product Odoo) di-scope TERPISAH lewat
 * scopedOdooCompanyId(), karena company_id di odoo_products itu company_id
 * ASLI Odoo (hasil mapping group_companies.odoo_company_id), bukan group_id
 * CRM langsung -- pola sama persis ProductController::index().
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

    /**
     * True kalau sales dengan id $salesId ada di company yang SAMA kayak
     * $user (group_id ms_users dicocokin LANGSUNG, tanpa mapping Odoo --
     * pola persis salesInSameCompany() di ExpenseController/
     * SalesTargetController). HANYA dipanggil di tempat yang sudah
     * mengecek role_id !== 1 duluan.
     */
    private function salesInSameCompany(int $salesId, $user): bool
    {
        return MsUsers::where('id_user', $salesId)
            ->where('group_id', $user->group_id)
            ->exists();
    }

    /**
     * odoo_company_id company CRM tempat $user berada -- dipakai buat
     * scoping tabel yang company_id-nya ASLI dari Odoo (odoo_products),
     * BEDA sama group_id di ms_users yang langsung dicocokin tanpa mapping
     * (lihat salesInSameCompany()). Pola sama persis
     * SalesTargetController::scopedOdooCompanyId(). HANYA dipanggil di
     * tempat yang sudah mengecek role_id !== 1 duluan.
     */
    private function scopedOdooCompanyId($user): ?int
    {
        return DB::table('group_companies')
            ->where('id_group', $user->group_id)
            ->value('odoo_company_id');
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

            // Company scoping: Manager (role_id 3) cuma boleh lihat
            // quotation dari sales company-nya sendiri. Administrator/IT
            // (role_id 1) full akses lintas company.
            if ((int) $user->role_id !== 1) {
                $query->whereHas('sales', function ($q) use ($user) {
                    $q->where('group_id', $user->group_id);
                });
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

        if ($this->canViewAllQuotations($user)) {
            // Company scoping: Manager cuma boleh lihat detail quotation
            // dari sales company-nya sendiri. Dibalikin "tidak ditemukan",
            // bukan "tidak punya akses" -- biar ga bocorin informasi kalau
            // quotation itu sebenarnya ada tapi di company lain (pola sama
            // persis ExpenseController::show()).
            if ((int) $user->role_id !== 1 && !$this->salesInSameCompany((int) $quotation->sales_id, $user)) {
                return ApiResponse::error('Data quotation tidak ditemukan', 404);
            }
        } elseif ((int) $quotation->sales_id !== (int) $user->id_user) {
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
        if ($this->canViewAllQuotations($user)) {
            // Company scoping: Manager cuma ngitung quotation dari sales
            // company-nya sendiri. Administrator/IT full akses.
            if ((int) $user->role_id !== 1) {
                $base->whereHas('sales', function ($q) use ($user) {
                    $q->where('group_id', $user->group_id);
                });
            }
        } else {
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
    // Company scoping: product yang company_id-nya NULL dianggap
    // shared/global (Odoo company_id = false), kelihatan buat semua
    // company. Selain itu, cuma product yang company_id-nya cocok sama
    // odoo_company_id milik company (group) user yang login yang
    // ditampilkan -- pola sama persis ProductController::index(). Kalau
    // tidak di-scope, sales bisa pilih product company LAIN buat item
    // quotation-nya, yang ujungnya bakal ditolak Odoo pas push ("no
    // company crossover is allowed") atau malah salah pilih company sale
    // order-nya. Administrator/IT (role_id 1) tetap full akses semua
    // company.
    public function productOptions(Request $request)
    {
        $user   = auth()->user();
        $search = $request->input('search');

        $query = OdooProduct::query()->where('active', true);

        if ((int) $user->role_id !== 1) {
            $odooCompanyId = $this->scopedOdooCompanyId($user);

            $query->where(function ($q) use ($odooCompanyId) {
                $q->whereNull('company_id');

                if ($odooCompanyId) {
                    $q->orWhere('company_id', $odooCompanyId);
                }
            });
        }

        $query
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

        if ($this->canViewAllQuotations($user)) {
            // Company scoping: sama pola kayak show().
            if ((int) $user->role_id !== 1 && !$this->salesInSameCompany((int) $quotation->sales_id, $user)) {
                return ApiResponse::error('Data quotation tidak ditemukan', 404);
            }
        } elseif ((int) $quotation->sales_id !== (int) $user->id_user) {
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
        $quotation = Quotation::with(['sales', 'customer', 'items.odooProduct'])->find($id);

        if (!$quotation) {
            return ApiResponse::error('Data quotation tidak ditemukan', 404);
        }

        $isOwner = (int) $quotation->sales_id === (int) $user->id_user;
        if (!$this->canViewAllQuotations($user) && !$isOwner) {
            return ApiResponse::error('Anda tidak memiliki akses ke quotation ini', 403);
        }

        // Company scoping: Manager cuma boleh push quotation dari sales
        // company-nya sendiri. Sales pemilik (owner) selalu boleh, sudah
        // ditangani lewat $isOwner di atas -- ini cuma buat jalur
        // Manager/Admin (pola sama persis ExpenseController::destroy()).
        if (!$isOwner && (int) $user->role_id !== 1 && !$this->salesInSameCompany((int) $quotation->sales_id, $user)) {
            return ApiResponse::error('Data quotation tidak ditemukan', 404);
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
     *
     * Salesperson (user_id) di sale.order DIISI juga sekarang lewat
     * resolveOdooUserId() -- auto-match by name + cache ke res.users,
     * sama pola persis employee/kategori. BEDA dengan partner_id/product_id
     * di atas, field ini TIDAK WAJIB ketemu -- kalau sales-nya tidak
     * ketemu/ambigu di res.users, push tetap lanjut (Salesperson-nya
     * dikosongkan di Odoo), tidak sampai bikin seluruh quotation gagal
     * push cuma gara-gara 1 field non-esensial ini.
     */
    private function pushQuotationToOdoo(Quotation $quotation): void
    {
        try {
            $quotation->loadMissing(['sales', 'customer', 'items.odooProduct']);

            $partnerId = $this->resolveOdooPartnerId($quotation->customer);
            if (!$partnerId) {
                throw new \Exception(
                    "Gagal menentukan partner Odoo untuk customer \"{$quotation->customer->company_name}\". "
                    . 'Kemungkinan ada lebih dari 1 contact di Odoo dengan nama yang sama persis (ambigu -- sistem sengaja TIDAK auto-create supaya tidak nambah duplikat baru), '
                    . 'atau auto-create contact baru sempat gagal (cek storage/logs/laravel.log buat detail error dari Odoo). '
                    . 'Silakan cek manual data Contact/Customer di Odoo, lalu isi kolom odoo_partner_id di customer ini kalau perlu.'
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

            // Salesperson -- lihat resolveOdooUserId(). Kalau tidak ketemu
            // (0/ambigu match di res.users), field ini dibiarkan tidak
            // dikirim sama sekali, jadi Odoo tetap boleh isi/kosongkan
            // sendiri (bukan dipaksa 0/kosong secara eksplisit).
            $salesUserId = $quotation->sales ? $this->resolveOdooUserId($quotation->sales) : null;
            if ($salesUserId) {
                $values['user_id'] = $salesUserId;
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

    /**
     * "Salesperson (CRM): ..." SENGAJA ikut dimasukkan ke sini juga (bukan
     * cuma mengandalkan field user_id/Salesperson resmi) -- soalnya field
     * Salesperson resmi TERGANTUNG employee-nya sudah di-link ke akun user
     * (res.users) di Odoo (lihat resolveOdooUserId()), yang ternyata belum
     * semua sales py link-nya. Selama belum semua ke-link, minimal nama
     * sales pembuat quotation-nya TETAP kelihatan di catatan sale.order,
     * gak sepenuhnya hilang cuma karena field resminya kosong.
     */
    private function buildOdooNote(Quotation $quotation): string
    {
        return implode("\n", array_filter([
            $quotation->sales?->fullname ? "Salesperson (CRM): {$quotation->sales->fullname}" : null,
            $quotation->payment_terms ? "Payment Terms: {$quotation->payment_terms}" : null,
            $quotation->validity ? "Validity: {$quotation->validity}" : null,
            $quotation->delivery_time ? "Delivery Time: {$quotation->delivery_time}" : null,
            $quotation->term ? "Term: {$quotation->term}" : null,
        ]));
    }

    /**
     * Resolve ID user Odoo (res.users) buat 1 sales (ms_users), dipakai
     * buat ngisi field "Salesperson" (user_id) di sale.order pas push
     * quotation. Cache-nya di ms_users.odoo_user_id.
     *
     * UPDATE (setelah ketauan di instance Odoo user ini cuma ada 1 akun
     * res.users, yaitu akun API "ARIS" -- sales-sales CRM TIDAK
     * masing-masing punya akun login Odoo sendiri): auto-match by name
     * LANGSUNG ke res.users (versi sebelumnya) jadinya SELALU 0 match,
     * karena memang tidak ada res.users dengan nama sales-sales itu.
     *
     * SEKARANG lewat jalur yang sama seperti fitur Expenses: resolve dulu
     * hr.employee-nya (resolveOdooEmployeeId() di bawah -- pola & cache
     * PERSIS ExpenseController::resolveOdooEmployeeId(), malah biasanya
     * LANGSUNG kepakai dari cache ms_users.odoo_employee_id yang sudah
     * keisi duluan dari fitur Expenses, tidak perlu search ulang), lalu
     * baca field user_id BAWAAN hr.employee itu sendiri di Odoo (kalau
     * employee itu memang sudah di-link ke 1 akun res.users oleh Admin
     * Odoo). Ini jauh lebih akurat daripada nebak-nebak lewat pencarian
     * nama terpisah ke res.users -- tinggal ngikutin link resmi yang
     * Odoo sendiri sudah punya antara hr.employee <-> res.users.
     *
     * TETAP TIDAK melempar exception kalau employee-nya tidak ketemu ATAU
     * employee-nya ketemu tapi belum di-link ke akun user manapun di
     * Odoo (user_id hr.employee-nya kosong) -- Salesperson bukan field
     * wajib di sale.order, jadi caller (pushQuotationToOdoo()) cukup
     * lewatin field user_id kalau null, push tetap lanjut.
     */
    private function resolveOdooUserId(MsUsers $sales): ?int
    {
        if ($sales->odoo_user_id) {
            return (int) $sales->odoo_user_id;
        }

        $employeeId = $this->resolveOdooEmployeeId($sales);
        if (!$employeeId) {
            Log::warning(
                "Tidak bisa resolve hr.employee Odoo buat sales \"{$sales->fullname}\" (dipakai buat cari Salesperson-nya juga) "
                . '-- field Salesperson di sale.order akan dikosongkan.'
            );

            return null;
        }

        $employeeRows = $this->odooService->searchRead(
            'hr.employee',
            [['id', '=', $employeeId]],
            ['user_id'],
            1
        );

        $userId = $employeeRows[0]['user_id'][0] ?? null;

        if (!$userId) {
            Log::warning(
                "Employee Odoo \"{$sales->fullname}\" (hr.employee #{$employeeId}) belum di-link ke akun user (res.users) manapun di Odoo "
                . '-- field Salesperson di sale.order akan dikosongkan. Kalau mau keisi, employee ini perlu dibuatkan/di-link ke akun user login di Odoo dulu.'
            );

            return null;
        }

        $userId   = (int) $userId;
        $userName = $employeeRows[0]['user_id'][1] ?? null;

        $sales->update([
            'odoo_user_id'   => $userId,
            'odoo_user_name' => $userName,
        ]);

        return $userId;
    }

    /**
     * AUTO-MATCH BY NAME + CACHE -- resolve ID employee Odoo (hr.employee)
     * buat 1 sales (ms_users). Duplikat sengaja dari
     * ExpenseController::resolveOdooEmployeeId() (pola yang sama persis,
     * termasuk cache-nya SAMA-SAMA di ms_users.odoo_employee_id -- jadi
     * kalau sales ini sudah pernah punya expense yang di-approve, cache-nya
     * langsung kepakai di sini tanpa search ulang ke Odoo). Tidak
     * cross-call ke ExpenseController supaya QuotationController tetap
     * self-contained (konsisten dengan alasan customerOptions() juga
     * dibuat sendiri, bukan cross-call).
     */
    private function resolveOdooEmployeeId(MsUsers $sales): ?int
    {
        if ($sales->odoo_employee_id) {
            return (int) $sales->odoo_employee_id;
        }

        $matches = $this->odooService->searchRead(
            'hr.employee',
            [['name', '=ilike', $sales->fullname]],
            ['id', 'name'],
            2 // cukup ambil maks 2 buat deteksi ambigu, gak perlu semua
        );

        if (count($matches) !== 1) {
            return null;
        }

        $employeeId   = (int) $matches[0]['id'];
        $employeeName = $matches[0]['name'];

        $sales->update([
            'odoo_employee_id'   => $employeeId,
            'odoo_employee_name' => $employeeName,
        ]);

        return $employeeId;
    }

    /**
     * AUTO-MATCH BY NAME + CACHE -- resolve partner_id Odoo (res.partner)
     * buat 1 customer CRM. Cache-nya di customers.odoo_partner_id
     * (mirip ms_users.odoo_employee_id di fitur Expenses).
     *
     * UPDATE: sekarang kalau nama customer-nya TIDAK KETEMU SAMA SEKALI
     * (0 match) di Odoo, sistem AUTO-CREATE contact baru (res.partner)
     * langsung dari data customer CRM ini -- lihat createOdooPartnerFor
     * Customer(). Ini permintaan eksplisit user supaya push quotation ga
     * ke-block cuma gara-gara contact-nya belum pernah dibikin di Odoo.
     *
     * Kalau AMBIGU (>1 match dengan nama sama persis), TETAP TIDAK
     * di-auto-create -- sengaja, karena kalau sistem asal bikin partner
     * baru pas ambigu, itu malah nambah 1 lagi duplikat di tengah data
     * yang sudah bermasalah, dan resiko salah pilih company/partner
     * makin gede. Kasus ambigu tetap harus dicek & di-map manual (lihat
     * command quotation:list-odoo-partners).
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

        if (count($matches) === 1) {
            $partnerId   = (int) $matches[0]['id'];
            $partnerName = $matches[0]['name'];

            $customer->update([
                'odoo_partner_id'   => $partnerId,
                'odoo_partner_name' => $partnerName,
            ]);

            return $partnerId;
        }

        // Ambigu (>1 match dengan nama sama persis) -- jangan auto-create,
        // biarkan caller lempar error supaya di-mapping manual.
        if (count($matches) > 1) {
            return null;
        }

        // 0 match -- benar-benar belum ada contact-nya di Odoo, auto-create.
        return $this->createOdooPartnerForCustomer($customer);
    }

    /**
     * Buat contact (res.partner) BARU di Odoo langsung dari data customer
     * CRM, dipanggil HANYA kalau exact-name-match beneran 0 hasil (bukan
     * ambigu). Field yang dikirim sengaja MINIMAL & aman (cuma yang jelas
     * ada di tabel customers) -- name, alamat, email, telpon -- supaya
     * ga nebak-nebak field kustom Odoo yang belum tentu ada.
     *
     * company_id DULU di-set eksplisit ke default company CRM (config
     * odoo.default_company_id) buat semua customer -- SEKARANG diganti
     * companyIdFor() supaya company_id-nya ikut company CRM pemilik
     * customer ini (dari group_id sales pemilik customer, lihat
     * customers.id_user), bukan default global buat semua company.
     * Company yang belum di-mapping (group_companies.odoo_company_id
     * masih null) tetap fallback ke default global lewat companyIdFor(),
     * jadi tetap konsisten sama product-product yang dipakai di sale
     * order-nya -- menghindari bug "company crossover" yang sama seperti
     * yang sudah pernah diperbaiki sebelumnya di fitur ini & di Expenses.
     */
    private function createOdooPartnerForCustomer(MsCustomers $customer): ?int
    {
        try {
            $groupId = DB::table('ms_users')
                ->where('id_user', $customer->id_user)
                ->value('group_id');

            $values = [
                'name'       => $customer->company_name,
                'is_company' => true,
                'company_id' => $this->odooService->companyIdFor($groupId ? (int) $groupId : null),
            ];

            if (!empty($customer->address)) {
                $values['street'] = $customer->address;
            }
            if (!empty($customer->email)) {
                $values['email'] = $customer->email;
            }
            if (!empty($customer->phone)) {
                $values['phone'] = $customer->phone;
            }

            $partnerId = $this->odooService->create('res.partner', $values);

            $customer->update([
                'odoo_partner_id'   => $partnerId,
                'odoo_partner_name' => $customer->company_name,
            ]);

            Log::info("Auto-create partner Odoo baru buat customer #{$customer->id} \"{$customer->company_name}\" -> odoo_partner_id={$partnerId}");

            return $partnerId;
        } catch (\Throwable $e) {
            Log::error("Gagal auto-create partner Odoo buat customer #{$customer->id} \"{$customer->company_name}\": " . $e->getMessage());

            return null;
        }
    }
}