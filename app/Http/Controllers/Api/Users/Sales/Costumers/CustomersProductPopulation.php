<?php

namespace App\Http\Controllers\Api\Users\Sales\Costumers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;
use App\Models\CustomersProductPopulationModel;
use App\Models\MsCustomers;
use App\Http\Requests\CustomersProductPopulationValidationIndex;
use App\Http\Requests\CustomersProductPopulationValidationRequest;
use App\Http\Requests\CustomersProductPopulationAssignRequest;
use App\Http\Resources\CustomersProductPopulationResource;
use App\Http\Resources\CustomersProductPopulationResourceCollection;

class CustomersProductPopulation extends Controller
{
    protected $CustomersProductPopulationModel;
    protected $MsCustomers;

    public function __construct(
        CustomersProductPopulationModel $CustomersProductPopulationModel,
        MsCustomers $MsCustomers
    ) {
        $this->CustomersProductPopulationModel = $CustomersProductPopulationModel;
        $this->MsCustomers = $MsCustomers;
    }

    /**
     * ======================================================
     * INDEX — LIST PRODUCT POPULATION
     * ------------------------------------------------------
     * Mendukung 3 mode tampilan lewat query param `view`:
     *   - all        : semua data (default)
     *   - mine       : data yang user_id-nya mengandung sales yang login
     *   - incomplete : data yang customer_id NULL dan user_id kosong/NULL
     *                  sama sekali (belum pernah di-assign ke siapapun)
     * ======================================================
     */
    public function index(CustomersProductPopulationValidationIndex $request)
    {
        try {
            $validated = $request->validated();

            $search  = $validated['search'] ?? null;
            $perPage = $validated['per_page'] ?? 10;
            $view    = $validated['view'] ?? 'all';

            $allowedSort = ['pp.created_at', 'pp.tag_no', 'pp.pump_serial_no', 'pp.qty', 'customer_name'];
            $sortBy = in_array($validated['sort_by'] ?? null, $allowedSort)
                ? $validated['sort_by']
                : 'pp.created_at';
            $sortDir = $validated['sort_dir'] ?? 'desc';

            $userId = auth()->user()->id_user;

            $query = $this->baseSelect()
                ->leftJoin('customers as c', 'c.id', '=', 'pp.customer_id');

            /**
             * ---------------- FILTER PER VIEW ----------------
             */
            if ($view === 'mine') {

                // hanya data yang user_id-nya (array) mengandung sales yang login
                $query->whereRaw('? = ANY(pp.user_id)', [$userId]);

            } elseif ($view === 'incomplete') {

                // belum ada customer SEKALIGUS belum ada PIC sama sekali
                $query->whereNull('pp.customer_id')
                    ->where(function ($q) {
                        $q->whereNull('pp.user_id')
                            ->orWhereRaw('cardinality(pp.user_id) = 0');
                    });
            }

            /**
             * ---------------- SEARCH ----------------
             */
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('pp.pump_serial_no', 'ILIKE', "%{$search}%")
                        ->orWhere('pp.tag_no', 'ILIKE', "%{$search}%")
                        ->orWhere('pp.product_model', 'ILIKE', "%{$search}%")
                        ->orWhere('pp.product_category', 'ILIKE', "%{$search}%")
                        ->orWhere('c.company_name', 'ILIKE', "%{$search}%");
                });
            }

            $query->orderBy($sortBy, $sortDir);

            $results = $query->paginate($perPage);

            return ApiResponse::paginate(
                CustomersProductPopulationResourceCollection::make($results),
                $results->isEmpty() ? 'Data product population not found' : 'Success'
            );

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to fetch product population (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while fetching product population.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * COUNTS — badge angka buat 3 tab (all / mine / incomplete)
     * dipanggil sekali di frontend supaya nggak perlu fetch
     * seluruh list 3x cuma buat tahu jumlahnya.
     * ======================================================
     */
    public function counts()
    {
        try {
            $userId = auth()->user()->id_user;

            $all = DB::table('product_populations')->count();

            $mine = DB::table('product_populations as pp')
                ->whereRaw('? = ANY(pp.user_id)', [$userId])
                ->count();

            $incomplete = DB::table('product_populations as pp')
                ->whereNull('pp.customer_id')
                ->where(function ($q) {
                    $q->whereNull('pp.user_id')
                        ->orWhereRaw('cardinality(pp.user_id) = 0');
                })
                ->count();

            return ApiResponse::success([
                'all'        => $all,
                'mine'       => $mine,
                'incomplete' => $incomplete,
            ], 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to fetch product population counts', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * SHOW — detail 1 baris product population
     * ======================================================
     */
    public function show($id)
    {
        try {
            $row = $this->baseSelect()
                ->leftJoin('customers as c', 'c.id', '=', 'pp.customer_id')
                ->where('pp.id', $id)
                ->first();

            if (! $row) {
                return ApiResponse::error('Product population not found.', [], 404);
            }

            return ApiResponse::success(
                CustomersProductPopulationResource::make($row),
                'Success'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to fetch product population detail', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * STORE — tambah data product population baru
     * ======================================================
     */
    public function store(CustomersProductPopulationValidationRequest $request)
    {
        try {
            $data = $request->validated();

            $id = DB::transaction(function () use ($data) {
                return DB::table('product_populations')->insertGetId([
                    'customer_id'                => $data['customer_id'] ?? null,
                    'pump_serial_no'             => $data['pump_serial_no'],
                    'product_category'           => $data['product_category'],
                    'product_display'            => $data['product_display'] ?? null,
                    'product_model'              => $data['product_model'] ?? null,
                    'tag_no'                     => $data['tag_no'],
                    'qty'                        => $data['qty'],
                    'seal_plan'                  => $data['seal_plan'] ?? null,
                    'mechanical_seal_drawing_no' => $data['mechanical_seal_drawing_no'] ?? null,
                    'user_id'                    => $this->toPgArray($data['user_id'] ?? []),
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
            });

            return $this->respondWithRow($id, 'Success Create Product Population', 201);

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to create product population (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while creating product population.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * UPDATE — edit data product population
     * ------------------------------------------------------
     * Endpoint inilah yang dipakai admin/manager buat
     * "melengkapi" data yang tadinya masuk tab incomplete —
     * begitu customer & PIC-nya sudah ketahuan, tinggal
     * PUT ke endpoint ini dengan customer_id & user_id terisi.
     * Kalau lewat edit ini customer_id & user_id sama-sama
     * keisi, ikut sinkron ke tabel `customers` juga (sama
     * seperti jalur bulk assign()) lewat syncCustomerOwnership(),
     * jadi efeknya konsisten mau lewat modal Assign Sales atau
     * lewat edit satuan.
     * ======================================================
     */
    public function update(CustomersProductPopulationValidationRequest $request, $id)
    {
        try {
            $row = DB::table('product_populations')->where('id', $id)->first();

            if (! $row) {
                return ApiResponse::error('Product population not found.', [], 404);
            }

            $data = $request->validated();

            DB::transaction(function () use ($id, $data) {
                DB::table('product_populations')->where('id', $id)->update([
                    'customer_id'                => $data['customer_id'] ?? null,
                    'pump_serial_no'             => $data['pump_serial_no'],
                    'product_category'           => $data['product_category'],
                    'product_display'            => $data['product_display'] ?? null,
                    'product_model'              => $data['product_model'] ?? null,
                    'tag_no'                     => $data['tag_no'],
                    'qty'                        => $data['qty'],
                    'seal_plan'                  => $data['seal_plan'] ?? null,
                    'mechanical_seal_drawing_no' => $data['mechanical_seal_drawing_no'] ?? null,
                    'user_id'                    => $this->toPgArray($data['user_id'] ?? []),
                    'updated_at'                 => now(),
                ]);

                // customer_id & user_id sama-sama terisi lewat form edit ini
                // (biasanya pas admin/manager melengkapi data yang tadinya
                // ada di tab "Data Belum Lengkap") -> ikut sinkron ke
                // customers. Kalau PIC yang dipilih lebih dari satu, yang
                // dipakai buat "pemilik" customer-nya cuma yang pertama
                // (sejalan sama assign() yang memang cuma nerima 1 sales
                // tujuan per assignment).
                if (! empty($data['customer_id']) && ! empty($data['user_id'])) {
                    $this->syncCustomerOwnership(
                        [$data['customer_id']],
                        (int) $data['user_id'][0]
                    );
                }
            });

            return $this->respondWithRow($id, 'Success Update Product Population');

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to update product population (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while updating product population.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * DESTROY — hapus data product population
     * ======================================================
     */
    public function destroy($id)
    {
        try {
            $row = DB::table('product_populations')->where('id', $id)->first();

            if (! $row) {
                return ApiResponse::error('Product population not found.', [], 404);
            }

            DB::table('product_populations')->where('id', $id)->delete();

            return ApiResponse::success(null, 'Product population deleted successfully.', 200);

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to delete product population (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while deleting product population.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * UNASSIGNED — data yang SUDAH ada customer-nya, tapi
     * BELUM dipegang sales manapun (user_id kosong/NULL).
     * Khusus admin/manager. Ini yang mengisi modal
     * "Assign Sales" di tab Semua Data pada frontend.
     * ======================================================
     */
    public function unassigned(Request $request)
    {
        $blocked = $this->ensurePrivileged();
        if ($blocked) return $blocked;

        try {
            $search = $request->query('search');

            $query = DB::table('product_populations as pp')
                ->select([
                    'pp.id',
                    'pp.customer_id',
                    'pp.pump_serial_no',
                    'pp.product_category',
                    'pp.product_display',
                    'pp.product_model',
                    'pp.tag_no',
                    'pp.qty',
                    'c.company_name as customer_name',
                    'c.customer_code as customer_code',
                ])
                ->leftJoin('customers as c', 'c.id', '=', 'pp.customer_id')
                ->whereNotNull('pp.customer_id')
                ->where(function ($q) {
                    $q->whereNull('pp.user_id')
                        ->orWhereRaw('cardinality(pp.user_id) = 0');
                });

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('c.company_name', 'ILIKE', "%{$search}%")
                        ->orWhere('pp.tag_no', 'ILIKE', "%{$search}%")
                        ->orWhere('pp.pump_serial_no', 'ILIKE', "%{$search}%");
                });
            }

            $rows = $query->orderBy('pp.created_at', 'desc')->get();

            return ApiResponse::success(
                $rows,
                $rows->isEmpty() ? 'Semua data customer sudah ada PIC-nya.' : 'Success'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to fetch unassigned product population', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * ASSIGN — assign sales ke banyak data sekaligus.
     * Khusus admin/manager. Dipanggil dari modal
     * "Assign Sales" setelah admin/manager centang beberapa
     * baris + pilih sales tujuan.
     * ------------------------------------------------------
     * Selain nge-set pp.user_id, baris ini SEKALIGUS sinkron
     * ke tabel `customers` (lewat pp.customer_id) supaya
     * customer-nya otomatis:
     *   - jadi milik sales tujuan (customers.id_user)
     *   - created_by-nya ikut pindah ke sales tujuan
     *   - approval_status ikut ke-set jadi "approved"
     * Cuma customer_id yang IKUT ke-assign (lolos guard di
     * bawah) yang disinkron — bukan seluruh $ids mentah,
     * supaya konsisten sama guard "masih kosong PIC" di pp.
     * Sinkron ke customers ini sendiri cuma jalan kalau
     * customers.id_user masih NULL (belum ada pemilik sama
     * sekali) ATAU approval_status-nya masih "pending" —
     * customer yang udah punya id_user DAN sudah "approved"
     * dibiarkan, nggak ditimpa.
     * ======================================================
     */
    public function assign(CustomersProductPopulationAssignRequest $request)
    {
        $blocked = $this->ensurePrivileged();
        if ($blocked) return $blocked;

        try {
            $data     = $request->validated();
            $ids      = $data['ids'];
            $targetId = $data['user_id'];

            $updated = DB::transaction(function () use ($ids, $targetId) {

                // helper builder query dengan guard yang sama, dipanggil 2x
                // (sekali buat ambil customer_id yang bakal kena, sekali
                // buat eksekusi update-nya) — supaya "lihat data" dan
                // "update data" konsisten pakai kondisi yang sama.
                $guardedQuery = function () use ($ids) {
                    return DB::table('product_populations')
                        ->whereIn('id', $ids)
                        // guard: hanya update baris yang MASIH kosong PIC-nya,
                        // supaya request yang telat/basi tidak menimpa
                        // assignment yang barusan dibuat request lain.
                        ->where(function ($q) {
                            $q->whereNull('user_id')
                                ->orWhereRaw('cardinality(user_id) = 0');
                        });
                };

                // customer_id yang product population-nya BENERAN ke-assign
                // (lolos guard), dipakai buat sinkronisasi ke tabel customers.
                $affectedCustomerIds = $guardedQuery()
                    ->whereNotNull('customer_id')
                    ->pluck('customer_id')
                    ->unique()
                    ->values();

                $updatedCount = $guardedQuery()->update([
                    'user_id'    => $this->toPgArray([$targetId]),
                    'updated_at' => now(),
                ]);

                $this->syncCustomerOwnership($affectedCustomerIds, $targetId);

                return $updatedCount;
            });

            return ApiResponse::success(
                ['updated' => $updated],
                "{$updated} data berhasil di-assign."
            );

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to assign sales (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while assigning sales.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * HELPERS
     * ======================================================
     */

    /**
     * Sinkron kepemilikan customer ke sales tujuan + auto-approve.
     * Dipakai bareng oleh assign() (bulk, dari modal Assign Sales)
     * dan update() (lengkapi data satuan lewat form edit) supaya
     * efeknya konsisten di kedua jalur:
     *   - customers.id_user & created_by ikut pindah ke sales tujuan
     *   - approval_status ikut ke-set "approved"
     *
     * Guard: cuma jalan kalau customer-nya id_user masih NULL
     * (kasus kayak PT Adaro yang semua masih null), ATAU
     * approval_status-nya masih "pending" (meskipun created_by
     * kebetulan udah keisi). Customer yang udah punya id_user DAN
     * sudah "approved" dibiarkan, nggak ditimpa — supaya assign PIC
     * di product population nggak diam-diam ngerebut customer yang
     * sudah settle di sales/approval lain.
     */
    private function syncCustomerOwnership($customerIds, int $targetUserId): void
    {
        $customerIds = collect($customerIds)->filter()->unique()->values();

        if ($customerIds->isEmpty()) {
            return;
        }

        DB::table('customers')
            ->whereIn('id', $customerIds)
            ->where(function ($q) {
                $q->whereNull('id_user')
                    ->orWhere('approval_status', 'pending');
            })
            ->update([
                'id_user'         => $targetUserId,
                'created_by'      => $targetUserId,
                'approval_status' => 'approved',
                'updated_at'      => now(),
            ]);
    }

    /**
     * Select dasar yang dipakai index()/show()/respondWithRow(),
     * termasuk resolve customer_name & daftar nama PIC (pic_list)
     * dari kolom array pp.user_id lewat unnest() + json_agg(),
     * senada dengan pola branchSub di controller Costumers.
     */
    private function baseSelect()
    {
        return DB::table('product_populations as pp')
            ->select([
                'pp.id',
                'pp.customer_id',
                'pp.pump_serial_no',
                'pp.product_category',
                'pp.product_display',
                'pp.product_model',
                'pp.tag_no',
                'pp.qty',
                'pp.seal_plan',
                'pp.mechanical_seal_drawing_no',
                'pp.created_at',
                'pp.updated_at',

                'c.company_name as customer_name',
                'c.customer_code as customer_code',

                // representasi text dari kolom bigint[], di-parse lagi
                // jadi array PHP di CustomersProductPopulationResource
                DB::raw('pp.user_id::text as user_id_raw'),

                // daftar {id, name} sales yang sedang pegang baris ini
                DB::raw("(
                    SELECT COALESCE(json_agg(json_build_object('id', u.id_user, 'name', u.fullname)), '[]'::json)
                    FROM unnest(pp.user_id) AS uid
                    LEFT JOIN ms_users AS u ON u.id_user = uid
                ) as pic_list"),
            ]);
    }

    /**
     * Ambil ulang 1 baris lengkap (customer_name + pic_list) lalu
     * bungkus dengan Resource, dipakai setelah store()/update()
     * supaya response-nya konsisten dengan show().
     */
    private function respondWithRow($id, string $message, int $code = 200)
    {
        $row = $this->baseSelect()
            ->leftJoin('customers as c', 'c.id', '=', 'pp.customer_id')
            ->where('pp.id', $id)
            ->first();

        return ApiResponse::success(
            CustomersProductPopulationResource::make($row),
            $message,
            $code
        );
    }

    /**
     * Cek role user yang login. Skema role di project ini pakai
     * `role_id` di user (bukan string), dengan role_id = 2 untuk
     * Sales. Jadi "privileged" di sini = bukan Sales (role_id !== 2),
     * mencakup Administrator/Manager/role lain yang mungkin dibuat
     * ke depannya. Kalau nanti ada role tambahan yang JUGA harus
     * diblok dari fitur ini, ganti jadi whitelist eksplisit
     * (whereIn role_id [id_admin, id_manager]) alih-alih blacklist ini.
     */
    private function ensurePrivileged()
    {
        $roleId = auth()->user()->role_id ?? null;

        if ($roleId === null || (int) $roleId === 2) {
            return ApiResponse::error('Unauthorized. Fitur ini khusus admin/manager.', [], 403);
        }

        return null;
    }

    /**
     * Convert array PHP jadi text representation array PostgreSQL,
     * misal [3, 5] => '{3,5}', supaya bisa langsung ditulis ke
     * kolom bigint[] lewat query builder biasa.
     */
    private function toPgArray(array $ids): string
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        return '{' . implode(',', $ids) . '}';
    }
}