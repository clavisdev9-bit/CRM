<?php

namespace App\Http\Controllers\Api\Users\Sales\Plan;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\SalesVisitPlanValidationIndex;
use App\Http\Requests\SalesVisitPlanValidationStore;
use App\Http\Requests\SalesVisitPlanValidationUpdate;
use App\Http\Resources\SalesVisitPlanResource;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ==========================================================================
 * PLANNING KUNJUNGAN SALES (CALENDAR VIEW)
 * --------------------------------------------------------------------------
 * Fitur baru, murni catatan rencana milik Sales sendiri -- TIDAK terhubung
 * ke alur Visit Check-In manapun (lihat komentar di migration
 * create_sales_visit_plans_table). Sales cuma bisa lihat/ubah/hapus rencana
 * MILIKNYA SENDIRI (semua query di-scope ke sales_id = auth user).
 *
 * index() SEKALIGUS menggabungkan 2 sumber data buat ditampilkan di 1
 * kalender yang sama:
 *   - `plans`      : dari tabel sales_visit_plans (rencana manual, BISA
 *                    diedit/dihapus lewat store()/update()/destroy() di
 *                    controller ini).
 *   - `follow_ups` : dari tabel follow_ups yang SUDAH ADA (fitur lain),
 *                    ditampilkan READ-ONLY di kalender yang sama supaya
 *                    Sales bisa lihat "rencana saya" + "follow up yang
 *                    sudah dijadwalkan sistem" dalam satu tampilan. Tidak
 *                    ada endpoint create/update/delete follow_ups di sini
 *                    -- controller/fitur follow_ups yang sudah ada tetap
 *                    jadi satu-satunya yang boleh ubah tabel itu.
 * ==========================================================================
 */
