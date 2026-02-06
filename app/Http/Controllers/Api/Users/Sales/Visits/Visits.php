<?php

namespace App\Http\Controllers\Api\Users\Sales\Visits;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\VisitLeadsDataIndex;
use App\Http\Resources\VisitLeadsDataResourcesCollection;
use App\Http\Requests\VisitCustomerDataIndex;
use App\Http\Resources\VisitCustomersDataResourcesCollection;
use App\Models\VisitsModel;
use App\Models\MsCustomers;
use App\Models\MsLeadsModel;
use App\Models\MsFollowUp;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\VisitsResources;
use App\Http\Resources\VisitsResourcesCollection;
use App\Http\Requests\VisitValidationIndex;



class Visits extends Controller
{
        protected $VisitsModel;
        protected $MsCustomers;
        protected $MsLeadsModel;
        protected $MsFollowUp;
        public function __construct(VisitsModel $VisitsModel,
         MsCustomers $MsCustomers, MsLeadsModel $MsLeadsModel,
         MsFollowUp $MsFollowUp) {
            $this->VisitsModel = $VisitsModel;
            $this->MsCustomers = $MsCustomers;
            $this->MsLeadsModel = $MsLeadsModel;
            $this->MsFollowUp = $MsFollowUp;
        }




    // code for get data visit leads and customer

    public function getVisit(VisitValidationIndex $request) {
                $validated = $request->validated();
                $search   = $validated['search'] ?? null;
                $perPage  = $validated['per_page'] ?? 10;
                $sortBy   = $validated['sort_by'] ?? 'c.created_at';
                $sortDir  = $validated['sort_dir'] ?? 'desc';

                $userId = auth()->user()->id_user;

                $query = DB::table('visits as v')
                    ->select([
                        'v.*',
                       

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
                    VisitsResourcesCollection::make($results),
                    $results->isEmpty()
                        ? 'Data customer not found'
                        : 'Success'
                );
    }



    //  code for get master data visit leads
    public function VisitLeads(VisitLeadsDataIndex $request)
        {
            $validated = $request->validated();

            $search  = $validated['search'] ?? null;

            $perPage = (int) ($validated['per_page'] ?? 10);
            $page    = (int) ($validated['page'] ?? 1);
            
            // Whitelist kolom sorting
            $allowedSort = [
                'company_name' => 'l.company_name',
                'created_at'   => 'l.created_at',
                'last_contacted_at' => 'l.last_contacted_at',
            ];

            $sortByKey = $validated['sort_by'] ?? 'created_at';
            $sortBy    = $allowedSort[$sortByKey] ?? 'l.created_at';

            $sortDirInput = $validated['sort_dir'] ?? 'desc';
            $sortDir = in_array($sortDirInput, ['asc', 'desc']) ? $sortDirInput : 'desc';

            $userId = auth()->user()->id_user;

            $query = DB::table('leads as l')
                ->select([
                    'l.id',
                    'l.company_name',
                    'l.contact_name',
                    'l.email',
                    'l.phone',
                    'l.lead_category_id',
                    'l.industry_id',
                    'l.id_user',
                    'l.assigned_to',
                    'l.created_by',
                    'l.lead_source',
                    'l.lead_status',
                    'l.visibility_type',
                    'l.notes',
                    'l.address',
                    'l.last_contacted_at',
                    'l.converted_at',
                    'l.created_at',
                    'l.updated_at',
                    'l.deleted_at',
                    'cat.name as category_name',
                    'ind.name as industry_name',
                    'owner.fullname as owner_name',
                    'sales.fullname as assigned_name',
                    'v.id as active_visit_id',
                    'v.visit_status as visit_status',
                    'v.check_in_at as active_check_in_at',

                ])
                ->leftJoin('lead_categories as cat', 'cat.id', '=', 'l.lead_category_id')
                ->leftJoin('lead_industries as ind', 'ind.id', '=', 'l.industry_id')
                ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'l.id_user')
                ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'l.assigned_to')
               ->leftJoinSub(
    DB::table('visits')
        ->select('id','lead_id','visit_status','check_in_at')
        ->whereIn('visit_status', ['ONGOING', 'CHECKED_IN']),
    'v',
    'v.lead_id',
    '=',
    'l.id'
)

                // ->whereIn('l.lead_status', ['New', 'Contacted', 'Qualified'])
                // ->where(function ($q) use ($userId) {
                //     $q->where('l.created_by', $userId)
                //     ->orWhere('l.assigned_to', $userId);
                // });
                ->where(function ($q) use ($userId) {
                    $q->where(function ($q2) use ($userId) {
                        $q2->where('l.created_by', $userId)
                        ->whereIn('l.lead_status', ['New', 'Contacted', 'Qualified']);
                    })
                    ->orWhere('l.assigned_to', $userId);
                });

