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
                        'c.lead_source',
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
                    ->where(function($q) use ($userId) {
                                $q->where('c.created_by', $userId)
                                ->orWhere('c.assigned_to', $userId);
                            });

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


        private function generateCustomerCode()
        {
            $date = now()->format('Ymd'); // 20260121

            // Hitung berapa customer sudah dibuat hari ini
            $countToday = DB::table('customers')
                ->whereDate('created_at', now()->toDateString())
                ->count();

            $number = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT); // 001, 002, 003

            return "CUST-{$date}-{$number}";
        }



    public function storeCostumers(CostumersValidationRequest $request)
        {
        $customerCode = $this->generateCustomerCode();
            try {
                $user = auth()->user();
                $userId = $user->id_user;
                $data = $request->validated();

                // Insert customer
                $customerId = DB::table('customers')->insertGetId([
                    'customer_code'    => $customerCode,
                    'company_name'     => $data['company_name'],
                    'contact_name'     => $data['contact_name'],
                    'email'            => $data['email'] ?? null,
                    'phone'            => $data['phone'] ?? null,
                    'industry_id'      => $data['industry_id'] ?? null,
                    'lead_category_id' => $data['lead_category_id'] ?? null,
                    'assigned_to'      => $data['assigned_to'] ?? null,
                    'customer_status'  => 'Active',
                    'lead_source'      => $data['lead_source'] ?? null,  // ← tambahkan ini
                    'id_user'          => $userId,
                    'created_by'       => $userId,
                    

                    'visibility_type'  => $data['visibility_type'] ?? 'PRIVATE',
                    'notes'            => $data['notes'] ?? null,
                    'address'          => $data['address'] ?? null,
                    'converted_at'     => now(),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                // Ambil data customer yang baru dibuat
                $customer = DB::table('customers as c')
                    ->select([
                        'c.*',
                        'cat.name as category_name',
                        'ind.name as industry_name',
                        'owner.fullname as owner_name',
                        'sales.fullname as assigned_name',
                    ])
                    ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
                    ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
                    ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.id_user')
                    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')
                    ->where('c.id', $customerId)
                    ->first();

                return ApiResponse::success($customer, 'Success Create New Customer', 201);

            } catch (\Illuminate\Database\QueryException $e) {
                return ApiResponse::error('Failed to create customer (query error)', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 422);
            } catch (\Exception $e) {
                return ApiResponse::error('An error occurred while creating the customer.', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }


    public function updateCostumers(CostumersValidationRequest $request, $id)
        {
            try {
                $user = auth()->user();
                $userId = $user->id_user;
                $data = $request->validated();

                // Cek apakah customer ada
                $customer = DB::table('customers')->where('id', $id)->first();
                if (!$customer) {
                    return ApiResponse::error('Customer not found.', [], 404);
                }

                // Optional: cek assigned_to valid
                if (!empty($data['assigned_to'])) {
                    $exists = DB::table('ms_users')->where('id_user', $data['assigned_to'])->exists();
                    if (!$exists) {
                        $data['assigned_to'] = null; // atau throw error jika wajib
                    }
                }

                // Update customer
                DB::table('customers')->where('id', $id)->update([
                    'company_name'     => $data['company_name'],
                    'contact_name'     => $data['contact_name'],
                    'email'            => $data['email'] ?? null,
                    'phone'            => $data['phone'] ?? null,
                    'industry_id'      => $data['industry_id'] ?? null,
                    'lead_category_id' => $data['lead_category_id'] ?? null,
                    // 'assigned_to'      => $data['assigned_to'] ?? null,
                    'lead_source'      => $data['lead_source'] ?? null,
                    'visibility_type'  => $data['visibility_type'] ?? 'PRIVATE',
                    'customer_status'  => $data['customer_status'] ?? 'Active',
                    'notes'            => $data['notes'] ?? null,
                    'address'          => $data['address'] ?? null,
                    'updated_at'       => now(),
                ]);

                // Ambil data customer yang diupdate
                $customer = DB::table('customers as c')
                    ->select([
                        'c.*',
                        'cat.name as category_name',
                        'ind.name as industry_name',
                        'owner.fullname as owner_name',
                        'sales.fullname as assigned_name',
                    ])
                    ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
                    ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
                    ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.id_user')
                    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')
                    ->where('c.id', $id)
                    ->first();

                return ApiResponse::success($customer, 'Success Update Customer', 200);

            } catch (\Illuminate\Database\QueryException $e) {
                return ApiResponse::error('Failed to update customer (query error)', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 422);
            } catch (\Exception $e) {
                return ApiResponse::error('An error occurred while updating the customer.', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }


            public function destroyCostumers($id)
            {
                try {
                    // Cek apakah customer ada
                    $customer = DB::table('customers')->where('id', $id)->first();
                    if (!$customer) {
                        return ApiResponse::error('Customer not found.', [], 404);
                    }

                    // Hapus customer
                    DB::table('customers')->where('id', $id)->delete();

                    return ApiResponse::success(null, 'Customer deleted successfully.', 200);

                } catch (\Illuminate\Database\QueryException $e) {
                    return ApiResponse::error('Failed to delete customer (query error)', [
                        'exception' => config('app.debug') ? $e->getMessage() : null
                    ], 422);
                } catch (\Exception $e) {
                    return ApiResponse::error('An error occurred while deleting the customer.', [
                        'exception' => config('app.debug') ? $e->getMessage() : null
                    ], 500);
                }
            }


        public function showCostumers($id)
        {
            try {
                // Ambil data customer beserta relasi
                $customer = DB::table('customers as c')
                    ->select([
                        'c.*',
                        'cat.name as category_name',
                        'ind.name as industry_name',
                        'owner.fullname as owner_name',
                        'sales.fullname as assigned_name',
                    ])
                    ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
                    ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
                    ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.id_user')
                    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')
                    ->where('c.id', $id)
                    ->first();

                if (!$customer) {
                    return ApiResponse::error('Customer not found.', [], 404);
                }

                return ApiResponse::success($customer, 'Customer detail retrieved successfully.', 200);

            } catch (\Illuminate\Database\QueryException $e) {
                return ApiResponse::error('Failed to fetch customer detail (query error)', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 422);
            } catch (\Exception $e) {
                return ApiResponse::error('An error occurred while fetching customer detail.', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }



        // code persiapan convert lead to customer
        public function convertLeadToCustomer($leadId)
        {
            try {
                $user = auth()->user();
                $userId = $user->id_user;

                // Ambil data lead
                $lead = DB::table('leads')->where('id', $leadId)->first();
                if (!$lead) {
                    return ApiResponse::error('Lead not found.', [], 404);
                }

                // Cek apakah lead sudah dikonversi
                $existingCustomer = DB::table('customers')
                    ->where('lead_id', $leadId)
                    ->first();
                if ($existingCustomer) {
                    return ApiResponse::error('Lead already converted to customer.', [], 422);
                }

                // Generate customer code
                $customerCode = $this->generateCustomerCode();

                // Insert customer
                $customerId = DB::table('customers')->insertGetId([
                    'lead_id'          => $lead->id,
                    'lead_category_id' => $lead->lead_category_id,
                    'industry_id'      => $lead->industry_id,
                    'customer_code'    => $customerCode,
                    'company_name'     => $lead->company_name,
                    'contact_name'     => $lead->contact_name,
                    'email'            => $lead->email ?? null,
                    'phone'            => $lead->phone ?? null,
                    'id_user'          => $userId,
                    'assigned_to'      => $lead->assigned_to ?? null,
                    'created_by'       => $userId,
                    'customer_status'  => 'Active',
                    'visibility_type'  => $lead->visibility_type ?? 'PRIVATE',
                    'address'          => $lead->address ?? null,
                    'notes'            => $lead->notes ?? null,
                    'converted_at'     => now(),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                // Ambil data customer baru
                $customer = DB::table('customers')->where('id', $customerId)->first();

                return ApiResponse::success($customer, 'Lead converted to customer successfully.', 201);

            } catch (\Exception $e) {
                return ApiResponse::error('Failed to convert lead.', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }




        //ini untuk timeline follow up customer tampil di customer detail (implementasi saat nanti semua sudah siap)
        public function customerFollowUpTimeline(Request $request, $customerId)
        {
            $userId = auth()->user()->id_user;

            try {
                /* ================= CEK AKSES CUSTOMER ================= */
                $customer = DB::table('customers')
                    ->where('id', $customerId)
                    ->where(function ($q) use ($userId) {
                        $q->where('created_by', $userId)
                        ->orWhere('assigned_to', $userId);
                    })
                    ->whereNull('deleted_at')
                    ->first();

                if (!$customer) {
                    return ApiResponse::error(
                        'Customer not found or access denied',
                        null,
                        404
                    );
                }

                /* ================= TIMELINE FOLLOW UP ================= */
                $timeline = DB::table('follow_ups as fu')
                    ->select([
                        'fu.id',
                        'fu.follow_up_at',
                        'fu.follow_up_type',
                        'fu.notes',
                        'fu.created_at',

                        'sales.fullname as sales_name',
                    ])
                    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'fu.created_by')
                    ->where('fu.customer_id', $customerId)
                    ->whereNull('fu.deleted_at')
                    ->orderBy('fu.follow_up_at', 'desc')
                    ->get();

                return ApiResponse::success(
                    $timeline,
                    'Success Get Customer Follow Up Timeline'
                );

            } catch (\Throwable $e) {
                return ApiResponse::error(
                    'Failed to get follow up timeline',
                    config('app.debug') ? ['exception' => $e->getMessage()] : null,
                    500
                );
            }
        }


}
