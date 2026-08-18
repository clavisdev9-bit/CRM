<?php

namespace App\Http\Controllers\Api\Manager\VisitTarget;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\VisitTargetValidationIndex;
use App\Http\Requests\VisitTargetValidationStore;
use App\Http\Requests\VisitTargetValidationUpdate;
use App\Http\Requests\VisitTargetValidationDuplicate;
use App\Http\Resources\VisitTargetResource;
use App\Http\Resources\VisitTargetResourceCollection;
use App\Traits\BuildsVisitTargetQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ============================================================================
 * TARGET VISIT (MANAGER ONLY) -- menu baru terpisah (didaftarin sendiri di
 * Menu Management), bukan bagian dari Sales Activity Dashboard.
 * ----------------------------------------------------------------------------
 * Manager kasih target jumlah kunjungan (visit) ke SATU sales, ke SATU
 * Customer ATAU SATU Branch, untuk SATU bulan kalender. Progress-nya dihitung
 * dinamis dari tabel `visits` (lihat BuildsVisitTargetQuery::baseVisitTargetQuery()
 * dan komentar di migration create_visit_targets_table untuk detail aturan
 * hitungnya) -- SUDAH DIKONFIRMASI user:
 *   - periode berulang bulanan (period_month = tanggal 1 bulan itu)
 *   - target boleh ke Customer ATAU ke Branch (persis 1 dari 2)
 *   - progress mulai dari 0 sejak target dibuat (created_at jadi cutoff),
 *     bukan ikut ngitung visit lama sebelum target di-input
 *   - kalau manager EDIT target_count di tengah jalan, progress TETAP LANJUT
 *     (bukan reset) -- otomatis kejamin krn cutoff-nya pakai created_at record
 *     target, dan update() di bawah cuma nyentuh target_count/notes, nggak
 *     nyentuh created_at.
 * ============================================================================
 */
class VisitTargetController extends Controller
{
    use BuildsVisitTargetQuery;

