<?php

namespace App\Http\Controllers\Api\Users\Expense;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseValidationIndex;
use App\Http\Requests\ExpenseValidationReject;
use App\Http\Requests\ExpenseValidationStore;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\ExpenseResourceCollection;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseCategoryOdooProduct;
use App\Models\MsUsers;
use App\Services\OdooService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller fitur Expenses (spek "6.13 Ekspenses").
 *
 * SATU controller dipakai bareng oleh Sales (submit & lihat expense
 * miliknya sendiri) dan Manager/Admin (lihat semua + approve/reject) --
 * pola ini SAMA PERSIS kayak SalesTargetController, jadi tidak
 * dipisah jadi 2 controller. Scoping "lihat punya sendiri vs lihat semua"
 * ditentukan lewat canViewAllExpenses($user) di tiap method.
 *
 * Alur:
 *   1. Sales submit expense lewat store() -> status = pending.
 *   2. Manager/Admin approve()/reject() dari halaman approval.
 *   3. Begitu approve(), sistem OTOMATIS push ke Odoo sebagai hr.expense
 *      (lihat pushExpenseToOdoo()). employee_id & product_id di-resolve
 *      lewat AUTO-MATCH BY NAME + CACHE (lihat resolveOdooEmployeeId() dan
 *      resolveOdooProductIdForCategory()) -- supaya Admin gak perlu setup
 *      mapping manual dari awal.
 *   4. Kalau push ke Odoo gagal (nama gak ketemu/ambigu, atau Odoo down),
 *      expense TETAP berstatus approved di CRM (approval-nya valid),
 *      tapi odoo_push_status diisi 'failed' + odoo_push_error diisi
 *      pesannya, supaya Manager tau ada yang perlu ditindaklanjuti manual.
 */
class ExpenseController extends Controller
{
    protected OdooService $odooService;

    public function __construct(OdooService $odooService)
    {
        $this->odooService = $odooService;
    }

    /**
     * Role 1 = Admin, 3 = Manager -- sama persis pola canManageTargets()/
     * canViewAllSales() di SalesTargetController & ReportProductBySalesController.
     */
    private function canViewAllExpenses($user): bool
    {
        return in_array($user->role_id, [1, 3]);
    }

    // ════════════════════════════════════════════
    // LIST (Sales: punya sendiri, Manager/Admin: semua)
    // ════════════════════════════════════════════
    public function index(ExpenseValidationIndex $request)
    {
        $validated = $request->validated();
        $user      = auth()->user();
        $perPage   = $validated['per_page'] ?? 10;

        $query = Expense::query()
            ->with(['sales:id_user,fullname', 'customer:id,company_name', 'approver:id_user,fullname']);

        if ($this->canViewAllExpenses($user)) {
            if (!empty($validated['sales_id'])) {
                $query->where('sales_id', $validated['sales_id']);
            }
        } else {
            // Sales cuma bisa lihat expense miliknya sendiri.
            $query->where('sales_id', $user->id_user);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (!empty($validated['period_year'])) {
            $query->whereYear('expense_date', $validated['period_year']);
        }

        if (!empty($validated['period_month'])) {
            $query->whereMonth('expense_date', $validated['period_month']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'ILIKE', "%{$search}%")
                  ->orWhereHas('sales', function ($sq) use ($search) {
                      $sq->where('fullname', 'ILIKE', "%{$search}%");
                  });
            });
        }

        $results = $query->orderByDesc('expense_date')->orderByDesc('id')->paginate($perPage);