            /**
             * SEARCH
             */
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('l.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('l.contact_name', 'ILIKE', "%{$search}%")
                    ->orWhere('l.email', 'ILIKE', "%{$search}%");
                });
            }

            /**
             * SORT
             */
            if ($sortBy === 'l.last_contacted_at') {
                $query->orderByRaw('l.last_contacted_at ASC NULLS FIRST');
            } else {
                $query->orderBy($sortBy, $sortDir);
            }

            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return ApiResponse::paginate(
                VisitLeadsDataResourcesCollection::make($results),
                $results->isEmpty()
                    ? 'Data yang Anda cari tidak ditemukan'
                    : 'Success'
            );
        }


      //  code for get master data visit customer
    public function VisitCustomers(VisitCustomerDataIndex $request)
        {
            $validated = $request->validated();

            $search  = $validated['search'] ?? null;
            $perPage = (int) ($validated['per_page'] ?? 10);
            $page    = (int) ($validated['page'] ?? 1);

            /**
             * Whitelist kolom sorting
             */
            $allowedSort = [
                'company_name' => 'c.company_name',
                'created_at'   => 'c.created_at',
                'converted_at' => 'c.converted_at',
            ];

            $sortByKey = $validated['sort_by'] ?? 'created_at';
            $sortBy    = $allowedSort[$sortByKey] ?? 'c.created_at';

            $sortDirInput = $validated['sort_dir'] ?? 'desc';
            $sortDir = in_array($sortDirInput, ['asc', 'desc']) ? $sortDirInput : 'desc';

            $userId = auth()->user()->id_user;

            $query = DB::table('customers as c')
                ->select([
                    'c.id',
                    'c.customer_code',
                    'c.company_name',
                    'c.contact_name',
                    'c.email',
                    'c.phone',
                    'c.lead_id',
                    'c.lead_category_id',
                    'c.industry_id',
                    'c.id_user',
                    'c.assigned_to',
                    'c.created_by',
                    'c.lead_source',
                    'c.customer_status',
                    'c.visibility_type',
                    'c.notes',
                    'c.address',
                    'c.converted_at',
                    'c.created_at',
                    'c.updated_at',
                    'cat.name as category_name',
                    'ind.name as industry_name',
                    'owner.fullname as owner_name',
                    'sales.fullname as assigned_name',
                ])
                ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
                ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
                ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.id_user')
                ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')
                ->whereNull('c.deleted_at')

                /**
                 * VISIBILITY USER
                 */
                ->where(function ($q) use ($userId) {
                    $q->where('c.created_by', $userId)
                    ->orWhere('c.assigned_to', $userId);
                })

                /**
                 * FILTER STATUS CUSTOMER
                 */
                ->whereIn('c.customer_status', ['Active', 'Dormant']);

            /**
             * SEARCH
             */
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('c.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('c.contact_name', 'ILIKE', "%{$search}%")
                    ->orWhere('c.email', 'ILIKE', "%{$search}%")
                    ->orWhere('c.customer_code', 'ILIKE', "%{$search}%");
                });
            }

            /**
             * SORT
             */
            $query->orderBy($sortBy, $sortDir);

            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return ApiResponse::paginate(
                VisitCustomersDataResourcesCollection::make($results),
                $results->isEmpty()
                    ? 'Data customer tidak ditemukan'
                    : 'Success'
            );
        }



        //  generate code customer
          public function generateCustomerCode()
        {
            $date = now()->format('Ymd'); // 20260121

            // Hitung berapa customer sudah dibuat hari ini
            $countToday = DB::table('customers')
                ->whereDate('created_at', now()->toDateString())
                ->count();

            $number = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT); // 001, 002, 003

            return "CUST-{$date}-{$number}";
        }


            // generate code follow up
                private function generateFollowUpCode(): string
                {
                    $date = now()->format('Ymd');

                    $lastCode = DB::table('follow_ups')
                        ->whereDate('created_at', now()->toDateString())
                        ->where('follow_up_code', 'like', "FUP-{$date}-%")
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->value('follow_up_code');

                    $nextNumber = 1;

                    if ($lastCode) {
                        // ambil 0001 dari FUP-20260201-0001-XXXXXX
                        $parts = explode('-', $lastCode);
                        $lastNumber = (int) ($parts[2] ?? 0);
                        $nextNumber = $lastNumber + 1;
                    }

                    $uuidShort = strtoupper(Str::uuid()->toString());
                    $uuidShort = substr(str_replace('-', '', $uuidShort), 0, 6);

                    return sprintf(
                        'FUP-%s-%04d-%s',
                        $date,
                        $nextNumber,
                        $uuidShort
                    );
                }



                public function startVisit(Request $request, $leadId)
                    {
                        $user = auth()->user();
                        $salesId = $user->id_user;

                        DB::beginTransaction();
                        try {
                            // Optional: pastikan tidak ada visit ONGOING
                            $ongoing = VisitsModel::where('sales_id',$salesId)
                                ->where('visit_status', 'ONGOING')
                                ->first();

                            if ($ongoing) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Masih ada visit yang sedang berlangsung'
                                ], 422);
                            }

                            $visit = VisitsModel::create([
                                'visit_code'   => VisitsModel::generateVisitCode(), // atau helper kamu
                                'sales_id'     => $salesId,           
                                'lead_id'      => $leadId,                
                                'visit_at'     => now(),
                                'visit_status' => 'ONGOING',
                                'created_by'   => $salesId,             
                            ]);

                            DB::commit();

                            return response()->json([
                                'success' => true,
                                'data' => $visit
                            ]);
                        } catch (\Throwable $e) {
                            DB::rollBack();

                            return response()->json([
                                'success' => false,
                                'message' => $e->getMessage()
                            ], 500);
                        }
                    }



             public function checkInVisit(Request $request, $visitId)
            {
                $request->validate([
                    'latitude'      => 'required',
                    'longitude'     => 'required',
                    'gps_snapshot'  => 'required|string',
                    'photo'         => 'required|image|max:4096',
                ]);

                $userId = auth()->user()->id_user;

                $visit = VisitsModel::where('id', $visitId)
                    ->where('sales_id', $userId)
                    ->where('visit_status', 'ONGOING')
                    ->firstOrFail();

                DB::beginTransaction();
                try {
                    // upload photo
                    $path = $request->file('photo')->store('visits/checkin', 'public');

                    $visit->update([
                        'check_in_at'  => now(),
                        'latitude'     => $request->latitude,
                        'longitude'    => $request->longitude,
                        'gps_snapshot' => $request->gps_snapshot,
                        'photo'        => $path,
                        'visit_status' => 'CHECKED_IN',
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'data' => $visit
                    ]);
                } catch (\Throwable $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage()
                    ], 500);
                }
            }