    /**
     * GET /manager/visit-targets
     * List target + progress, filter per bulan/sales/status, search, sort, pagination.
     */
    public function index(VisitTargetValidationIndex $request)
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Halaman ini khusus Manager.', [], 403);
        }

        try {
            $validated   = $request->validated();
            $periodMonth = ! empty($validated['period_month'])
                ? Carbon::parse($validated['period_month'])->startOfMonth()->toDateString()
                : now()->startOfMonth()->toDateString();

            $salesId = $validated['sales_id'] ?? null;
            $search  = $validated['search'] ?? null;
            $status  = $validated['status'] ?? 'all';
            $perPage = $validated['per_page'] ?? 10;
            $sortBy  = $validated['sort_by'] ?? 'created_at';
            $sortDir = strtolower($validated['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

            $inner = $this->baseVisitTargetQuery()
                ->where('vt.period_month', $periodMonth);

            if ($salesId) {
                $inner->where('vt.sales_id', $salesId);
            }

            if ($search) {
                $inner->where(function ($q) use ($search) {
                    $q->where('sales.fullname', 'ILIKE', "%{$search}%")
                        ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
                        ->orWhere('cb.branch_name', 'ILIKE', "%{$search}%");
                });
            }

            // achieved_count/target_count adalah kolom hasil subquery di inner ->
            // difilter/sort di LUAR lewat fromSub(), sama pola kayak buildActivitiesUnion()
            // di SalesActivityDashboardController (bukan whereRaw ke alias langsung).
            $results = DB::query()
                ->fromSub($inner, 'vt_result')
                ->when($status === 'achieved', fn ($q) => $q->whereColumn('achieved_count', '>=', 'target_count'))
                ->when($status === 'not_achieved', fn ($q) => $q->whereColumn('achieved_count', '<', 'target_count'))
                ->orderBy($sortBy, $sortDir)
                ->paginate($perPage);

            return ApiResponse::paginate(
                VisitTargetResourceCollection::make($results),
                $results->isEmpty() ? 'Belum ada target visit pada bulan ini' : 'Success'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load visit targets', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /manager/visit-targets/support/customers?sales_id=123
     * Dipakai buat isi dropdown Customer (dan dropdown "Customer induk branch")
     * di form Tambah Target -- SENGAJA di-scope ke customer yang jadi
     * tanggung jawab sales_id yang lagi dipilih, bukan SEMUA customer.
     * Alasan: /customers-masters (endpoint Sales) ternyata di-scope ke customer
     * milik user yang lagi login sendiri, jadi kalau dipanggil sebagai Manager
     * selalu kosong -- endpoint ini query langsung ke tabel `customers` tanpa
     * scope auth, tapi DIFILTER manual pakai sales_id dari query param.
     * "Milik sales" = customers.id_user (owner asli) ATAU customers.assigned_to
     * (hasil reassign lewat SalesReassign) sama dengan sales_id itu -- pola ini
     * dikonfirmasi dari migration create_customers_table yang kamu kirim.
     * Cuma nampilin customer yang approval_status = 'approved' (yang masih
     * pending/rejected nggak masuk akal buat dikasih target visit).
     */
    public function supportCustomers(Request $request)
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Halaman ini khusus Manager.', [], 403);
        }

        $salesId = $request->query('sales_id');
        if (! $salesId) {
            return ApiResponse::error('sales_id wajib diisi.', [], 422);
        }

        try {
            $rows = DB::table('customers')
                ->select(['id', 'company_name', 'customer_code', 'customer_status'])
                ->whereNull('deleted_at')
                ->where('approval_status', 'approved')
                ->where(function ($q) use ($salesId) {
                    $q->where('id_user', $salesId)
                        ->orWhere('assigned_to', $salesId);
                })
                ->orderBy('company_name')
                ->get();

            return ApiResponse::success($rows, 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load customers', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /manager/visit-targets/{id}
     */
    public function show($id)
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Halaman ini khusus Manager.', [], 403);
        }

        try {
            $row = $this->baseVisitTargetQuery()->where('vt.id', $id)->first();

            if (! $row) {
                return ApiResponse::error('Target visit tidak ditemukan.', [], 404);
            }

            return ApiResponse::success(new VisitTargetResource($row), 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load visit target', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /manager/visit-targets
     */
    public function store(VisitTargetValidationStore $request)
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Halaman ini khusus Manager.', [], 403);
        }

        try {
            $validated   = $request->validated();
            $managerId   = auth()->user()->id_user;
            $periodMonth = Carbon::parse($validated['period_month'])->startOfMonth()->toDateString();
            $customerId  = $validated['customer_id'] ?? null;
            $branchId    = $validated['branch_id'] ?? null;

            // dobel cek manual selain partial unique index di DB, biar pesan errornya
            // jelas (bukan cuma nunggu exception 500 dari constraint violation).
            $duplicate = DB::table('visit_targets')
                ->whereNull('deleted_at')
                ->where('sales_id', $validated['sales_id'])
                ->where('period_month', $periodMonth)
                ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->exists();

            if ($duplicate) {
                return ApiResponse::error(
                    'Target visit untuk sales & target (customer/branch) ini di bulan tersebut sudah ada.',
                    [],
                    422
                );
            }

            $targetCode = $this->generateTargetCode();

            $id = DB::table('visit_targets')->insertGetId([
                'target_code'  => $targetCode,
                'sales_id'     => $validated['sales_id'],
                'customer_id'  => $customerId,
                'branch_id'    => $branchId,
                'target_count' => $validated['target_count'],
                'period_month' => $periodMonth,
                'created_by'   => $managerId,
                'notes'        => $validated['notes'] ?? null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            return ApiResponse::success(['id' => $id, 'target_code' => $targetCode], 'Target visit berhasil dibuat');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to create visit target', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * PUT /manager/visit-targets/{id}
     * Cuma target_count & notes yang bisa diubah (lihat komentar di
     * VisitTargetValidationUpdate).
     */
    public function update(VisitTargetValidationUpdate $request, $id)
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Halaman ini khusus Manager.', [], 403);
        }

        try {
            $validated = $request->validated();

            $target = DB::table('visit_targets')->where('id', $id)->whereNull('deleted_at')->first();
            if (! $target) {
                return ApiResponse::error('Target visit tidak ditemukan.', [], 404);
            }

            DB::table('visit_targets')->where('id', $id)->update([
                'target_count' => $validated['target_count'],
                'notes'        => $validated['notes'] ?? null,
                'updated_at'   => now(),
            ]);

            return ApiResponse::success(null, 'Target visit berhasil diupdate');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to update visit target', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /manager/visit-targets/duplicate-next-month
     * -----------------------------------------------------------------------
     * Duplikat semua target visit dari SATU bulan sumber (`period_month` di
     * body request, biasanya bulan yang lagi ditampilkan manager di halaman)
     * ke bulan BERIKUTNYA (source + 1 bulan, dihitung otomatis oleh server --
     * bukan dipilih bebas oleh manager, sesuai nama fiturnya).
     *
     * - target_count & notes disalin apa adanya dari target sumbernya; manager
     *   tetap bisa edit lagi belakangan lewat modal Edit yang sudah ada.
     * - Progress target hasil duplikat MULAI DARI 0 di bulan barunya -- sama
     *   seperti bikin target manual biasa, karena cutoff `achieved_count`
     *   dihitung dari `created_at` record target yang baru ini (lihat
     *   BuildsVisitTargetQuery::baseVisitTargetQuery()), bukan ikut cutoff
     *   target sumbernya.
     * - target_code baru di-generate ulang (bukan reuse code lama).
     * - Kombinasi sales+customer/branch yang bulan TUJUANNYA sudah pernah
     *   dibuatkan target (manual, atau dari duplikat sebelumnya) DI-SKIP, bukan
     *   bikin error -- jadi tombol ini aman diklik berkali-kali (idempoten),
     *   misal manager nggak sengaja klik dobel atau sudah keburu bikin
     *   sebagian target manual duluan buat bulan depan.
     */
    public function duplicateToNextMonth(VisitTargetValidationDuplicate $request)
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Halaman ini khusus Manager.', [], 403);
        }

        try {
            $validated      = $request->validated();
            $managerId      = auth()->user()->id_user;
            $sourceMonth    = Carbon::parse($validated['period_month'])->startOfMonth();
            $sourceMonthStr = $sourceMonth->toDateString();
            $targetMonth    = $sourceMonth->copy()->addMonthNoOverflow()->startOfMonth()->toDateString();

            $sourceTargets = DB::table('visit_targets')
                ->whereNull('deleted_at')
                ->where('period_month', $sourceMonthStr)
                ->get();

            if ($sourceTargets->isEmpty()) {
                return ApiResponse::error('Tidak ada target visit di bulan sumber untuk diduplikat.', [], 422);
            }

            $created = 0;
            $skipped = 0;

            DB::beginTransaction();

            foreach ($sourceTargets as $target) {
                $duplicate = DB::table('visit_targets')
                    ->whereNull('deleted_at')
                    ->where('sales_id', $target->sales_id)
                    ->where('period_month', $targetMonth)
                    ->when($target->customer_id, fn ($q) => $q->where('customer_id', $target->customer_id))
                    ->when($target->branch_id, fn ($q) => $q->where('branch_id', $target->branch_id))
                    ->exists();

                if ($duplicate) {
                    $skipped++;
                    continue;
                }

                DB::table('visit_targets')->insert([
                    'target_code'  => $this->generateTargetCode(),
                    'sales_id'     => $target->sales_id,
                    'customer_id'  => $target->customer_id,
                    'branch_id'    => $target->branch_id,
                    'target_count' => $target->target_count,
                    'period_month' => $targetMonth,
                    'created_by'   => $managerId,
                    'notes'        => $target->notes,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
                $created++;
            }

            DB::commit();

            $message = "Berhasil duplikat {$created} target ke bulan berikutnya"
                . ($skipped > 0 ? ", {$skipped} dilewati karena sudah ada target di bulan tujuan." : '.');

            return ApiResponse::success([
                'source_month' => $sourceMonthStr,
                'target_month' => $targetMonth,
                'created'      => $created,
                'skipped'      => $skipped,
                'total'        => $sourceTargets->count(),
            ], $message);

        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to duplicate visit targets', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * DELETE /manager/visit-targets/{id}
     */
    public function destroy($id)
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Halaman ini khusus Manager.', [], 403);
        }

        try {
            $target = DB::table('visit_targets')->where('id', $id)->whereNull('deleted_at')->first();
            if (! $target) {
                return ApiResponse::error('Target visit tidak ditemukan.', [], 404);
            }

            DB::table('visit_targets')->where('id', $id)->update(['deleted_at' => now()]);

            return ApiResponse::success(null, 'Target visit berhasil dihapus');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to delete visit target', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Cek apakah user yang login adalah Manager (copy dari
     * SalesActivityDashboardController -- kalau kamu convert ini ke Trait
     * bersama nanti, tinggal replace method ini).
     */
    private function isManager(): bool
    {
        $user = auth()->user();

        if (! $user || empty($user->role_id)) {
            return false;
        }

        return DB::table('ms_role')
            ->where('id_role', $user->role_id)
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(role) = ?', ['manager'])
            ->exists();
    }
}