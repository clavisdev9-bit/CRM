<?php

namespace App\Http\Controllers\Api\Users\Sales\Costumers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CostumersValidationIndex;
use App\Http\Requests\CostumersValidationRequest;
use App\Http\Resources\CostumersResources;
use App\Http\Resources\CostumersResourcesCollection;
use App\Models\MsCustomers;
use App\Models\MsLeadsCategory;
use App\Models\MsLeadsIndustries;
use App\Models\MsLeadsModel;

class Costumers extends Controller
{
     protected $MsCustomers;
     protected $MsLeadsCategory;
     protected $MsLeadsIndustries;
     protected $MsLeadsModel;

      public function __construct(MsCustomers $MsCustomers, 
                MsLeadsCategory $MsLeadsCategory, 
                MsLeadsIndustries $MsLeadsIndustries, 
                MsLeadsModel $MsLeadsModel) {
        $this->MsCustomers = $MsCustomers;
        $this->MsLeadsCategory = $MsLeadsCategory;
        $this->MsLeadsIndustries = $MsLeadsIndustries;
        $this->MsLeadsModel = $MsLeadsModel;
      }


 


// ambil data customers berdasarkan yang sales buat
public function customers(CostumersValidationIndex $request)
{
    $validated = $request->validated();

    $search   = $validated['search'] ?? null;
    $perPage  = $validated['per_page'] ?? 10;
    $sortBy   = $validated['sort_by'] ?? 'c.created_at';
    $sortDir  = $validated['sort_dir'] ?? 'desc';

    $userId = auth()->user()->id_user;

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
            'c.notes',
            'c.converted_at',
            'c.created_at',
            'c.updated_at',
            'c.deleted_at',

            // RELATION LEAD
            'l.company_name as lead_company_name',
            'l.lead_source',
            'l.lead_status',

            // MASTER
            'cat.name as category_name',
            'ind.name as industry_name',

            // USER
            'owner.fullname as owner_name',
            'sales.fullname as assigned_name',
        ])
        ->leftJoin('leads as l', 'l.id', '=', 'c.lead_id')
        ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
        ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
        ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.created_by')
        ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')

        // FILTER hanya data yang dibuat oleh user/sales login
        ->where('c.created_by', $userId);

    // SEARCH
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('c.company_name', 'ILIKE', "%{$search}%")
              ->orWhere('c.contact_name', 'ILIKE', "%{$search}%")
              ->orWhere('c.email', 'ILIKE', "%{$search}%")
              ->orWhere('c.customer_code', 'ILIKE', "%{$search}%");
        });
    }

    // SORT
    $query->orderBy($sortBy, $sortDir);

    $results = $query->paginate($perPage);

    return ApiResponse::paginate(
        CostumersResourcesCollection::make($results),
        $results->isEmpty()
            ? 'Data customer not found'
            : 'Success'
    );
}



}
