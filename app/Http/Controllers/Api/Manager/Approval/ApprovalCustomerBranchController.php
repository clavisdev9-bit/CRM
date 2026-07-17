<?php

namespace App\Http\Controllers\Api\Manager\Approval;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerBranch;
use App\Http\Resources\CustomerBranchApprovalResource;
use App\Http\Resources\CustomerBranchApprovalResourceCollection;

class ApprovalCustomerBranchController extends Controller
{
    protected $branch;

    public function __construct(CustomerBranch $branch)
    {
        $this->branch = $branch;
    }

    /**
     * ======================================================
     * BRANCH APPROVAL LIST
     * ======================================================
     */
    public function index(Request $request)
    {
        $search         = $request->search;
        $perPage        = $request->per_page ?? 10;
        $sortBy         = $request->sort_by ?? 'cb.created_at';
        $sortDir        = $request->sort_dir ?? 'desc';
        $approvalStatus = $request->approval_status ?? 'all';

        $query = DB::table('customer_branches as cb')
            ->select([
                'cb.id',
                'cb.customer_id',
                'c.company_name',
                'c.customer_code',

                'cb.branch_code',
                'cb.branch_name',
                'cb.is_main_branch',
                'cb.status',
                'cb.address',
                'cb.city',
                'cb.contact_name',
                'cb.email',
                'cb.phone',

                'cb.assigned_to',
                'sales.fullname as assigned_name',

                'cb.created_by',
                'creator.fullname as created_by_name',

                // =========================
                // APPROVAL
                // =========================
                'cb.approval_status',
                'cb.approved_by',
                'approver.fullname as approved_name',
                'cb.approved_at',
                'cb.approval_note',
                'cb.approval_revision',
                'cb.submitted_for_approval_at',

                'cb.notes',
                'cb.created_at',
                'cb.updated_at',
            ])
            ->leftJoin('customers as c', 'c.id', '=', 'cb.customer_id')
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'cb.assigned_to')
            ->leftJoin('ms_users as creator', 'creator.id_user', '=', 'cb.created_by')
            ->leftJoin('ms_users as approver', 'approver.id_user', '=', 'cb.approved_by')
            ->whereNull('cb.deleted_at');

        /**
         * ==========================================
         * SEARCH
         * ==========================================
         */
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('cb.branch_name', 'ILIKE', "%{$search}%")
                    ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('c.customer_code', 'ILIKE', "%{$search}%");
            });
        }

        /**
         * ==========================================
         * FILTER STATUS
         * ==========================================
         */
        if ($approvalStatus != 'all') {
            $query->where('cb.approval_status', $approvalStatus);
        }

        /**
         * ==========================================
         * SORT
         * ==========================================
         */
        $query->orderBy($sortBy, $sortDir);

        $results = $query->paginate($perPage);

        return ApiResponse::paginate(
            CustomerBranchApprovalResourceCollection::make($results),
            $results->isEmpty()
                ? 'No branch found'
                : 'Success'
        );
    }

    /**
     * ======================================================
     * APPROVE BRANCH
     * ======================================================
     */
    public function approve($id)
    {
        $branch = $this->branch->find($id);

        if (!$branch) {
            return ApiResponse::error('Branch not found', 404);
        }

        $branch->update([
            'approval_status' => 'approved',
            'approved_by'     => auth()->user()->id_user,
            'approved_at'     => now(),
            'approval_note'   => null,
        ]);

        return ApiResponse::success(
            new CustomerBranchApprovalResource($branch->fresh()),
            'Branch approved successfully.'
        );
    }

    /**
     * ======================================================
     * REJECT BRANCH
     * ======================================================
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'approval_note' => 'required|string|max:1000'
        ]);

        $branch = $this->branch->find($id);

        if (!$branch) {
            return ApiResponse::error('Branch not found', 404);
        }

        $branch->update([
            'approval_status'   => 'rejected',
            'approval_note'     => $request->approval_note,
            'approval_revision' => $branch->approval_revision + 1,
            'approved_by'       => auth()->user()->id_user,
            'approved_at'       => now(),
        ]);

        return ApiResponse::success(
            new CustomerBranchApprovalResource($branch->fresh()),
            'Branch rejected successfully.'
        );
    }
}