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
    // public function index(Request $request)
    // {
    //     $search         = $request->search;
    //     $perPage        = $request->per_page ?? 10;
    //     $sortBy         = $request->sort_by ?? 'cb.created_at';
    //     $sortDir        = $request->sort_dir ?? 'desc';
    //     $approvalStatus = $request->approval_status ?? 'all';

    //     $query = DB::table('customer_branches as cb')
    //         ->select([
    //             'cb.id',
    //             'cb.customer_id',
    //             'c.company_name',
    //             'c.customer_code',

    //             'cb.branch_code',
    //             'cb.branch_name',
    //             'cb.is_main_branch',
    //             'cb.status',
    //             'cb.address',
    //             'cb.city',
    //             'cb.contact_name',
    //             'cb.email',
    //             'cb.phone',

    //             'cb.assigned_to',
    //             'sales.fullname as assigned_name',

    //             'cb.created_by',
    //             'creator.fullname as created_by_name',

    //             // =========================
    //             // APPROVAL
    //             // =========================
    //             'cb.approval_status',
    //             'cb.approved_by',
    //             'approver.fullname as approved_name',
    //             'cb.approved_at',
    //             'cb.approval_note',
    //             'cb.approval_revision',
    //             'cb.submitted_for_approval_at',

    //             'cb.notes',
    //             'cb.created_at',
    //             'cb.updated_at',
    //         ])
    //         ->leftJoin('customers as c', 'c.id', '=', 'cb.customer_id')
    //         ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'cb.assigned_to')
    //         ->leftJoin('ms_users as creator', 'creator.id_user', '=', 'cb.created_by')
    //         ->leftJoin('ms_users as approver', 'approver.id_user', '=', 'cb.approved_by')
    //         ->whereNull('cb.deleted_at');

    //     /**
    //      * ==========================================
    //      * SEARCH
    //      * ==========================================
    //      */
    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('cb.branch_name', 'ILIKE', "%{$search}%")
    //                 ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
    //                 ->orWhere('c.customer_code', 'ILIKE', "%{$search}%");
    //         });
    //     }

    //     /**
    //      * ==========================================
    //      * FILTER STATUS
    //      * ==========================================
    //      */
    //     if ($approvalStatus != 'all') {
    //         $query->where('cb.approval_status', $approvalStatus);
    //     }

    //     /**
    //      * ==========================================
    //      * SORT
    //      * ==========================================
    //      */
    //     $query->orderBy($sortBy, $sortDir);

    //     $results = $query->paginate($perPage);

    //     return ApiResponse::paginate(
    //         CustomerBranchApprovalResourceCollection::make($results),
    //         $results->isEmpty()
    //             ? 'No branch found'
    //             : 'Success'
    //     );
    // }

    public function index(Request $request)
{
    $search         = $request->get('search');
    $perPage        = $request->get('per_page', 10);
    $sortBy         = $request->get('sort_by', 'created_at');
    $sortDir        = strtolower($request->get('sort_dir', 'desc'));

    $approvalStatus = $request->get('approval_status', 'all');
    $status         = $request->get('status');
    $customerId     = $request->get('customer_id');
    $assignedTo     = $request->get('assigned_to');
    $city           = $request->get('city');
    $isMainBranch   = $request->get('is_main_branch');

    /**
     * =====================================================
     * SORTING
     * =====================================================
     */
    $allowedSorts = [
        'created_at'      => 'cb.created_at',
        'branch_name'     => 'cb.branch_name',
        'branch_code'     => 'cb.branch_code',
        'company_name'    => 'c.company_name',
        'customer_code'   => 'c.customer_code',
        'assigned_name'   => 'sales.fullname',
        'approval_status' => 'cb.approval_status',
        'city'            => 'cb.city',
        'status'          => 'cb.status',
    ];

    $sortColumn = $allowedSorts[$sortBy] ?? 'cb.created_at';
    $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

    /**
     * =====================================================
     * QUERY
     * =====================================================
     */
    $query = DB::table('customer_branches as cb')

        ->select([

            // =========================
            // BRANCH
            // =========================
            'cb.id',
            'cb.customer_id',
            'cb.branch_code',
            'cb.branch_name',
            'cb.is_main_branch',
            'cb.status',

            // =========================
            // CUSTOMER
            // =========================
            'c.company_name',
            'c.customer_code',

            // =========================
            // PRIMARY CONTACT
            // =========================
            'bc.name as contact_name',
            'bc.position as contact_position',
            'bc.email as contact_email',
            'bc.phone as contact_phone',

            // =========================
            // CURRENT PIC
            // =========================
            'cb.assigned_to',
            'sales.fullname as assigned_name',

            // =========================
            // SUBMITTED BY
            // =========================
            'cb.created_by',
            'creator.fullname as created_by_name',

            // =========================
            // APPROVAL
            // =========================
            'cb.approval_status',
            'cb.submitted_for_approval_at',
            'cb.approved_by',
            'approver.fullname as approved_name',
            'cb.approved_at',
            'cb.approval_note',
            'cb.approval_revision',

            // =========================
            // LOCATION
            // =========================
            'cb.address',
            'cb.city',

            // =========================
            // NOTES
            // =========================
            'cb.notes',

            // =========================
            // AUDIT
            // =========================
            'cb.created_at',
            'cb.updated_at',
        ])

        ->leftJoin('customers as c', 'c.id', '=', 'cb.customer_id')

        ->leftJoin('branch_contacts as bc', function ($join) {
            $join->on('bc.branch_id', '=', 'cb.id')
                ->where('bc.is_primary', true)
                ->whereNull('bc.deleted_at');
        })

        // ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'cb.assigned_to')
        ->leftJoin('ms_users as sales', function ($join) {
            $join->on('sales.id_user', '=', DB::raw('COALESCE(cb.assigned_to, cb.created_by)'));
        })

        ->leftJoin('ms_users as creator', 'creator.id_user', '=', 'cb.created_by')

        ->leftJoin('ms_users as approver', 'approver.id_user', '=', 'cb.approved_by')

        ->whereNull('cb.deleted_at');

    /**
     * =====================================================
     * SEARCH
     * =====================================================
     */
    if (!empty($search)) {

        $query->where(function ($q) use ($search) {

            $q->where('cb.branch_name', 'ILIKE', "%{$search}%")
                ->orWhere('cb.branch_code', 'ILIKE', "%{$search}%")
                ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
                ->orWhere('c.customer_code', 'ILIKE', "%{$search}%")
                ->orWhere('bc.name', 'ILIKE', "%{$search}%")
                ->orWhere('bc.email', 'ILIKE', "%{$search}%")
                ->orWhere('bc.phone', 'ILIKE', "%{$search}%")
                ->orWhere('sales.fullname', 'ILIKE', "%{$search}%")
                ->orWhere('creator.fullname', 'ILIKE', "%{$search}%")
                ->orWhere('cb.city', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * =====================================================
     * FILTER
     * =====================================================
     */

    if ($approvalStatus !== 'all') {
        $query->where('cb.approval_status', $approvalStatus);
    }

    if (!empty($status)) {
        $query->where('cb.status', $status);
    }

    if (!empty($customerId)) {
        $query->where('cb.customer_id', $customerId);
    }

    if (!empty($assignedTo)) {
        $query->where('cb.assigned_to', $assignedTo);
    }

    if (!empty($city)) {
        $query->where('cb.city', $city);
    }

    if ($request->has('is_main_branch')) {
        $query->where(
            'cb.is_main_branch',
            filter_var($isMainBranch, FILTER_VALIDATE_BOOLEAN)
        );
    }

    /**
     * =====================================================
     * SORT
     * =====================================================
     */
    $query->orderBy($sortColumn, $sortDir);

    /**
     * =====================================================
     * PAGINATION
     * =====================================================
     */
    $results = $query->paginate($perPage);

    return ApiResponse::paginate(
        CustomerBranchApprovalResourceCollection::make($results),
        $results->isEmpty()
            ? 'No customer branch approval found.'
            : 'Customer branch approval retrieved successfully.'
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