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
use App\Http\Requests\VisitValidationForExternalIndex;


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


                //untuk target map 
                //Skenario Query       Param
                //Hari ini           (default)Tidak perlu kirim apa-apa
                //Tanggal tertentu   ?date_from=2025-06-01&date_to=2025-06-01
                //Range seminggu     ?date_from=2025-06-01&date_to=2025-06-07
                //Semua data         ?date_from=2020-01-01&date_to=2099-12-31
                public function getVisitTargetMap(Request $request)
                {
                    // Default hari ini, bisa dioverride via query param
                    $dateFrom = $request->input('date_from', today()->toDateString());
                    $dateTo   = $request->input('date_to', today()->toDateString());

                    $query = DB::table('visits as v')
                        ->select([
                            'v.id',
                            'v.visit_code',
                            'v.visit_at',
                            'v.check_in_at',
                            'v.check_out_at',
                        // Tambahkan di select
                            DB::raw("CAST(v.latitude AS DECIMAL(10,7)) as latitude"),
                            DB::raw("CAST(v.longitude AS DECIMAL(10,7)) as longitude"),
                            'v.gps_snapshot',
                            'v.lead_id',
                            'v.customer_id',

                            'u.id_user as sales_id',
                            'u.fullname as sales_name',
                            'u.image as sales_photo',

                            DB::raw("
                                CASE
                                    WHEN u.image IS NOT NULL AND u.image != ''
                                        THEN CONCAT('" . asset('storage/users') . "/', u.image)
                                    ELSE '" . asset('storage/users/default.png') . "'
                                END as sales_photo_url
                            "),

                            DB::raw("
                                CASE
                                    WHEN v.lead_id IS NOT NULL THEN 'LEAD'
                                    WHEN v.customer_id IS NOT NULL THEN 'CUSTOMER'
                                    ELSE 'UNKNOWN'
                                END as target_type
                            "),

                            DB::raw("
                                CASE
                                    WHEN v.lead_id IS NOT NULL THEN l.company_name
                                    WHEN v.customer_id IS NOT NULL THEN c.company_name
                                    ELSE NULL
                                END as target_name
                            "),

                            DB::raw("
                                CASE
                                    WHEN v.lead_id IS NOT NULL THEN l.contact_name
                                    WHEN v.customer_id IS NOT NULL THEN c.contact_name
                                    ELSE NULL
                                END as target_contact
                            "),

                            DB::raw("
                                CASE
                                    WHEN v.lead_id IS NOT NULL THEN l.address
                                    WHEN v.customer_id IS NOT NULL THEN c.address
                                    ELSE NULL
                                END as target_address
                            "),

                            DB::raw("
                                CASE
                                    WHEN v.check_in_at IS NULL
                                        AND v.visit_at <= NOW()
                                        THEN 'BELUM_CHECK_IN'
                                    WHEN v.check_in_at IS NOT NULL
                                        AND v.check_out_at IS NULL
                                        THEN 'SEDANG_CHECK_IN'
                                    WHEN v.check_out_at IS NOT NULL
                                        THEN 'SELESAI'
                                    ELSE 'UNKNOWN'
                                END as visit_status_label
                            "),

                            DB::raw("
                                CASE
                                    WHEN v.latitude IS NOT NULL
                                        AND v.longitude IS NOT NULL
                                        THEN true
                                    ELSE false
                                END as show_on_map
                            ")
                        ])

                        ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
                        ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
                        ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')

                        ->where(function ($q) {
                            $q
                                ->where(function ($qq) {
                                    $qq->whereNull('v.check_in_at')
                                    ->where('v.visit_at', '<=', now());
                                })
                                ->orWhere(function ($qq) {
                                    $qq->whereNotNull('v.check_in_at')
                                    ->whereNull('v.check_out_at');
                                })
                                ->orWhereNotNull('v.check_out_at');
                        })

                        // Filter tanggal — default hari ini
                        ->whereBetween('v.visit_at', [
                            $dateFrom . ' 00:00:00',
                            $dateTo   . ' 23:59:59',
                        ])
                        
                        //untuk soft delete
                        ->whereNull('v.deleted_at')
                        ->whereNull('l.deleted_at')  
                        ->whereNull('c.deleted_at')

                        ->orderBy('v.visit_at', 'desc');

                    $results = $query->get();

                    return ApiResponse::success(
                        $results,
                        $results->isEmpty()
                            ? 'Tidak ada data visit pada periode ini'
                            : 'Success'
                    );
                }


        // code for get data visit leads and customer  for  (external)
        public function getVisitAllData(VisitValidationForExternalIndex $request)
{
    $validated = $request->validated();

    $perPage = $validated['per_page'];
    $search  = $validated['search'] ?? null;
    $sortBy  = $validated['sort_by'];
    $sortDir = strtolower($validated['sort_dir']);
    $status  = $validated['visit_status'] ?? null;

    /*
    |--------------------------------------------------------------------------
    | Mapping Sorting Column (ANTI SQL INJECTION)
    |--------------------------------------------------------------------------
    */
    $sortMap = [
        'company_name' => DB::raw("COALESCE(l.company_name, c.company_name)"),
        'created_at'   => 'v.created_at',
        'visit_date'   => 'v.visit_at',
        'check_out'    => 'v.check_out_at',
    ];

    $orderColumn = $sortMap[$sortBy] ?? 'v.created_at';

    /*
    |--------------------------------------------------------------------------
    | MAIN QUERY
    |--------------------------------------------------------------------------
    */
    $query = DB::table('visits as v')
        ->select([

            'v.id',
            'v.visit_code',
            'v.lead_id',
            'v.customer_id',
            'v.sales_id',

            // Company name dari lead atau customer
            DB::raw("COALESCE(l.company_name, c.company_name) as company_name"),

            // Visit type label
            DB::raw("
                CASE
                    WHEN v.customer_id IS NOT NULL THEN 'CUSTOMER'
                    ELSE 'LEAD'
                END as visit_type
            "),

            // Target contact
            DB::raw("
                CASE
                    WHEN v.lead_id IS NOT NULL THEN l.contact_name
                    WHEN v.customer_id IS NOT NULL THEN c.contact_name
                    ELSE NULL
                END as target_contact
            "),

            // Target phone
            DB::raw("
                CASE
                    WHEN v.lead_id IS NOT NULL THEN l.phone
                    WHEN v.customer_id IS NOT NULL THEN c.phone
                    ELSE NULL
                END as target_phone
            "),

            // Target address
            DB::raw("
                CASE
                    WHEN v.lead_id IS NOT NULL THEN l.address
                    WHEN v.customer_id IS NOT NULL THEN c.address
                    ELSE NULL
                END as target_address
            "),

            // Visit progress (English)
            DB::raw("
                CASE
                    WHEN v.check_in_at IS NULL
                        THEN 'PLANNED'
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NULL
                        THEN 'ONGOING'
                    WHEN v.check_out_at IS NOT NULL
                        THEN 'DONE'
                END as visit_progress
            "),

            // Visit status label (Indonesian)
            DB::raw("
                CASE
                    WHEN v.check_in_at IS NULL
                        THEN 'SEDANG VISIT'
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NULL
                        THEN 'SEDANG CHECK IN'
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NOT NULL
                        THEN 'SELESAI'
                END as visit_status_label
            "),

            'u.fullname as sales_name',
            'v.visit_at',
            'v.check_in_at',
            'v.check_out_at',

            /*
            |------------------------------------------------------------------
            | DURATIONS (PostgreSQL)
            |------------------------------------------------------------------
            */

            // Durasi dari jadwal visit ke check-in
            DB::raw("
                CASE
                    WHEN v.check_in_at IS NOT NULL
                        THEN TO_CHAR(v.check_in_at - v.visit_at, 'HH24:MI:SS')
                    ELSE NULL
                END as time_from_visit_to_check_in
            "),

            // Durasi dari check-in ke check-out
            DB::raw("
                CASE
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NOT NULL
                        THEN TO_CHAR(v.check_out_at - v.check_in_at, 'HH24:MI:SS')
                    ELSE NULL
                END as time_from_check_in_to_check_out
            "),

            // Total durasi dari visit ke check-out
            DB::raw("
                CASE
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NOT NULL
                        THEN TO_CHAR(v.check_out_at - v.visit_at, 'HH24:MI:SS')
                    ELSE NULL
                END as total_time_result
            "),

            'v.latitude',
            'v.longitude',
            'v.gps_snapshot',
            'v.photo',
            // Photo visit (full URL)
            DB::raw("
                CASE
                    WHEN v.photo IS NOT NULL AND v.photo != ''
                        THEN CONCAT('" . asset('storage') . "/', v.photo)
                    ELSE NULL
                END as photo_url
            "),
            'v.notes',
            'v.visit_result',
            'v.visit_status',
            'v.customer_response',
            'v.has_complaint',
            'v.complaint_detail',
            'v.has_potential_order',
            'v.potential_order_detail',
            'v.created_by',
            'v.created_at',
            'v.updated_at',
        ])

        ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
        ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
        ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')

        // Hanya visit yang valid (punya lead atau customer)
        ->where(function ($q) {
            $q->whereNotNull('v.lead_id')
              ->orWhereNotNull('v.customer_id');
        })

        // Exclude soft deleted visits
        ->whereNull('v.deleted_at')

        // Setelah bagian soft delete, sebelum FILTER VISIT TYPE
->when($validated['date_from'] ?? null, function ($q, $date) {
    $q->whereDate('v.visit_at', '>=', $date);
})
->when($validated['date_to'] ?? null, function ($q, $date) {
    $q->whereDate('v.visit_at', '<=', $date);
})

        // Exclude soft deleted leads (hanya jika lead_id ada)
        ->where(function ($q) {
            $q->whereNull('v.lead_id')
              ->orWhereNull('l.deleted_at');
        })

        // Exclude soft deleted customers (hanya jika customer_id ada)
        ->where(function ($q) {
            $q->whereNull('v.customer_id')
              ->orWhereNull('c.deleted_at');
        });

    /*
    |--------------------------------------------------------------------------
    | FILTER VISIT TYPE (LEAD / CUSTOMER)
    |--------------------------------------------------------------------------
    */
    $query->when($validated['visit_type'] ?? null, function ($q, $type) {
        if ($type === 'LEAD') {
            $q->whereNotNull('v.lead_id')->whereNull('v.customer_id');
        } elseif ($type === 'CUSTOMER') {
            $q->whereNotNull('v.customer_id')->whereNull('v.lead_id');
        }
    });

    /*
    |--------------------------------------------------------------------------
    | FILTER STATUS
    |--------------------------------------------------------------------------
    */
    $query->when($status, function ($q) use ($status) {
        switch ($status) {
            case 'PLANNED':
                $q->whereNull('v.check_in_at');
                break;
            case 'ONGOING':
                $q->whereNotNull('v.check_in_at')
                  ->whereNull('v.check_out_at');
                break;
            case 'DONE':
                $q->whereNotNull('v.check_out_at');
                break;
        }
    });

    /*
    |--------------------------------------------------------------------------
    | SEARCHING
    |--------------------------------------------------------------------------
    */
    $query->when($search, function ($q) use ($search) {
        $q->where(function ($sq) use ($search) {
            $sq->where('l.company_name', 'ILIKE', "%{$search}%")
               ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
               ->orWhere('v.visit_code', 'ILIKE', "%{$search}%")
               ->orWhere('u.fullname', 'ILIKE', "%{$search}%");
        });
    });

    /*
    |--------------------------------------------------------------------------
    | SORTING
    |--------------------------------------------------------------------------
    */
    $query->orderBy($orderColumn, $sortDir);

    $results = $query->paginate($perPage);

    return ApiResponse::paginate(
        VisitsResourcesCollection::make($results),
        $results->isEmpty()
            ? 'Data visit tidak ditemukan'
            : 'Success'
    );
}



        // get data untuk tabel sales (ambil semua data hasil visit entah itu hasilnya masih follow up, jadi customer ataupun failed)
        public function getVisitLead(VisitValidationIndex $request)
        {
            $validated = $request->validated();

            $perPage = $validated['per_page'] ?? 10;
            $userId  = auth()->user()->id_user;
            $search  = $validated['search'] ?? null;
            $sortBy  = $validated['sort_by'] ?? 'created_at';
            $sortDir = strtolower($validated['sort_dir'] ?? 'desc');
            $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc';
            $sortMap = [
                'company_name' => 'l.company_name',
                'created_at'   => 'v.created_at',
                'visit_date'   => 'v.visit_at',
                'check_out'    => 'v.check_out_at',
            ];
            $orderColumn = $sortMap[$sortBy] ?? 'v.created_at';


                $query = DB::table('visits as v')
            ->select([
                'v.id',
                'v.visit_code',
                'v.lead_id',
                'v.customer_id',
                'v.sales_id',

                DB::raw("
                    COALESCE(l.company_name, c.company_name) as company_name
                "),

                DB::raw("
            CASE
                WHEN v.customer_id IS NOT NULL THEN 'CUSTOMER'
                ELSE 'LEAD'
            END as visit_type
        "),


                'u.fullname as sales_name',
                'v.visit_at',
                'v.check_in_at',

                DB::raw("
                    CASE 
                        WHEN v.check_in_at IS NOT NULL
                        THEN TO_CHAR(v.check_in_at - v.visit_at, 'HH24:MI:SS')
                        ELSE NULL
                    END as time_from_visit_to_check_in
                "),

                DB::raw("
                    CASE
                        WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NOT NULL
                        THEN TO_CHAR(v.check_out_at - v.check_in_at, 'HH24:MI:SS')
                        ELSE NULL
                    END as time_from_check_in_to_check_out
                "),

                DB::raw("
                    CASE
                        WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NOT NULL
                        THEN TO_CHAR(v.check_out_at - v.visit_at, 'HH24:MI:SS')
                        ELSE NULL
                    END as total_time_result
                "),

                'v.check_out_at',
                'v.latitude',
                'v.longitude',
                'v.gps_snapshot',
                'v.photo',
                'v.notes',
                'v.visit_result',
                'v.visit_status',
                'v.customer_response',
                'v.created_by',
                'v.created_at',
                'v.updated_at',
            ])

            ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')

            // visit lead ATAU visit customer
            ->where(function ($q) {
                $q->whereNotNull('v.lead_id')
                ->orWhereNotNull('v.customer_id');
            })

            // filter user
            ->where(function ($q) use ($userId) {
                $q->where('v.created_by', $userId)
                ->orWhere('v.sales_id', $userId);
            })

            // search
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('l.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('v.visit_code', 'ILIKE', "%{$search}%")
                    ->orWhere('u.fullname', 'ILIKE', "%{$search}%");
                });
            })

            ->orderBy($orderColumn, $sortDir);


            $results = $query->paginate($perPage);

            return ApiResponse::paginate(
                VisitsResourcesCollection::make($results),
                $results->isEmpty()
                    ? 'Data visit lead tidak ditemukan'
                    : 'Success'
            );
        }



        public function getVisitDetail($id)
{
    $userId = auth()->user()->id_user;

    $visit = DB::table('visits as v')
        ->select([
            'v.id',
            'v.visit_code',
            'v.lead_id',
            'v.customer_id',
            'v.sales_id',

            DB::raw("
                COALESCE(l.company_name, c.company_name) as company_name
            "),

            DB::raw("
                CASE
                    WHEN v.customer_id IS NOT NULL THEN 'CUSTOMER'
                    ELSE 'LEAD'
                END as visit_type
            "),

            'u.fullname as sales_name',
            'v.visit_at',
            'v.check_in_at',
            'v.check_out_at',

            DB::raw("
                CASE 
                    WHEN v.check_in_at IS NOT NULL
                    THEN TO_CHAR(v.check_in_at - v.visit_at, 'HH24:MI:SS')
                    ELSE NULL
                END as time_from_visit_to_check_in
            "),

            DB::raw("
                CASE
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NOT NULL
                    THEN TO_CHAR(v.check_out_at - v.check_in_at, 'HH24:MI:SS')
                    ELSE NULL
                END as time_from_check_in_to_check_out
            "),

            DB::raw("
                CASE
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NOT NULL
                    THEN TO_CHAR(v.check_out_at - v.visit_at, 'HH24:MI:SS')
                    ELSE NULL
                END as total_time_result
            "),

            'v.latitude',
            'v.longitude',
            'v.gps_snapshot',
            'v.photo',
            'v.notes',
            'v.visit_result',
            'v.visit_status',
            'v.customer_response',
            'v.created_at',
            'v.updated_at',
        ])
        ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
        ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
        ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')

        //  security (hanya visit milik user)
        ->where('v.id', $id)
        ->where(function ($q) use ($userId) {
            $q->where('v.created_by', $userId)
              ->orWhere('v.sales_id', $userId);
        })
        ->first();

    if (!$visit) {
        return response()->json([
            'message' => 'Detail visit tidak ditemukan'
        ], 404);
    }

    return response()->json([
        'message' => 'Success',
        'data'    => $visit
    ]);
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
                    'v.id as active_visit_id',
                    'v.visit_status as visit_status',
                    'v.check_in_at as active_check_in_at',
                ])
                ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
                ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
                ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.id_user')
                ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')

                ->leftJoinSub(
                        DB::table('visits')
                            ->select('id', 'customer_id', 'visit_status', 'check_in_at')
                            ->whereIn('visit_status', ['ONGOING', 'CHECKED_IN']),
                        'v',
                        'v.customer_id', '=', 'c.id'
                    )
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
                        $user    = auth()->user();
                        $salesId = $user->id_user;

                        DB::beginTransaction();

                        try {
                            // 🔒 Cek visit aktif (ONGOING / CHECKED_IN)
                            $activeVisit = VisitsModel::where('sales_id', $salesId)
                                ->whereIn('visit_status', ['ONGOING', 'CHECKED_IN'])
                                ->first();

                            if ($activeVisit) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Masih ada visit yang sedang berjalan atau belum check out'
                                ], 422);
                            }

                            $visit = VisitsModel::create([
                                'visit_code'   => VisitsModel::generateVisitCode(),
                                'sales_id'     => $salesId,
                                'lead_id'      => $leadId,
                                'visit_at'     => now(),
                                'visit_status' => 'ONGOING',
                                'created_by'   => $salesId,
                            ]);

                            DB::commit();

                            return response()->json([
                                'success' => true,
                                'data'    => $visit
                            ], 201);

                        } catch (\Throwable $e) {
                            DB::rollBack();

                            return response()->json([
                                'success' => false,
                                'message' => 'Gagal memulai visit',
                                'error'   => $e->getMessage()
                            ], 500);
                        }
                    }




             public function checkInVisit(Request $request, $visitId)
            {
               
                $request->validate([
                    'latitude' => [
                        'required',
                        'numeric',
                        'between:-90,90'
                    ],

                    'longitude' => [
                        'required',
                        'numeric',
                        'between:-180,180'
                    ],

                    'gps_snapshot' => [
                        'required',
                        'string',
                        'max:5000' // batasi agar tidak kirim string besar
                    ],

                    'photo' => [
                        'required',
                        'image',
                        'mimes:jpg,jpeg,png',
                        'max:4096' // 4MB
                    ],
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
                        'visit_result'      => $request->customer_response,
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

                            // MsFollowUp::create([
                            //     'lead_id'        => $lead->id,
                            //     'visit_id'       => $visit->id,
                            //     'follow_up_code' => $followUpCode,
                            //     'follow_up_at'   => now()->addDays(3),
                            //     'subject'        => 'Result Visit',
                            //     'notes'          => $request->notes,
                            //     'follow_up_type' => 'VISIT',
                            //     'created_by'     => auth()->id(),
                            // ]);
                            $followUpId = DB::table('follow_ups')->insertGetId([
                                'lead_id'        => $lead->id,
                                // 'visit_id'       => $visit->id,
                                'follow_up_code' => $followUpCode,
                                'follow_up_at'   => now()->addDays(3),
                                'subject'        => 'Result Visit',
                                'notes'          => $request->notes,
                                'follow_up_type' => 'VISIT',
                                'status'         => 'PENDING',
                                'created_by'     => auth()->id(),
                                'created_at'     => now(),
                                'updated_at'     => now(),
                            ]);


                            DB::table('follow_up_activities')->insert([
                                'follow_up_id'  => $followUpId,
                                'title'         => 'Follow Up Created Result Visit',
                                'description'   => 'Follow up dibuat otomatis dari hasil visit dengan status: '.$request->customer_response,
                                'activity_type' => 'CREATE',
                                'activity_at'   => now(),
                                'created_at'    => now(),
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
                            // $visit->update([
                            //     'customer_id' => $customer->id,
                            //     'lead_id'     => null
                            // ]);
                            break;
                    }
                });

                return response()->json([
                    'message' => 'Check out visit berhasil disimpan'
                ], 200);
            }



            // start code untuk visit ongoing bagian customer
            public function startVisitCustomer(Request $request, $customersId)
                {
                    $user    = auth()->user();
                    $salesId = $user->id_user;
                    DB::beginTransaction();
                    try {
                        // Cek visit aktif (ONGOING / CHECKED_IN)
                        $activeVisit = VisitsModel::where('sales_id', $salesId)
                            ->whereIn('visit_status', ['ONGOING', 'CHECKED_IN'])
                            ->lockForUpdate()
                            ->first();

                        if ($activeVisit) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Masih ada visit yang sedang berjalan atau belum check out'
                            ], 422);
                        }

                        // Cek apakah customer ini sudah pernah divisit oleh sales ini
                        // $alreadyVisited = VisitsModel::where('sales_id', $salesId)
                        //     ->where('customer_id', $customersId)
                        //     ->lockForUpdate()
                        //     ->exists();

                        // if ($alreadyVisited) {
                        //     return response()->json([
                        //         'success' => false,
                        //         'message' => 'Customer ini sudah pernah kamu visit'
                        //     ], 422);
                        // }

                        // Insert visit baru
                        $visit = VisitsModel::create([
                            'visit_code'   => VisitsModel::generateVisitCode(),
                            'sales_id'     => $salesId,
                            'customer_id'  => $customersId,
                            'visit_at'     => now(),
                            'visit_status' => 'ONGOING',
                            'created_by'   => $salesId,
                        ]);

                        DB::commit();

                        return response()->json([
                            'success' => true,
                            'data'    => $visit
                        ], 201);

                    } catch (\Throwable $e) {
                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => 'Gagal memulai visit',
                            'error'   => $e->getMessage()
                        ], 500);
                    }
                }


                //  check in visit customer
                  public function checkInVisitCustomer(Request $request, $visitId)
            {
               
                $request->validate([
                    'latitude' => [
                        'required',
                        'numeric',
                        'between:-90,90'
                    ],

                    'longitude' => [
                        'required',
                        'numeric',
                        'between:-180,180'
                    ],

                    'gps_snapshot' => [
                        'required',
                        'string',
                        'max:5000' // batasi agar tidak kirim string besar
                    ],

                    'photo' => [
                        'required',
                        'image',
                        'mimes:jpg,jpeg,png',
                        'max:4096' // 4MB
                    ],
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


            // ========================
            // CHECK OUT OLD
            // ========================

            // public function checkOutCustomer(Request $request, $visitId)
            //     {
            //         $request->validate([
            //             'notes'                  => 'required|string',
            //             'customer_response'      => 'required|string',
            //             'has_complaint'          => 'boolean',
            //             'complaint_detail'       => 'required_if:has_complaint,true|nullable|string',
            //             'has_potential_order'    => 'boolean',
            //             'potential_order_detail' => 'required_if:has_potential_order,true|nullable|string',
            //             'follow_up_at'           => 'required|date|after:today',
            //             'follow_up_notes'        => 'nullable|string',
            //             'follow_up_type'        => 'required',
            //         ]);

            //         try {
            //             //  Cari visit
            //             $visit = VisitsModel::find($visitId);

            //             //  Kalau tidak ada → STOP disini
            //             if (!$visit) {
            //                 return response()->json([
            //                     'message' => 'Visit not found.'
            //                 ], 404);
            //             }

            //             //  Kalau status bukan CHECKED_IN → STOP
            //             if ($visit->visit_status !== 'CHECKED_IN') {
            //                 return response()->json([
            //                     'message' => 'Visit is not in CHECKED_IN status.'
            //                 ], 422);
            //             }


            //             DB::transaction(function () use ($request, $visit) {
            //             $userId = auth()->user()->id_user;

            //                     //  Update Visit
            //                     $visit->update([
            //                     'check_out_at'           => now(),
            //                     'notes'                  => $request->notes,
            //                     'customer_response'      => $request->customer_response,
            //                     'visit_result'           => $request->customer_response,
            //                     'has_complaint'          => $request->boolean('has_complaint'),
            //                     'complaint_detail'       => $request->boolean('has_complaint') ? $request->complaint_detail : null,
            //                     'has_potential_order'    => $request->boolean('has_potential_order'),
            //                     'potential_order_detail' => $request->boolean('has_potential_order') ? $request->potential_order_detail : null,
            //                     'visit_status'           => 'DONE',
            //                     ]);

            //                     //  Create Follow Up
            //                     $followUp = MsFollowUp::create([
            //                     'follow_up_code' => $this->generateFollowUpCode(),
            //                     'customer_id'    => $visit->customer_id,
            //                     'follow_up_type' => $request->input('follow_up_type', 'CALL'),
            //                     'subject'        => 'Follow up after visit Customer with code ' . $visit->visit_code,
            //                     'notes'          => $request->follow_up_notes,
            //                     'follow_up_at'   => $request->follow_up_at,
            //                     'status'         => 'PENDING',
            //                     'assigned_to'    => $visit->sales_id,
            //                     'created_by'     => $userId,
            //                     ]);

            //                     // Create Follow Up activity log 
            //                     DB::table('follow_up_activities')->insert([
            //                         'follow_up_id'  => $followUp->id,
            //                         'title'         => 'Follow Up Created Result Visit Customer',
            //                         'description'   => 'Follow up generated automatically after visit Customer '. $visit->visit_code,
            //                         'activity_type' => 'CREATE',
            //                         'scheduled_for' => $request->follow_up_at,
            //                         'activity_at'   => now(),
            //                         'created_at'    => now(),
            //                         'created_by'    => $userId,
            //                     ]);
            //                 });

            //             return response()->json([
            //                 'message' => 'Check out successful & follow up scheduled.',
            //                 'data'    => $visit->fresh()
            //             ]);

            //         } catch (\Throwable $e) {

            //             //  Kalau ada error lain (DB, dll)
            //             return response()->json([
            //                 'message' => 'Checkout failed.',
            //                 'error'   => $e->getMessage()
            //             ], 500);
            //         }
            //     }


            public function checkOutCustomer(Request $request, $visitId)
{
    $request->validate([
        'notes'                  => 'required|string',
        'customer_response'      => 'required|string',
        // 'has_complaint'          => 'boolean',
        // 'complaint_detail'       => 'required_if:has_complaint,true|nullable|string',
         'has_complaint'          => 'nullable|boolean',  // tambah nullable
        'complaint_detail'       => 'required_if:has_complaint,1|nullable|string',
        // 'has_potential_order'    => 'boolean',
        // 'potential_order_detail' => 'required_if:has_potential_order,true|nullable|string',
        'has_potential_order'    => 'nullable|boolean',
        'potential_order_detail' => 'required_if:has_potential_order,1|nullable|string',
        'follow_up_at'           => 'required|date|after:today',
        'follow_up_notes'        => 'nullable|string',
        'follow_up_type'         => 'required|string',
    ]);

    // dd([
    //     'has_potential_order'    => $request->has_potential_order,
    //     'has_potential_order_bool' => $request->boolean('has_potential_order'),
    //     'potential_order_detail' => $request->potential_order_detail,
    //     'all'              => $request->all()
    // ]);

//     dd([
//     'has_potential_order' => $request->boolean('has_potential_order'),
//     'potential_order_detail' => $request->boolean('has_potential_order') ? $request->potential_order_detail : null,
// ]);
   
// $visit = VisitsModel::find($visitId);
// dd([
//     'current_has_potential_order' => $visit->has_potential_order,
//     'current_potential_order_detail' => $visit->potential_order_detail,
//     'dirty' => $visit->getDirty(), // field yang akan diupdate
// ]);


    try {

        // Cari Visit
        $visit = VisitsModel::find($visitId);

        if (!$visit) {
            return response()->json([
                'message' => 'Visit not found.'
            ], 404);
        }

        // Pastikan status masih CHECKED_IN
        if ($visit->visit_status !== 'CHECKED_IN') {
            return response()->json([
                'message' => 'Visit is not in CHECKED_IN status.'
            ], 422);
        }

        DB::transaction(function () use ($request, $visit) {

            $userId = auth()->user()->id_user;

            /*
            |--------------------------------------------------------------------------
            | 1. UPDATE VISIT (CHECK OUT)
            |--------------------------------------------------------------------------
            */
            $visit->update([
                'check_out_at'           => now(),
                'notes'                  => $request->notes,
                'customer_response'      => $request->customer_response,
                'visit_result'           => $request->customer_response,
                'has_complaint'          => $request->boolean('has_complaint'),
                'complaint_detail'       => $request->boolean('has_complaint') ? $request->complaint_detail : null,
                'has_potential_order'    => $request->boolean('has_potential_order'),
                'potential_order_detail' => $request->boolean('has_potential_order') ? $request->potential_order_detail : null,
                'visit_status'           => 'DONE',
            ]);
            

            /*
            |--------------------------------------------------------------------------
            | 2. AUTO CLOSE FOLLOW UPS LAMA (YANG MASIH AKTIF)
            |--------------------------------------------------------------------------
            | Semua follow up sebelumnya dianggap sudah tidak relevan
            | karena sudah digantikan hasil visit terbaru.
            */
            $openFollowUps = MsFollowUp::where('customer_id', $visit->customer_id)
                ->whereIn('status', ['PENDING', 'OPEN'])
                ->lockForUpdate() // mencegah race condition
                ->get();

            foreach ($openFollowUps as $oldFollowUp) {

                $oldFollowUp->update([
                    'status'        => 'CLOSED',
                    'closed_at'     => now(),
                    'closed_reason' => 'AUTO_CLOSED_BY_VISIT'
                ]);

                // Log activity penutupan otomatis
                DB::table('follow_up_activities')->insert([
                    'follow_up_id'  => $oldFollowUp->id,
                    'title'         => 'Follow Up Auto Closed',
                    'description'   => 'Closed automatically because visit '
                                      . $visit->visit_code . ' has been completed.',
                    'activity_type' => 'AUTO_CLOSE',
                    'scheduled_for' => null,
                    'activity_at'   => now(),
                    'created_at'    => now(),
                    'created_by'    => $userId,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. CREATE FOLLOW UP BARU (NEXT ACTION)
            |--------------------------------------------------------------------------
            | Ini menjadi satu-satunya follow up aktif setelah visit selesai.
            */
            $followUp = MsFollowUp::create([
                'follow_up_code' => $this->generateFollowUpCode(),
                'customer_id'    => $visit->customer_id,
                'follow_up_type' => $request->follow_up_type,
                'subject'        => 'Follow up after visit Customer with code ' . $visit->visit_code,
                'notes'          => $request->follow_up_notes,
                'follow_up_at'   => $request->follow_up_at,
                'status'         => 'PENDING',
                'assigned_to'    => $visit->sales_id,
                'created_by'     => $userId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 4. CREATE ACTIVITY LOG FOLLOW UP BARU
            |--------------------------------------------------------------------------
            */
            DB::table('follow_up_activities')->insert([
                'follow_up_id'  => $followUp->id,
                'title'         => 'Follow Up Created Result Visit Customer',
                'description'   => 'Follow up generated automatically after visit Customer '
                                  . $visit->visit_code,
                'activity_type' => 'CREATE',
                'scheduled_for' => $request->follow_up_at,
                'activity_at'   => now(),
                'created_at'    => now(),
                'created_by'    => $userId,
            ]);
        });

        return response()->json([
            'message' => 'Check out successful & follow up scheduled.',
            'data'    => $visit->fresh()
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'message' => 'Checkout failed.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

        }