class SalesVisitPlanController extends Controller
{
    /**
     * GET /sales/visit-plans?month=2026-08
     */
    public function index(SalesVisitPlanValidationIndex $request)
    {
        try {
            $validated = $request->validated();
            $salesId   = auth()->user()->id_user;

            $month     = $validated['month'] ?? now()->format('Y-m');
            $startDate = Carbon::parse($month . '-01')->startOfMonth();
            $endDate   = $startDate->copy()->endOfMonth();

            // ---------------- RENCANA MANUAL (sales_visit_plans) ----------------
            $plans = $this->baseSelect()
                ->where('svp.sales_id', $salesId)
                ->whereBetween('svp.plan_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->orderBy('svp.plan_date')
                ->get();

            // ---------------- FOLLOW UP YANG SUDAH ADA (read-only) ----------------
            $followUps = DB::table('follow_ups as f')
                ->leftJoin('customers as c', 'c.id', '=', 'f.customer_id')
                ->where('f.assigned_to', $salesId)
                ->whereNull('f.deleted_at')
                ->whereBetween('f.follow_up_at', [
                    $startDate->toDateString() . ' 00:00:00',
                    $endDate->toDateString() . ' 23:59:59',
                ])
                ->select([
                    'f.id',
                    'f.follow_up_code',
                    'f.follow_up_type',
                    'f.subject',
                    'f.notes',
                    'f.follow_up_at',
                    'f.status',
                    'f.result',
                    'f.customer_id',
                    'f.lead_id',
                    'c.company_name as customer_company_name',
                    'c.customer_code as customer_code',
                ])
                ->orderBy('f.follow_up_at')
                ->get()
                ->map(function ($row) {
                    return [
                        'id'             => $row->id,
                        'type'           => 'follow_up',
                        'follow_up_code' => $row->follow_up_code,
                        'follow_up_type' => $row->follow_up_type,
                        // title selalu ada isinya: subject > nama company > fallback tipe
                        'title'          => $row->subject
                            ?: ($row->customer_company_name ?: ('Follow Up ' . $row->follow_up_type)),
                        // "plan_date" disamain namanya dengan item plan, supaya frontend
                        // bisa nge-grup kedua jenis item ini ke kalender pakai 1 key yang sama.
                        'plan_date'      => substr($row->follow_up_at, 0, 10),
                        'follow_up_at'   => $row->follow_up_at,
                        'status'         => $row->status,
                        'result'         => $row->result,
                        'customer_id'    => $row->customer_id,
                        'customer_code'  => $row->customer_code,
                        'lead_id'        => $row->lead_id,
                        'notes'          => $row->notes,
                    ];
                })
                ->values();

            // "done" & "cancelled" digabung dari 2 sumber (rencana manual + follow up
            // yang sudah ada) karena secara makna sama -- "pending" & "closed" cuma
            // ada di follow_ups (rencana manual cuma kenal planned/done/cancelled).
            $stats = [
                'planned'   => $plans->where('status', 'planned')->count(),
                'done'      => $plans->where('status', 'done')->count()
                    + $followUps->where('status', 'DONE')->count(),
                'cancelled' => $plans->where('status', 'cancelled')->count()
                    + $followUps->where('status', 'CANCELLED')->count(),
                'pending'   => $followUps->where('status', 'PENDING')->count(),
                'closed'    => $followUps->where('status', 'CLOSED')->count(),
            ];

            return ApiResponse::success([
                'plans'      => SalesVisitPlanResource::collection($plans),
                'follow_ups' => $followUps,
                'stats'      => $stats,
            ], 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load visit plans', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /sales/visit-plans
     */
    public function store(SalesVisitPlanValidationStore $request)
    {
        try {
            $data    = $request->validated();
            $salesId = auth()->user()->id_user;

            $title = $data['title'] ?? null;

            if (! empty($data['customer_id'])) {
                $customer = DB::table('customers')->where('id', $data['customer_id'])->first();

                if (! $customer) {
                    return ApiResponse::error('Customer tidak ditemukan.', [], 404);
                }

                // title SELALU disinkron dari company_name kalau customer_id keisi,
                // apapun yang dikirim frontend di field title.
                $title = $customer->company_name;
            }

            $id = DB::table('sales_visit_plans')->insertGetId([
                'sales_id'    => $salesId,
                'customer_id' => $data['customer_id'] ?? null,
                'title'       => $title,
                'plan_date'   => $data['plan_date'],
                'status'      => 'planned',
                'notes'       => $data['notes'] ?? null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return $this->respondWithRow($id, 'Rencana kunjungan berhasil dibuat.', 201);

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to create visit plan (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while creating visit plan.', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * PUT /sales/visit-plans/{id}
     */
    public function update(SalesVisitPlanValidationUpdate $request, $id)
    {
        try {
            $salesId = auth()->user()->id_user;

            $row = DB::table('sales_visit_plans')
                ->where('id', $id)
                ->where('sales_id', $salesId)
                ->first();

            if (! $row) {
                return ApiResponse::error('Rencana kunjungan tidak ditemukan.', [], 404);
            }

            $data   = $request->validated();
            $update = ['updated_at' => now()];

            // customer_id dikirim (termasuk `null` eksplisit buat lepas link) --
            // title ikut disinkron ulang dari company_name kalau diisi.
            if ($request->has('customer_id')) {
                $customerId = $data['customer_id'] ?? null;
                $update['customer_id'] = $customerId;

                if ($customerId) {
                    $customer = DB::table('customers')->where('id', $customerId)->first();

                    if (! $customer) {
                        return ApiResponse::error('Customer tidak ditemukan.', [], 404);
                    }

                    $update['title'] = $customer->company_name;
                } elseif ($request->filled('title')) {
                    // customer_id dilepas TAPI title baru ikut dikirim -> pakai title baru
                    $update['title'] = $data['title'];
                }
                // customer_id dilepas TANPA title baru -> title lama (hasil sinkron
                // sebelumnya) dibiarkan apa adanya, supaya kartu ini tidak jadi kosong.
            } elseif ($request->filled('title')) {
                $update['title'] = $data['title'];
            }

            if ($request->filled('plan_date')) {
                $update['plan_date'] = $data['plan_date'];
            }

            if ($request->filled('status')) {
                $update['status'] = $data['status'];
            }

            if ($request->has('notes')) {
                $update['notes'] = $data['notes'] ?? null;
            }

            DB::table('sales_visit_plans')->where('id', $id)->update($update);

            return $this->respondWithRow($id, 'Rencana kunjungan berhasil diperbarui.');

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to update visit plan (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while updating visit plan.', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * DELETE /sales/visit-plans/{id}
     */
    public function destroy($id)
    {
        try {
            $salesId = auth()->user()->id_user;

            $deleted = DB::table('sales_visit_plans')
                ->where('id', $id)
                ->where('sales_id', $salesId)
                ->delete();

            if (! $deleted) {
                return ApiResponse::error('Rencana kunjungan tidak ditemukan.', [], 404);
            }

            return ApiResponse::success(null, 'Rencana kunjungan berhasil dihapus.');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to delete visit plan', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /sales/visit-plans/select/customers
     * ----------------------------------------------------------------
     * Daftar customer KHUSUS milik sales yang login (customers.id_user =
     * auth id) -- dipakai buat dropdown "Customer" di form tambah/edit
     * rencana kunjungan. SENGAJA endpoint terpisah dari
     * /customers/search-company (Costumers::searchCompany) yang global
     * dan dipakai modul lain (misal Product Population) buat cari SEMUA
     * customer approved -- di sini Sales cuma boleh milih customer yang
     * emang dia pegang sendiri.
     *
     * Nggak ada parameter search di server -- list-nya sengaja diambil
     * semua sekaligus (per sales biasanya nggak banyak) terus difilter
     * di frontend, sama persis pola dropdown "per company" di halaman
     * Customer Product Population.
     */
    public function customerSelect()
    {
        try {
            $salesId = auth()->user()->id_user;

            $customers = DB::table('customers as c')
                ->select(['c.id', 'c.customer_code', 'c.company_name'])
                ->whereNull('c.deleted_at')
                ->where('c.approval_status', 'approved')
                ->where('c.id_user', $salesId)
                ->orderBy('c.company_name')
                ->get();

            return ApiResponse::success($customers, 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to fetch customer select', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ======================================================
     * HELPERS
     * ======================================================
     */

    private function baseSelect()
    {
        return DB::table('sales_visit_plans as svp')
            ->leftJoin('customers as c', 'c.id', '=', 'svp.customer_id')
            ->select([
                'svp.id',
                'svp.sales_id',
                'svp.customer_id',
                'svp.title',
                'svp.plan_date',
                'svp.status',
                'svp.notes',
                'svp.created_at',
                'svp.updated_at',
                'c.customer_code as customer_code',
            ]);
    }

    private function respondWithRow($id, string $message, int $code = 200)
    {
        $row = $this->baseSelect()->where('svp.id', $id)->first();

        return ApiResponse::success(
            SalesVisitPlanResource::make($row),
            $message,
            $code
        );
    }
}