        return ApiResponse::paginate(
            ExpenseResourceCollection::make($results),
            $results->isEmpty() ? 'Data expense tidak ditemukan' : 'Success'
        );
    }

    // ════════════════════════════════════════════
    // DETAIL
    // ════════════════════════════════════════════
    public function show($id)
    {
        $user    = auth()->user();
        $expense = Expense::with(['sales:id_user,fullname', 'customer:id,company_name', 'approver:id_user,fullname'])
            ->find($id);

        if (!$expense) {
            return ApiResponse::error('Data expense tidak ditemukan', 404);
        }

        if (!$this->canViewAllExpenses($user) && (int) $expense->sales_id !== (int) $user->id_user) {
            return ApiResponse::error('Anda tidak memiliki akses ke data expense ini', 403);
        }

        return ApiResponse::success(ExpenseResource::make($expense), 'Success');
    }

    // ════════════════════════════════════════════
    // STORE (Sales submit expense baru)
    // ════════════════════════════════════════════
    public function store(ExpenseValidationStore $request)
    {
        $validated = $request->validated();
        $user      = auth()->user();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('expenses', 'public');
        }

        // Kunjungan (opsional) -- pola sinkronisasi SAMA PERSIS dengan
        // SalesVisitPlanController::store() (customer_id + title): kalau
        // customer_id diisi, location_name SELALU disinkron dari
        // company_name customer tersebut (apapun yang dikirim frontend
        // di field location_name). Kalau customer_id kosong,
        // location_name dipakai apa adanya (isi manual/free text).
        $customerId   = $validated['customer_id'] ?? null;
        $locationName = $validated['location_name'] ?? null;

        if ($customerId) {
            $customer = DB::table('customers')->where('id', $customerId)->first();

            if (!$customer) {
                return ApiResponse::error('Customer tidak ditemukan.', [], 404);
            }

            $locationName = $customer->company_name;
        }

        $expense = Expense::create([
            'sales_id'      => $user->id_user,
            'customer_id'   => $customerId,
            'location_name' => $locationName,
            'expense_date'  => $validated['expense_date'],
            'amount'        => $validated['amount'],
            'category'      => $validated['category'],
            'description'   => $validated['description'] ?? null,
            'attachment'    => $attachmentPath,
            'status'        => Expense::STATUS_PENDING,
            'created_by'    => $user->id_user,
        ]);

        return ApiResponse::success(
            ExpenseResource::make($expense->load(['sales:id_user,fullname', 'customer:id,company_name'])),
            'Expense berhasil diajukan, menunggu approval Manager'
        );
    }

    // ════════════════════════════════════════════
    // DESTROY (Sales boleh hapus punya sendiri SELAMA masih pending)
    // ════════════════════════════════════════════
    public function destroy($id)
    {
        $user    = auth()->user();
        $expense = Expense::find($id);

        if (!$expense) {
            return ApiResponse::error('Data expense tidak ditemukan', 404);
        }

        $isOwner = (int) $expense->sales_id === (int) $user->id_user;

        if (!$this->canViewAllExpenses($user) && !$isOwner) {
            return ApiResponse::error('Anda tidak memiliki akses ke data expense ini', 403);
        }

        if ($expense->status !== Expense::STATUS_PENDING) {
            return ApiResponse::error('Expense yang sudah di-' . $expense->status . ' tidak bisa dihapus', 422);
        }

        $expense->delete();

        return ApiResponse::success(null, 'Expense berhasil dihapus');
    }

    // ════════════════════════════════════════════
    // APPROVE (Manager/Admin) -- lalu otomatis push ke Odoo
    // ════════════════════════════════════════════
    public function approve(Request $request, $id)
    {
        $user = auth()->user();

        if (!$this->canViewAllExpenses($user)) {
            return ApiResponse::error('Anda tidak memiliki akses untuk approve expense', 403);
        }

        $expense = Expense::with('sales')->find($id);

        if (!$expense) {
            return ApiResponse::error('Data expense tidak ditemukan', 404);
        }

        if ($expense->status !== Expense::STATUS_PENDING) {
            return ApiResponse::error('Expense ini sudah di-' . $expense->status . ' sebelumnya', 422);
        }

        $expense->update([
            'status'      => Expense::STATUS_APPROVED,
            'approved_by' => $user->id_user,
            'approved_at' => now(),
        ]);

        // ── PUSH KE ODOO (hr.expense) ──
        // Kalau gagal (nama employee/kategori gak ketemu di Odoo, atau Odoo
        // down), approval TETAP SAH -- cuma dicatat gagal push-nya supaya
        // Manager bisa tindak lanjut manual. Tidak melempar exception ke
        // response supaya approve() tidak ikut gagal gara-gara Odoo.
        $this->pushExpenseToOdoo($expense);

        return ApiResponse::success(
            ExpenseResource::make($expense->fresh(['sales:id_user,fullname', 'approver:id_user,fullname'])),
            $expense->odoo_push_status === Expense::ODOO_PUSH_PUSHED
                ? 'Expense di-approve dan berhasil dikirim ke Odoo'
                : 'Expense di-approve, TAPI gagal dikirim ke Odoo (lihat catatan error) -- perlu ditindaklanjuti manual'
        );
    }

    // ════════════════════════════════════════════
    // REJECT (Manager/Admin)
    // ════════════════════════════════════════════
    public function reject(ExpenseValidationReject $request, $id)
    {
        $user = auth()->user();

        if (!$this->canViewAllExpenses($user)) {
            return ApiResponse::error('Anda tidak memiliki akses untuk reject expense', 403);
        }

        $expense = Expense::find($id);

        if (!$expense) {
            return ApiResponse::error('Data expense tidak ditemukan', 404);
        }

        if ($expense->status !== Expense::STATUS_PENDING) {
            return ApiResponse::error('Expense ini sudah di-' . $expense->status . ' sebelumnya', 422);
        }

        $expense->update([
            'status'            => Expense::STATUS_REJECTED,
            'approved_by'       => $user->id_user,
            'approved_at'       => now(),
            'rejection_reason'  => $request->validated()['rejection_reason'],
        ]);

        return ApiResponse::success(
            ExpenseResource::make($expense->fresh(['sales:id_user,fullname', 'approver:id_user,fullname'])),
            'Expense berhasil di-reject'
        );
    }

    // ════════════════════════════════════════════
    // RETRY PUSH (Manager/Admin) -- buat expense yang statusnya sudah
    // approved tapi odoo_push_status='failed' (misal gara-gara mapping
    // employee/kategori sempat belum ketemu, atau Odoo sempat down).
    // TIDAK mengubah status approval, cuma coba kirim ulang ke Odoo.
    // ════════════════════════════════════════════
    public function retryPush($id)
    {
        $user = auth()->user();

        if (!$this->canViewAllExpenses($user)) {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengirim ulang expense ke Odoo', 403);
        }

        $expense = Expense::with('sales')->find($id);

        if (!$expense) {
            return ApiResponse::error('Data expense tidak ditemukan', 404);
        }

        if ($expense->status !== Expense::STATUS_APPROVED) {
            return ApiResponse::error('Cuma expense yang sudah approved yang bisa dikirim ulang ke Odoo', 422);
        }

        if ($expense->odoo_push_status === Expense::ODOO_PUSH_PUSHED) {
            return ApiResponse::error('Expense ini sudah berhasil terkirim ke Odoo sebelumnya', 422);
        }

        $this->pushExpenseToOdoo($expense);

        return ApiResponse::success(
            ExpenseResource::make($expense->fresh(['sales:id_user,fullname', 'approver:id_user,fullname'])),
            $expense->odoo_push_status === Expense::ODOO_PUSH_PUSHED
                ? 'Berhasil dikirim ulang ke Odoo'
                : 'Masih gagal dikirim ke Odoo (lihat catatan error) -- perlu ditindaklanjuti manual'
        );
    }

    // ════════════════════════════════════════════
    // SUMMARY (kartu ringkasan)
    // ════════════════════════════════════════════
    public function summary(Request $request)
    {
        $user  = auth()->user();
        $query = Expense::query();

        if (!$this->canViewAllExpenses($user)) {
            $query->where('sales_id', $user->id_user);
        }

        $totalPending  = (clone $query)->where('status', Expense::STATUS_PENDING)->count();
        $totalApproved = (clone $query)->where('status', Expense::STATUS_APPROVED)->count();
        $totalRejected = (clone $query)->where('status', Expense::STATUS_REJECTED)->count();
        $totalAmountApproved = (clone $query)->where('status', Expense::STATUS_APPROVED)->sum('amount');
        $totalFailedPush = (clone $query)->where('odoo_push_status', Expense::ODOO_PUSH_FAILED)->count();

        return ApiResponse::success([
            'total_pending'         => $totalPending,
            'total_approved'        => $totalApproved,
            'total_rejected'        => $totalRejected,
            'total_amount_approved' => (float) $totalAmountApproved,
            'total_failed_push'     => $totalFailedPush,
        ], 'Success');
    }

    // ════════════════════════════════════════════
    // OPTIONS: daftar kategori (dropdown, sesuai spek)
    // ════════════════════════════════════════════
    public function categoryOptions()
    {
        // Kategori sekarang diambil dari tabel master expense_categories
        // (yang aktif saja) -- bisa ditambah/diubah Admin langsung lewat
        // tabel ini tanpa perlu ubah kode/deploy ulang. Lihat model
        // ExpenseCategory.
        $categories = ExpenseCategory::where('is_active', true)
            ->orderBy('name')
            ->pluck('name');

        return ApiResponse::success(
            $categories->map(fn ($c) => ['value' => $c, 'label' => $c])->values(),
            'Success'
        );
    }

    // ════════════════════════════════════════════
    // OPTIONS: dropdown-search "Kunjungan" (opsional, di form Ajukan Expense)
    // ════════════════════════════════════════════
    // GANTI dari visitOptions() (harus visit yang sudah check-out) jadi
    // customerOptions() -- nampilin customer yang DIPEGANG sales yang
    // login, TIDAK terikat status visit sama sekali. Pola scoping-nya
    // SAMA PERSIS dengan Costumers.php (scopeSearch/query customer per
    // sales): COALESCE(c.assigned_to, c.created_by) = sales login,
    // supaya customer yang assigned_to-nya masih NULL (belum pernah
    // di-assign ulang) tetap kehitung punya sales pembuatnya.
    //
    // Kalau customer yang dimaksud belum ada di sistem, sales tetap bisa
    // ketik manual nama lokasinya di frontend (dikirim sebagai
    // location_name tanpa customer_id) -- lihat ExpenseValidationStore &
    // ExpenseController::store().
    public function customerOptions(Request $request)
    {
        $user   = auth()->user();
        $search = $request->input('search');

        $query = DB::table('customers as c')
            ->select(['c.id', 'c.company_name'])
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
            'id'    => $row->id,
            'label' => $row->company_name,
        ]);

        return ApiResponse::success($results, 'Success');
    }

    // ════════════════════════════════════════════════════════════════════
    // ── PUSH KE ODOO ──
    // ════════════════════════════════════════════════════════════════════

    /**
     * Push 1 expense yang sudah approved ke Odoo sebagai record hr.expense
     * baru. Semua error ditangkap & dicatat ke kolom odoo_push_* di
     * expense-nya sendiri -- TIDAK melempar exception ke caller, supaya
     * proses approve() di CRM tidak ikut gagal gara-gara Odoo bermasalah.
     */
    private function pushExpenseToOdoo(Expense $expense): void
    {
        try {
            $employeeId = $this->resolveOdooEmployeeId($expense->sales);
            if (!$employeeId) {
                throw new \Exception(
                    "Tidak ditemukan employee Odoo dengan nama persis \"{$expense->sales->fullname}\" (atau namanya ambigu/lebih dari 1 match). "
                    . 'Silakan cek manual data Employee di Odoo, lalu isi kolom odoo_employee_id di user ini.'
                );
            }

            $productId = $this->resolveOdooProductIdForCategory($expense->category);
            if (!$productId) {
                throw new \Exception(
                    "Tidak ditemukan product Odoo (can_be_expensed) yang namanya cocok dengan kategori \"{$expense->category}\". "
                    . 'Silakan cek manual data Expense Category/Product di Odoo.'
                );
            }

            // WAJIB set company_id eksplisit sama dengan company_id
            // employee-nya -- kalau tidak diisi, Odoo defaultnya pakai
            // company punya user API (ARIS), yang bisa beda dari company
            // tempat employee-nya terdaftar. Odoo menolak create kalau
            // company_id record beda dengan company_id employee_id-nya
            // ("no company crossover is allowed"). Diambil FRESH dari
            // Odoo tiap push (bukan dari cache ms_users) supaya selalu
            // akurat kalau suatu saat employee-nya dipindah company.
            $employeeRows = $this->odooService->searchRead(
                'hr.employee',
                [['id', '=', $employeeId]],
                ['company_id'],
                1
            );
            $companyId = $employeeRows[0]['company_id'][0] ?? null;

            if (!$companyId) {
                throw new \Exception(
                    "Tidak bisa menentukan company Odoo untuk employee ID {$employeeId}. "
                    . 'Silakan cek manual data Employee tersebut di Odoo.'
                );
            }

            $odooExpenseId = $this->odooService->create('hr.expense', [
                'name'          => $expense->description ?: $expense->category,
                'employee_id'   => $employeeId,
                'product_id'    => $productId,
                'company_id'    => $companyId,
                'total_amount'  => (float) $expense->amount,
                'date'          => optional($expense->expense_date)->toDateString(),
            ]);

            $expense->update([
                'odoo_expense_id'  => $odooExpenseId,
                'odoo_push_status' => Expense::ODOO_PUSH_PUSHED,
                'odoo_push_error'  => null,
                'odoo_pushed_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal push expense #' . $expense->id . ' ke Odoo: ' . $e->getMessage());

            $expense->update([
                'odoo_push_status' => Expense::ODOO_PUSH_FAILED,
                'odoo_push_error'  => $e->getMessage(),
                'odoo_pushed_at'   => now(),
            ]);
        }
    }

    /**
     * AUTO-MATCH BY NAME + CACHE -- resolve ID employee Odoo (hr.employee)
     * buat 1 sales (ms_users). Kalau sudah ada cache-nya di
     * ms_users.odoo_employee_id, langsung dipakai. Kalau belum, cari ke
     * Odoo berdasarkan nama persis (fullname), simpan hasilnya kalau
     * ketemu TEPAT 1 match. Kalau tidak ketemu / ambigu (>1 match),
     * return null (caller yang nampilin pesan error-nya).
     */
    private function resolveOdooEmployeeId(MsUsers $sales): ?int
    {
        if ($sales->odoo_employee_id) {
            return (int) $sales->odoo_employee_id;
        }

        $matches = $this->odooService->searchRead(
            'hr.employee',
            [['name', '=', $sales->fullname]],
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
     * AUTO-MATCH BY NAME + CACHE -- resolve product_id Odoo buat 1 kategori
     * expense CRM. Cache-nya di tabel expense_category_odoo_products (1
     * baris per kategori, cuma 7 kategori total). Kalau belum ada
     * cache-nya, cari product Odoo (can_be_expensed=true) yang namanya
     * ILIKE kategori, simpan kalau ketemu TEPAT 1 match.
     */
    private function resolveOdooProductIdForCategory(string $category): ?int
    {
        $cached = ExpenseCategoryOdooProduct::where('category', $category)->first();

        if ($cached && $cached->odoo_product_id) {
            return (int) $cached->odoo_product_id;
        }

        $matches = $this->odooService->searchRead(
            'product.product',
            [
                ['can_be_expensed', '=', true],
                ['name', 'ilike', $category],
            ],
            ['id', 'name'],
            2
        );

        if (count($matches) !== 1) {
            return null;
        }

        $productId   = (int) $matches[0]['id'];
        $productName = $matches[0]['name'];

        ExpenseCategoryOdooProduct::updateOrCreate(
            ['category' => $category],
            ['odoo_product_id' => $productId, 'odoo_product_name' => $productName]
        );

        return $productId;
    }
}