public function checkOutVisit(Request $request, $visitId)
{
    $request->validate([
        'notes'             => 'required|string',
        'visit_result'      => 'nullable|string',
        'customer_response' => 'required|in:potential_customers,consideration_stage,prospective_customers,failed,convert_to_customer',
    ]);

    DB::transaction(function () use ($request, $visitId) {

        // =========================
        // 1. GET VISIT (LOCK)
        // =========================
        $visit = VisitsModel::lockForUpdate()
            ->where('id', $visitId)
            ->where('visit_status', 'CHECKED_IN')
            ->firstOrFail();

        // =========================
        // 2. GET LEAD (LOCK)
        // =========================
        $lead = MsLeadsModel::lockForUpdate()
            ->findOrFail($visit->lead_id);

        // =========================
        // 3. UPDATE VISIT (CHECK OUT)
        // =========================
        $visit->update([
            'check_out_at'      => now(),
            'notes'             => $request->notes,
            'visit_result'      => $request->visit_result,
            'customer_response' => $request->customer_response,
            'visit_status'      => 'DONE',
        ]);

        // =========================
        // 4. PROCESS CUSTOMER RESPONSE
        // =========================
        switch ($request->customer_response) {

            // -------------------------
            // A. FOLLOW UP
            // -------------------------
            case 'potential_customers':
            case 'consideration_stage':
            case 'prospective_customers':

                $followUpCode = $this->generateFollowUpCode();

                $lead->update([
                    'lead_status'       => $request->customer_response,
                    'last_contacted_at' => now(),
                ]);

                MsFollowUp::create([
                    'lead_id'        => $lead->id,
                    'visit_id'       => $visit->id,
                    'follow_up_code' => $followUpCode,
                    'follow_up_at'   => now()->addDays(3),
                    'subject'        => 'Result Visit',
                    'notes'          => $request->notes,
                    'follow_up_type' => 'VISIT',
                    'created_by'     => auth()->id(),
                ]);
                break;

            // -------------------------
            // B. FAILED
            // -------------------------
            case 'failed':

                $lead->update([
                    'lead_status'       => 'failed',
                    'last_contacted_at' => now(),
                ]);
                break;

            // -------------------------
            // C. CONVERT TO CUSTOMER
            // -------------------------
            case 'convert_to_customer':

                $customerCode = $this->generateCustomerCode();

                $customer = MsCustomers::create([
                    'lead_id'          => $lead->id,
                    'lead_category_id' => $lead->lead_category_id,
                    'industry_id'      => $lead->industry_id,
                    'customer_code'    => $customerCode,
                    'company_name'     => $lead->company_name,
                    'contact_name'     => $lead->contact_name,
                    'email'            => $lead->email,
                    'phone'            => $lead->phone,
                    'id_user'          => $lead->id_user ?? auth()->id(),
                    'assigned_to'      => $lead->assigned_to,
                    'created_by'       => auth()->id(),
                    'address'          => $lead->address,
                    'notes'            => $lead->notes,
                    'converted_at'     => now(),
                    'customer_status'  => 'Active',
                ]);

                // update lead
                $lead->update([
                    'lead_status'  => 'customer',
                    'converted_at' => now(),
                    'last_contacted_at' => now(),
                ]);

                // fix check constraint (visit tidak boleh punya lead + customer)
                $visit->update([
                    'customer_id' => $customer->id,
                    'lead_id'     => null
                ]);
                break;
        }
    });

    return response()->json([
        'message' => 'Check out visit berhasil disimpan'
    ], 200);
}

        }
