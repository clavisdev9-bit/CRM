<?php

namespace App\Http\Controllers\Api\Manager\Reassign;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReassign extends Controller
{

    // ======================================================
    // LIST CUSTOMER + BRANCH (dengan fallback sales)
    // ======================================================
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'approval_status' => 'nullable|in:pending,approved,rejected',
        ]);

        try {
            $perPage = $data['per_page'] ?? 10;
            $search = $data['search'] ?? null;

            /**
             * ============================================
             * SUB QUERY BRANCH
             * ----------------------------------------------
             * Fallback: kalau cb.assigned_to belum pernah
             * diisi (NULL), anggap cb.created_by sebagai
             * sales yang PEGANG cabang ini saat ini.
             * ============================================
             */
            $branchSubQuery = DB::table('customer_branches as cb')
                ->leftJoin(
                    'ms_users as branch_sales',
                    'branch_sales.id_user',
                    '=',
                    'cb.assigned_to'
                )
                ->leftJoin(
                    'ms_users as branch_owner',
                    'branch_owner.id_user',
                    '=',
                    'cb.created_by'
                )
                ->select(
                    'cb.customer_id',
                    DB::raw('COUNT(*) as branch_count'),
                    DB::raw("
                        json_agg(
                            json_build_object(
                                'id', cb.id,
                                'branch_code', cb.branch_code,
                                'branch_name', cb.branch_name,
                                'assigned_to', COALESCE(cb.assigned_to, cb.created_by),
                                'assigned_name', COALESCE(branch_sales.fullname, branch_owner.fullname),
                                'approval_status', cb.approval_status
                            )
                            ORDER BY cb.is_main_branch DESC, cb.branch_name ASC
                        ) as branches
                    ")
                )
                ->whereNull('cb.deleted_at')
                ->groupBy('cb.customer_id');

            /**
             * ============================================
             * CUSTOMER QUERY
             * ----------------------------------------------
             * Fallback yang sama: kalau c.assigned_to NULL,
             * anggap c.created_by sebagai sales saat ini.
             * ============================================
             */
            $query = DB::table('customers as c')
                ->leftJoin(
                    'ms_users as customer_sales',
                    'customer_sales.id_user',
                    '=',
                    'c.assigned_to'
                )
                ->leftJoin(
                    'ms_users as customer_owner',
                    'customer_owner.id_user',
                    '=',
                    'c.created_by'
                )
                ->leftJoinSub($branchSubQuery, 'branch_data', function ($join) {
                    $join->on('branch_data.customer_id', '=', 'c.id');
                })
                ->select([
                    'c.id',
                    'c.customer_code',
                    'c.company_name',
                    'c.approval_status',
                    'c.customer_status',
                    DB::raw('COALESCE(c.assigned_to, c.created_by) as assigned_to'),
                    DB::raw('COALESCE(customer_sales.fullname, customer_owner.fullname) as assigned_name'),
                    DB::raw('COALESCE(branch_data.branch_count, 0) as branch_count'),
                    DB::raw("COALESCE(branch_data.branches, '[]'::json) as branches"),
                ])
                ->whereNull('c.deleted_at');

            if ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('c.company_name', 'ILIKE', "%{$search}%")
                        ->orWhere('c.customer_code', 'ILIKE', "%{$search}%")
                        ->orWhere('customer_sales.fullname', 'ILIKE', "%{$search}%")
                        ->orWhere('customer_owner.fullname', 'ILIKE', "%{$search}%");
                });
            }

            if (!empty($data['approval_status'])) {
                $query->where('c.approval_status', $data['approval_status']);
            }

            $results = $query
                ->orderBy('c.company_name')
                ->paginate($perPage);

            $results->getCollection()->transform(function ($customer) {
                $customer->branches = is_string($customer->branches)
                    ? json_decode($customer->branches, true)
                    : ($customer->branches ?? []);

                return $customer;
            });

            return ApiResponse::paginate(
                $results,
                $results->isEmpty()
                    ? 'Data customer tidak ditemukan'
                    : 'Success'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validasi gagal', $e->errors(), 422);
        } catch (\Throwable $e) {
            return ApiResponse::error(
                'Gagal mengambil data assignment customer',
                [
                    'exception' => config('app.debug')
                        ? $e->getMessage()
                        : null,
                ],
                500
            );
        }
    }

    // ======================================================
    // LIST SALES UNTUK DROPDOWN
    // ======================================================
    public function sales(Request $request)
    {
        $data = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        try {
            $query = DB::table('ms_users as u')
                ->join('ms_role as r', 'r.id_role', '=', 'u.role_id')
                ->select([
                    'u.id_user',
                    'u.fullname',
                    'u.email',
                    'r.role',
                ])
                ->where('u.is_active', true)
                ->whereNull('u.deleted_at')
                ->whereNull('r.deleted_at')
                ->whereRaw('LOWER(r.role) = ?', ['sales']);

            if (!empty($data['search'])) {
                $search = $data['search'];

                $query->where(function ($builder) use ($search) {
                    $builder->where('u.fullname', 'ILIKE', "%{$search}%")
                        ->orWhere('u.email', 'ILIKE', "%{$search}%");
                });
            }

            $sales = $query
                ->orderBy('u.fullname')
                ->get();

            return ApiResponse::success(
                $sales,
                $sales->isEmpty() ? 'Data sales tidak ditemukan' : 'Success'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validasi gagal', $e->errors(), 422);
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal mengambil data sales', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ======================================================
    // REASSIGN CUSTOMER (head company)
    // ======================================================
    public function reassignCustomer(Request $request, $id)
    {
        $data = $request->validate([
            'new_sales_id' => 'required|integer|exists:ms_users,id_user',
            'reason'       => 'nullable|string|max:255',
        ]);

        try {
            $userId = auth()->user()->id_user;

            $customer = DB::table('customers')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$customer) {
                return ApiResponse::error('Customer not found', [], 404);
            }

            // Fallback: kalau assigned_to belum pernah diisi,
            // anggap created_by sebagai sales yang PEGANG saat ini.
            $effectiveCurrentSales = $customer->assigned_to ?? $customer->created_by;

            if ((int) $effectiveCurrentSales === (int) $data['new_sales_id']) {
                return ApiResponse::error(
                    'Sales yang dipilih sama dengan sales yang sedang memegang customer ini.',
                    [],
                    422
                );
            }

            DB::transaction(function () use ($id, $data, $effectiveCurrentSales, $userId) {

                DB::table('customer_assignment_histories')->insert([
                    'customer_id'        => $id,
                    'previous_sales_id'  => $effectiveCurrentSales,
                    'new_sales_id'       => $data['new_sales_id'],
                    'changed_by'         => $userId,
                    'reason'             => $data['reason'] ?? null,
                    'changed_at'         => now(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                DB::table('customers')
                    ->where('id', $id)
                    ->update([
                        'assigned_to' => $data['new_sales_id'],
                        'updated_at'  => now(),
                    ]);
            });

            $updated = DB::table('customers as c')
                ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')
                ->select('c.id', 'c.customer_code', 'c.company_name', 'c.assigned_to', 'sales.fullname as assigned_name')
                ->where('c.id', $id)
                ->first();

            return ApiResponse::success($updated, 'Customer berhasil dipindahkan ke sales baru.', 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validasi gagal', $e->errors(), 422);
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal memindahkan customer', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ======================================================
    // REASSIGN BRANCH
    // ======================================================
    public function reassignBranch(Request $request, $id)
    {
        $data = $request->validate([
            'new_sales_id' => 'required|integer|exists:ms_users,id_user',
            'reason'       => 'nullable|string|max:255',
        ]);

        try {
            $userId = auth()->user()->id_user;

            $branch = DB::table('customer_branches')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$branch) {
                return ApiResponse::error('Branch not found', [], 404);
            }

            // Fallback: kalau assigned_to belum pernah diisi,
            // anggap created_by sebagai sales yang PEGANG saat ini.
            $effectiveCurrentSales = $branch->assigned_to ?? $branch->created_by;

            if ((int) $effectiveCurrentSales === (int) $data['new_sales_id']) {
                return ApiResponse::error(
                    'Sales yang dipilih sama dengan sales yang sedang memegang cabang ini.',
                    [],
                    422
                );
            }

            DB::transaction(function () use ($id, $data, $effectiveCurrentSales, $userId) {

                DB::table('branch_assignment_histories')->insert([
                    'branch_id'         => $id,
                    'previous_sales_id' => $effectiveCurrentSales,
                    'new_sales_id'      => $data['new_sales_id'],
                    'changed_by'        => $userId,
                    'reason'            => $data['reason'] ?? null,
                    'changed_at'        => now(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                DB::table('customer_branches')
                    ->where('id', $id)
                    ->update([
                        'assigned_to' => $data['new_sales_id'],
                        'updated_at'  => now(),
                    ]);
            });

            $updated = DB::table('customer_branches as cb')
                ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'cb.assigned_to')
                ->select('cb.id', 'cb.branch_code', 'cb.branch_name', 'cb.assigned_to', 'sales.fullname as assigned_name')
                ->where('cb.id', $id)
                ->first();

            return ApiResponse::success($updated, 'Cabang berhasil dipindahkan ke sales baru.', 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validasi gagal', $e->errors(), 422);
        } catch (\Throwable $e) {
            return ApiResponse::error('Gagal memindahkan cabang', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}