<?php

namespace App\Http\Controllers\Api\Manager\Approval;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MsCustomers;
use App\Http\Resources\CostumersResourcesCollection;

class ApprovalCustomerController extends Controller
{
    protected $customer;

    public function __construct(MsCustomers $customer)
    {
        $this->customer = $customer;
    }

   



    /**
 * ======================================================
 * CUSTOMER APPROVAL LIST
 * ======================================================
 */
public function index(Request $request)
{
    $search           = $request->search;
    $perPage          = $request->per_page ?? 10;
    $sortBy           = $request->sort_by ?? 'c.created_at';
    $sortDir          = $request->sort_dir ?? 'desc';
    $approvalStatus   = $request->approval_status ?? 'all';

    $query = DB::table('customers as c')
        ->select([
            'c.id',
            'c.customer_code',
            'c.company_name',
            'c.contact_name',
            'c.email',
            'c.phone',
            'c.address',

            'c.lead_id',
            'c.lead_category_id',
            'c.industry_id',

            'c.assigned_to',
            'c.created_by',

            'c.customer_status',

            // =========================
            // APPROVAL
            // =========================
            'c.approval_status',
            'c.approved_by',
            'c.approved_at',
            'c.approval_note',
            'c.approval_revision',

            'c.notes',

            'c.converted_at',
            'c.created_at',
            'c.updated_at',

            // =========================
            // LEAD
            // =========================
            'l.company_name as lead_company_name',
            'c.lead_source',
            'l.lead_status',

            // =========================
            // MASTER
            // =========================
            'cat.name as category_name',
            'ind.name as industry_name',

            // =========================
            // USER
            // =========================
            'owner.fullname as owner_name',
            'sales.fullname as assigned_name',
            'approver.fullname as approved_name',
        ])

        ->leftJoin('leads as l', 'l.id', '=', 'c.lead_id')
        ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
        ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')

        ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.created_by')
        ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')
        ->leftJoin('ms_users as approver', 'approver.id_user', '=', 'c.approved_by');

    /**
     * ==========================================
     * SEARCH
     * ==========================================
     */
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('c.company_name', 'ILIKE', "%{$search}%")
                ->orWhere('c.contact_name', 'ILIKE', "%{$search}%")
                ->orWhere('c.customer_code', 'ILIKE', "%{$search}%")
                ->orWhere('c.email', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * ==========================================
     * FILTER STATUS
     * ==========================================
     */
    if ($approvalStatus != 'all') {
        $query->where('c.approval_status', $approvalStatus);
    }

    /**
     * ==========================================
     * SORT
     * ==========================================
     */
    $query->orderBy($sortBy, $sortDir);

    $results = $query->paginate($perPage);

    return ApiResponse::paginate(
        CostumersResourcesCollection::make($results),
        $results->isEmpty()
            ? 'No customer found'
            : 'Success'
    );
}

    /**
     * ======================================================
     * APPROVE CUSTOMER
     * ======================================================
     */
    public function approve($id)
    {
        $customer = $this->customer->find($id);

        if (!$customer) {
            return ApiResponse::error('Customer not found', 404);
        }

        $customer->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->user()->id_user,
            'approved_at' => now(),
            'approval_note' => null,
        ]);

        return ApiResponse::success(
            new \App\Http\Resources\CostumersResources($customer->fresh()),
            'Customer approved successfully.'
        );
    }

    /**
     * ======================================================
     * REJECT CUSTOMER
     * ======================================================
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'approval_note' => 'required|string|max:1000'
        ]);

        $customer = $this->customer->find($id);

        if (!$customer) {
            return ApiResponse::error('Customer not found', 404);
        }

        $customer->update([
            'approval_status' => 'rejected',
            'approval_note' => $request->approval_note,
            'approval_revision' => $customer->approval_revision + 1,
            'approved_by' => auth()->user()->id_user,
            'approved_at' => now(),
        ]);

        return ApiResponse::success(
            new \App\Http\Resources\CostumersResources($customer->fresh()),
            'Customer rejected successfully.'
        );
    }
}
