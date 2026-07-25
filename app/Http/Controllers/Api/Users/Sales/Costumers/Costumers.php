<?php

namespace App\Http\Controllers\Api\Users\Sales\Costumers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CostumersValidationIndex;
use App\Http\Requests\CostumersValidationRequest;
use App\Http\Resources\CostumersResources;
use App\Http\Resources\CustomerBranchResource;
use App\Http\Resources\CustomerBranchResourceCollection;
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
           
           
//             public function customers(CostumersValidationIndex $request)
// {
//     $validated = $request->validated();

//     $search   = $validated['search'] ?? null;
//     $perPage  = $validated['per_page'] ?? 10;
//     $sortBy   = $validated['sort_by'] ?? 'c.created_at';
//     $sortDir  = $validated['sort_dir'] ?? 'desc';

//     $userId = auth()->user()->id_user;

//     /**
//      * ============================================
//      * SUB QUERY BRANCH
//      * ============================================
//      */
//     $branchSub = DB::table('customer_branches as cb')
//     ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'cb.created_by')
//     ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'cb.assigned_to')
//     ->select(
//         'cb.customer_id',
//         DB::raw('COUNT(*) as branch_count'),
//         DB::raw("
//             json_agg(
//                 json_build_object(
//                     'id', cb.id,
//                     'branch_code', cb.branch_code,
//                     'branch_name', cb.branch_name,
//                     'contact_name', cb.contact_name,
//                     'email', cb.email,
//                     'phone', cb.phone,
//                     'address', cb.address,
//                     'city', cb.city,

//                     'assigned_to', cb.assigned_to,
//                     'assigned_name', sales.fullname,

//                     'created_by', cb.created_by,
//                     'owner_name', owner.fullname,

//                     'status', cb.status,
//                     'approval_status', cb.approval_status,
//                     'approved_by', cb.approved_by,
//                     'approved_at', cb.approved_at
//                 )
//                 ORDER BY cb.is_main_branch DESC, cb.id ASC
//             ) as branches
//         ")
//     )
//     ->whereNull('cb.deleted_at')
//     ->where('cb.approval_status', 'approved')
//     ->groupBy('cb.customer_id');

//     /**
//      * ============================================
//      * CUSTOMER QUERY
//      * ============================================
//      */
//     $query = DB::table('customers as c')
//         ->select([
//             'c.id',
//             'c.customer_code',
//             'c.company_name',
//             'c.contact_name',
//             'c.email',
//             'c.phone',
//             'c.address',
//             'c.lead_id',
//             'c.lead_category_id',
//             'c.industry_id',
//             'c.assigned_to',
//             'c.created_by',
//             'c.customer_status',

//             'c.approval_status',
//             'c.approved_by',
//             'c.approved_at',
//             'c.approval_note',
//             'c.approval_revision',

//             'c.notes',
//             'c.converted_at',
//             'c.created_at',
//             'c.updated_at',
//             'c.deleted_at',

//             'l.company_name as lead_company_name',
//             'l.lead_status',
//             'c.lead_source',

//             'cat.name as category_name',
//             'ind.name as industry_name',

//             'owner.fullname as owner_name',
//             'sales.fullname as assigned_name',

//             DB::raw("COALESCE(cb.branch_count,0) as branch_count"),
//             DB::raw("COALESCE(cb.branches,'[]'::json) as branches"),
//         ])

//         ->leftJoin('leads as l', 'l.id', '=', 'c.lead_id')
//         ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
//         ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
//         ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.created_by')
//         ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')

//         ->leftJoinSub($branchSub, 'cb', function ($join) {
//             $join->on('cb.customer_id', '=', 'c.id');
//         })

//         ->where('c.approval_status', 'approved')

//         ->where(function ($q) use ($userId) {

//             /**
//              * Customer dibuat oleh saya
//              */
//             $q->where('c.created_by', $userId)

//             /**
//              * Customer diassign ke saya
//              */
//             ->orWhere('c.assigned_to', $userId)

//             /**
//              * Saya membuat salah satu branch customer
//              */
//             ->orWhereExists(function ($branch) use ($userId) {

//                 $branch->select(DB::raw(1))
//                     ->from('customer_branches as cb')
//                     ->whereColumn('cb.customer_id', 'c.id')
//                     ->whereNull('cb.deleted_at')
//                     ->where('cb.approval_status', 'approved')
//                     ->where('cb.created_by', $userId);

//             })

//             /**
//              * Branch customer diassign ke saya
//              */
//             ->orWhereExists(function ($branch) use ($userId) {

//                 $branch->select(DB::raw(1))
//                     ->from('customer_branches as cb')
//                     ->whereColumn('cb.customer_id', 'c.id')
//                     ->whereNull('cb.deleted_at')
//                     ->where('cb.approval_status', 'approved')
//                     ->where('cb.assigned_to', $userId);

//             });

//         });

//     /**
//      * SEARCH
//      */
//     if ($search) {
//         $query->where(function ($q) use ($search) {
//             $q->where('c.company_name', 'ILIKE', "%{$search}%")
//                 ->orWhere('c.contact_name', 'ILIKE', "%{$search}%")
//                 ->orWhere('c.email', 'ILIKE', "%{$search}%")
//                 ->orWhere('c.customer_code', 'ILIKE', "%{$search}%");
//         });
//     }

//     /**
//      * SORTING
//      */
//     $query->orderBy($sortBy, $sortDir);

    
//     $results = $query->paginate($perPage);
//     // dd($results->items());


//     $query->orderBy($sortBy, $sortDir);

// $results = $query->paginate($perPage);

// /**
//  * ============================================
//  * BUSINESS RULE
//  * ============================================
//  */
// $results->setCollection(

//     $results->getCollection()

//         ->map(function ($item) use ($userId) {

//             $branches = collect(
//                 is_string($item->branches)
//                     ? json_decode($item->branches, true)
//                     : ($item->branches ?? [])
//             );

//             // Branch yang dimiliki user login
//             $myBranches = $branches->filter(function ($branch) use ($userId) {
//                 return ($branch['created_by'] ?? null) == $userId
//                     || ($branch['assigned_to'] ?? null) == $userId;
//             })->values();

//             /**
//              * CUSTOMER OWNER
//              */
//             if (
//                 $item->created_by == $userId ||
//                 $item->assigned_to == $userId
//             ) {

//                 $item->display_type = 'customer';

//                 // owner customer melihat semua branch
//                 $item->branches = $branches;

//                 $item->branch_count = $branches->count();

//                 return $item;
//             }

//             /**
//              * BRANCH OWNER
//              */
//             if ($myBranches->isNotEmpty()) {

//                 $item->display_type = 'branch';

//                 // hanya branch milik user login
//                 $item->branches = $myBranches;

//                 $item->branch_count = $myBranches->count();

//                 return $item;
//             }

//             // tidak punya akses
//             return null;

//         })

//         ->filter()

//         ->values()

// );

//     return ApiResponse::paginate(
//         CostumersResourcesCollection::make($results),
//         $results->isEmpty()
//             ? 'Data customer not found'
//             : 'Success'
//     );
// }


public function customers(CostumersValidationIndex $request)
{
    $validated = $request->validated();

    $search   = $validated['search'] ?? null;
    $perPage  = $validated['per_page'] ?? 10;
    $sortBy   = $validated['sort_by'] ?? 'c.created_at';
    $sortDir  = $validated['sort_dir'] ?? 'desc';

    $userId = auth()->user()->id_user;

    /**
     * ============================================
     * SUB QUERY BRANCH
     * ----------------------------------------------
     * Fallback: kalau cb.assigned_to belum pernah
     * diisi (NULL), anggap cb.created_by sebagai
     * sales yang PEGANG cabang ini saat ini.
     * ============================================
     */
    $branchSub = DB::table('customer_branches as cb')
    ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'cb.created_by')
    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'cb.assigned_to')
    ->select(
        'cb.customer_id',
        DB::raw('COUNT(*) as branch_count'),
        DB::raw("
            json_agg(
                json_build_object(
                    'id', cb.id,
                    'branch_code', cb.branch_code,
                    'branch_name', cb.branch_name,
                    'contact_name', cb.contact_name,
                    'email', cb.email,
                    'phone', cb.phone,
                    'address', cb.address,
                    'city', cb.city,

                    'assigned_to', COALESCE(cb.assigned_to, cb.created_by),
                    'assigned_name', COALESCE(sales.fullname, owner.fullname),

                    'created_by', cb.created_by,
                    'owner_name', owner.fullname,

                    'status', cb.status,
                    'approval_status', cb.approval_status,
                    'approved_by', cb.approved_by,
                    'approved_at', cb.approved_at
                )
                ORDER BY cb.is_main_branch DESC, cb.id ASC
            ) as branches
        ")
    )
    ->whereNull('cb.deleted_at')
    ->where('cb.approval_status', 'approved')
    ->groupBy('cb.customer_id');

    /**
     * ============================================
     * CUSTOMER QUERY
     * ----------------------------------------------
     * Fallback yang sama untuk head company: kalau
     * c.assigned_to NULL, anggap c.created_by sebagai
     * sales saat ini.
     * ============================================
     */
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
            'c.created_by',
            'c.customer_status',

            'c.approval_status',
            'c.approved_by',
            'c.approved_at',
            'c.approval_note',
            'c.approval_revision',

            'c.notes',
            'c.converted_at',
            'c.created_at',
            'c.updated_at',
            'c.deleted_at',

            'l.company_name as lead_company_name',
            'l.lead_status',
            'c.lead_source',

            'cat.name as category_name',
            'ind.name as industry_name',

            'owner.fullname as owner_name',

            // ── FALLBACK: assigned_to/assigned_name sekarang
            //    otomatis jatuh ke created_by/owner_name kalau
            //    customer belum pernah di-assign manual ──
            DB::raw('COALESCE(c.assigned_to, c.created_by) as assigned_to'),
            DB::raw('COALESCE(sales.fullname, owner.fullname) as assigned_name'),

            DB::raw("COALESCE(cb.branch_count,0) as branch_count"),
            DB::raw("COALESCE(cb.branches,'[]'::json) as branches"),
        ])

        ->leftJoin('leads as l', 'l.id', '=', 'c.lead_id')
        ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
        ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
        ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.created_by')
        ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')

        ->leftJoinSub($branchSub, 'cb', function ($join) {
            $join->on('cb.customer_id', '=', 'c.id');
        })

        ->where('c.approval_status', 'approved')

        ->where(function ($q) use ($userId) {

            /**
             * Saya adalah sales EFEKTIF head company ini
             * (assigned_to kalau ada, kalau tidak fallback
             * ke created_by). Sales lama yang sudah dipindah
             * TIDAK lagi lolos di sini.
             */
            $q->whereRaw('COALESCE(c.assigned_to, c.created_by) = ?', [$userId])

            /**
             * Atau saya adalah sales EFEKTIF salah satu
             * branch-nya (independen dari head company).
             */
            ->orWhereExists(function ($branch) use ($userId) {

                $branch->select(DB::raw(1))
                    ->from('customer_branches as cb')
                    ->whereColumn('cb.customer_id', 'c.id')
                    ->whereNull('cb.deleted_at')
                    ->where('cb.approval_status', 'approved')
                    ->whereRaw('COALESCE(cb.assigned_to, cb.created_by) = ?', [$userId]);

            });

        });

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
     * SORTING
     */
    $query->orderBy($sortBy, $sortDir);

    $results = $query->paginate($perPage);

    /**
     * ============================================
     * BUSINESS RULE
     * ----------------------------------------------
     * Sama-sama pakai sales EFEKTIF (fallback ke
     * created_by), bukan created_by murni.
     * ============================================
     */
    $results->setCollection(

        $results->getCollection()

            ->map(function ($item) use ($userId) {

                $branches = collect(
                    is_string($item->branches)
                        ? json_decode($item->branches, true)
                        : ($item->branches ?? [])
                );

                $effectiveCustomerSales = $item->assigned_to; // sudah ter-fallback dari SELECT

                // Branch yang EFEKTIF dipegang user login
                $myBranches = $branches->filter(function ($branch) use ($userId) {
                    $effectiveBranchSales = $branch['assigned_to'] ?? null; // sudah ter-fallback dari branchSub
                    return $effectiveBranchSales == $userId;
                })->values();

                /**
                 * SAYA SALES EFEKTIF HEAD COMPANY
                 */
                if ($effectiveCustomerSales == $userId) {

                    $item->display_type = 'customer';

                    // owner customer melihat semua branch
                    $item->branches = $branches;

                    $item->branch_count = $branches->count();

                    return $item;
                }

                /**
                 * SAYA SALES EFEKTIF SALAH SATU BRANCH SAJA
                 */
                if ($myBranches->isNotEmpty()) {

                    $item->display_type = 'branch';

                    // hanya branch yang efektif milik user login
                    $item->branches = $myBranches;

                    $item->branch_count = $myBranches->count();

                    return $item;
                }

                // tidak punya akses efektif sama sekali
                return null;

            })

            ->filter()

            ->values()

    );

    return ApiResponse::paginate(
        CostumersResourcesCollection::make($results),
        $results->isEmpty()
            ? 'Data customer not found'
            : 'Success'
    );
}


            // ======================================================
            // CUSTOMER SUBMISSION
            // Pending + Rejected
            // ======================================================
           
            public function customerSubmission(CostumersValidationIndex $request)
{
    $validated = $request->validated();
 
    $search  = $validated['search'] ?? null;
    $perPage = $validated['per_page'] ?? 10;
 
    // Kolom hasil UNION tidak lagi pakai prefix tabel (c./b.),
    // jadi whitelist & default-nya disesuaikan tanpa prefix.
    $allowedSort = ['created_at', 'company_name'];
    $sortBy      = in_array($validated['sort_by'] ?? null, $allowedSort)
        ? $validated['sort_by']
        : 'created_at';
    $sortDir     = $validated['sort_dir'] ?? 'desc';
 
    $userId = auth()->user()->id_user;
 
    /**
     * ──────────────────────────────────────────────────────────
     * QUERY 1: SUBMISSION HEAD COMPANY (customers)
     * ──────────────────────────────────────────────────────────
     */
    $customerQuery = DB::table('customers as c')
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
 
            'c.approval_status',
            'c.approved_by',
            'c.approved_at',
            'c.approval_note',
            'c.approval_revision',
 
            'c.notes',
 
            'c.converted_at',
            'c.created_at',
            'c.updated_at',
 
            'l.company_name as lead_company_name',
            'c.lead_source',
            'l.lead_status',
 
            'cat.name as category_name',
            'ind.name as industry_name',
 
            'owner.fullname as owner_name',
            'sales.fullname as assigned_name',
 
            DB::raw("'customer' as display_type"),
            DB::raw('NULL::text as branches'),
            DB::raw('0 as branch_count'),
        ])
        ->leftJoin('leads as l', 'l.id', '=', 'c.lead_id')
        ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
        ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
        ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.created_by')
        ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')
        ->where(function ($q) use ($userId) {
            $q->where('c.created_by', $userId)
              ->orWhere('c.assigned_to', $userId);
        })
        ->whereIn('c.approval_status', ['pending', 'rejected']);
 
    if ($search) {
        $customerQuery->where(function ($q) use ($search) {
            $q->where('c.company_name', 'ILIKE', "%{$search}%")
              ->orWhere('c.contact_name', 'ILIKE', "%{$search}%")
              ->orWhere('c.email', 'ILIKE', "%{$search}%")
              ->orWhere('c.customer_code', 'ILIKE', "%{$search}%");
        });
    }
 
    /**
     * ──────────────────────────────────────────────────────────
     * QUERY 2: SUBMISSION CABANG (branches)
     * Join ke `customers` cuma untuk ambil identitas head company
     * (customer_code, company_name) sebagai konteks/parent.
     * ──────────────────────────────────────────────────────────
     */
    $branchQuery = DB::table('customer_branches as b')
        ->select([
            'c.id',
            'c.customer_code',
            'c.company_name',
            'b.contact_name',
            'b.email',
            'b.phone',
            'b.address',
 
            DB::raw('NULL::integer as lead_id'),
            DB::raw('NULL::integer as lead_category_id'),
            DB::raw('NULL::integer as industry_id'),
 
            'b.assigned_to',
            'b.created_by',
 
            'b.status as customer_status',
 
            'b.approval_status',
            'b.approved_by',
            'b.approved_at',
            'b.approval_note',
            'b.approval_revision',
 
            'b.notes',
 
            DB::raw('NULL::timestamp as converted_at'),
            'b.created_at',
            'b.updated_at',
 
            DB::raw('NULL::text as lead_company_name'),
            DB::raw('NULL::text as lead_source'),
            DB::raw('NULL::text as lead_status'),
 
            DB::raw('NULL::text as category_name'),
            DB::raw('NULL::text as industry_name'),
 
            'owner.fullname as owner_name',
            'sales.fullname as assigned_name',
 
            DB::raw("'branch' as display_type"),
 
            // Bungkus data branch jadi JSON array 1 item, formatnya
            // disamakan dengan yang dibaca CostumersResources untuk
            // display_type = 'branch'. creator_name & assigned_name
            // disertakan supaya frontend bisa langsung tampilkan
            // PIC cabang tanpa request tambahan.
            DB::raw("json_build_array(json_build_object(
                'id', b.id,
                'branch_code', b.branch_code,
                'branch_name', b.branch_name,
                'contact_name', b.contact_name,
                'email', b.email,
                'phone', b.phone,
                'address', b.address,
                'city', b.city,
                'status', b.status,
                'approval_status', b.approval_status,
                'assigned_to', b.assigned_to,
                'created_by', b.created_by,
                'approved_by', b.approved_by,
                'approved_at', b.approved_at,
                'creator_name', owner.fullname,
                'assigned_name', sales.fullname
            ))::text as branches"),
 
            DB::raw('0 as branch_count'),
        ])
        ->join('customers as c', 'c.id', '=', 'b.customer_id')
        ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'b.created_by')
        ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'b.assigned_to')
        ->where(function ($q) use ($userId) {
            $q->where('b.created_by', $userId)
              ->orWhere('b.assigned_to', $userId);
        })
        ->whereIn('b.approval_status', ['pending', 'rejected']);
 
    if ($search) {
        $branchQuery->where(function ($q) use ($search) {
            $q->where('c.company_name', 'ILIKE', "%{$search}%")
              ->orWhere('b.branch_name', 'ILIKE', "%{$search}%")
              ->orWhere('b.contact_name', 'ILIKE', "%{$search}%")
              ->orWhere('b.email', 'ILIKE', "%{$search}%")
              ->orWhere('c.customer_code', 'ILIKE', "%{$search}%");
        });
    }
 
    /**
     * ──────────────────────────────────────────────────────────
     * GABUNGKAN + SORT + PAGINATE
     * ──────────────────────────────────────────────────────────
     */
    $results = $customerQuery
        ->unionAll($branchQuery)
        ->orderBy($sortBy, $sortDir)
        ->paginate($perPage);
 
    return ApiResponse::paginate(
        CostumersResourcesCollection::make($results),
        $results->isEmpty()
            ? 'Customer submission not found'
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
        $user   = auth()->user();
        $userId = $user->id_user;
        $data   = $request->validated();


         // ── CEK DUPLIKAT COMPANY NAME (case-insensitive, exact match) ──
        $existing = DB::table('customers')
            ->whereNull('deleted_at')
            ->where('approval_status', 'approved') // atau sesuaikan, mungkin pending juga perlu dicek
            ->whereRaw('LOWER(company_name) = ?', [strtolower(trim($data['company_name']))])
            ->select('id', 'customer_code', 'company_name')
            ->first();

        if ($existing) {
            return ApiResponse::error(
                'Company name sudah terdaftar. Silakan tambahkan sebagai cabang dari perusahaan yang sudah ada.',
                [
                    'company_name' => ["Perusahaan \"{$existing->company_name}\" ({$existing->customer_code}) sudah terdaftar."],
                ],
                422
            );
        }

        // pastikan ada minimal 1 kontak, dan tentukan primary
        $contacts = collect($data['contacts'] ?? []);

        if ($contacts->isEmpty()) {
            return ApiResponse::error('Minimal 1 kontak harus diisi.', [], 422);
        }

        // kalau tidak ada yg ditandai primary, paksa kontak pertama jadi primary
        if ($contacts->where('is_primary', true)->isEmpty()) {
            $contacts[0]['is_primary'] = true;
        }

        $primary = $contacts->firstWhere('is_primary', true);

        $customerId = DB::transaction(function () use ($data, $contacts, $primary, $customerCode, $userId) {

            $customerId = DB::table('customers')->insertGetId([
                'customer_code'    => $customerCode,
                'company_name'     => $data['company_name'],

                // sinkron dari kontak primary (backward compat)
                'contact_name'     => $primary['name'],
                'email'            => $primary['email'] ?? null,
                'phone'            => $primary['phone'] ?? null,

                'industry_id'      => $data['industry_id'] ?? null,
                'lead_category_id' => $data['lead_category_id'] ?? null,
                'assigned_to'      => $data['assigned_to'] ?? null,
                'customer_status'  => 'Active',
                'lead_source'      => $data['lead_source'] ?? null,
                'id_user'          => $userId,
                'created_by'       => $userId,
                'visibility_type'  => $data['visibility_type'] ?? 'PRIVATE',
                'notes'            => $data['notes'] ?? null,
                'address'          => $data['address'] ?? null,
                'converted_at'     => now(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // insert semua kontak
            foreach ($contacts as $contact) {
                DB::table('customer_contacts')->insert([
                    'customer_id' => $customerId,
                    'name'        => $contact['name'],
                    'position'    => $contact['position'] ?? null,
                    'email'       => $contact['email'] ?? null,
                    'phone'       => $contact['phone'] ?? null,
                    'is_primary'  => $contact['is_primary'] ?? false,
                    'status'      => 'Active',
                    'created_by'  => $userId,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            return $customerId;
        });

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

        $customer->contacts = DB::table('customer_contacts')
            ->where('customer_id', $customerId)
            ->whereNull('deleted_at')
            ->orderByDesc('is_primary')
            ->get();

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



 //  ini kode lama dengan desain tabel lama 
    // public function updateCostumers(CostumersValidationRequest $request, $id)
    //     {
    //         try {
    //             $user = auth()->user();
    //             $userId = $user->id_user;
    //             $data = $request->validated();

    //             // Cek apakah customer ada
    //             $customer = DB::table('customers')->where('id', $id)->first();
    //             if (!$customer) {
    //                 return ApiResponse::error('Customer not found.', [], 404);
    //             }

    //             // Optional: cek assigned_to valid
    //             if (!empty($data['assigned_to'])) {
    //                 $exists = DB::table('ms_users')->where('id_user', $data['assigned_to'])->exists();
    //                 if (!$exists) {
    //                     $data['assigned_to'] = null; // atau throw error jika wajib
    //                 }
    //             }

    //             // Update customer
    //             DB::table('customers')->where('id', $id)->update([
    //                 'company_name'     => $data['company_name'],
    //                 'contact_name'     => $data['contact_name'],
    //                 'email'            => $data['email'] ?? null,
    //                 'phone'            => $data['phone'] ?? null,
    //                 'industry_id'      => $data['industry_id'] ?? null,
    //                 'lead_category_id' => $data['lead_category_id'] ?? null,
    //                 // 'assigned_to'      => $data['assigned_to'] ?? null,
    //                 'lead_source'      => $data['lead_source'] ?? null,
    //                 'visibility_type'  => $data['visibility_type'] ?? 'PRIVATE',
    //                 'customer_status'  => $data['customer_status'] ?? 'Active',
    //                 'notes'            => $data['notes'] ?? null,
    //                 'address'          => $data['address'] ?? null,
    //                 'updated_at'       => now(),
    //             ]);

    //             // Ambil data customer yang diupdate
    //             $customer = DB::table('customers as c')
    //                 ->select([
    //                     'c.*',
    //                     'cat.name as category_name',
    //                     'ind.name as industry_name',
    //                     'owner.fullname as owner_name',
    //                     'sales.fullname as assigned_name',
    //                 ])
    //                 ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
    //                 ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
    //                 ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.id_user')
    //                 ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')
    //                 ->where('c.id', $id)
    //                 ->first();

    //             return ApiResponse::success($customer, 'Success Update Customer', 200);

    //         } catch (\Illuminate\Database\QueryException $e) {
    //             return ApiResponse::error('Failed to update customer (query error)', [
    //                 'exception' => config('app.debug') ? $e->getMessage() : null
    //             ], 422);
    //         } catch (\Exception $e) {
    //             return ApiResponse::error('An error occurred while updating the customer.', [
    //                 'exception' => config('app.debug') ? $e->getMessage() : null
    //             ], 500);
    //         }
    //     }


    public function updateCostumers(CostumersValidationRequest $request, $id)
{
    try {
        $user   = auth()->user();
        $userId = $user->id_user;
        $data   = $request->validated();

        $customer = DB::table('customers')->where('id', $id)->first();
        if (!$customer) {
            return ApiResponse::error('Customer not found.', [], 404);
        }

          // ── CEK DUPLIKAT COMPANY NAME (exclude diri sendiri) ──
        $normalizedName = strtolower(trim(preg_replace('/\s+/', ' ', $data['company_name'])));

        $existing = DB::table('customers')
            ->where('id', '!=', $id)
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(TRIM(company_name)) = ?', [$normalizedName])
            ->select('id', 'customer_code', 'company_name')
            ->first();

        if ($existing) {
            return ApiResponse::error(
                'Company name sudah terdaftar.',
                [
                    'company_name' => ["Perusahaan \"{$existing->company_name}\" ({$existing->customer_code}) sudah terdaftar."],
                ],
                422
            );
        }

        $contacts = collect($data['contacts'] ?? []);

        if ($contacts->isEmpty()) {
            return ApiResponse::error('Minimal 1 kontak harus diisi.', [], 422);
        }

        if ($contacts->where('is_primary', true)->isEmpty()) {
            $contacts[0]['is_primary'] = true;
        }

        $primary = $contacts->firstWhere('is_primary', true);

        DB::transaction(function () use ($id, $data, $contacts, $primary, $userId) {

            DB::table('customers')->where('id', $id)->update([
                'company_name'     => $data['company_name'],
                'contact_name'     => $primary['name'],
                'email'            => $primary['email'] ?? null,
                'phone'            => $primary['phone'] ?? null,
                'industry_id'      => $data['industry_id'] ?? null,
                'lead_category_id' => $data['lead_category_id'] ?? null,
                'lead_source'      => $data['lead_source'] ?? null,
                'visibility_type'  => $data['visibility_type'] ?? 'PRIVATE',
                'customer_status'  => $data['customer_status'] ?? 'Active',
                'notes'            => $data['notes'] ?? null,
                'address'          => $data['address'] ?? null,
                'updated_at'       => now(),
            ]);

            // hapus kontak yang tidak dikirim lagi (soft delete)
            $incomingIds = $contacts->pluck('id')->filter()->all();

            DB::table('customer_contacts')
                ->where('customer_id', $id)
                ->when(!empty($incomingIds), fn($q) => $q->whereNotIn('id', $incomingIds))
                ->update(['deleted_at' => now()]);

            foreach ($contacts as $contact) {
                if (!empty($contact['id'])) {
                    // update kontak lama
                    DB::table('customer_contacts')
                        ->where('id', $contact['id'])
                        ->update([
                            'name'       => $contact['name'],
                            'position'   => $contact['position'] ?? null,
                            'email'      => $contact['email'] ?? null,
                            'phone'      => $contact['phone'] ?? null,
                            'is_primary' => $contact['is_primary'] ?? false,
                            'updated_at' => now(),
                        ]);
                } else {
                    // kontak baru
                    DB::table('customer_contacts')->insert([
                        'customer_id' => $id,
                        'name'        => $contact['name'],
                        'position'    => $contact['position'] ?? null,
                        'email'       => $contact['email'] ?? null,
                        'phone'       => $contact['phone'] ?? null,
                        'is_primary'  => $contact['is_primary'] ?? false,
                        'status'      => 'Active',
                        'created_by'  => $userId,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        });

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

        $customer->contacts = DB::table('customer_contacts')
            ->where('customer_id', $id)
            ->whereNull('deleted_at')
            ->orderByDesc('is_primary')
            ->get();

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

        $userId = auth()->user()->id_user;

        /**
         * ======================================================
         * CUSTOMER
         * ======================================================
         */
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

            // owner customer
            ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.created_by')

            // sales customer
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')

            ->where('c.id', $id)
            ->where('c.approval_status', 'approved')
            ->first();

        if (!$customer) {
            return ApiResponse::error(
                'Customer not found.',
                [],
                404
            );
        }

        /**
         * ======================================================
         * CONTACTS (customer_contacts)
         * ======================================================
         */
        $customer->contacts = DB::table('customer_contacts')
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        /**
         * ======================================================
         * BRANCH CUSTOMER
         * ======================================================
         */
        $branches = DB::table('customer_branches as cb')
            ->select([
                'cb.*',

                'creator.fullname as creator_name',
                'sales.fullname as assigned_name',
            ])

            ->leftJoin(
                'ms_users as creator',
                'creator.id_user',
                '=',
                'cb.created_by'
            )

            ->leftJoin(
                'ms_users as sales',
                'sales.id_user',
                '=',
                'cb.assigned_to'
            )

            ->where('cb.customer_id', $customer->id)
            ->whereNull('cb.deleted_at')
            ->where('cb.approval_status', 'approved')
            ->orderByDesc('cb.is_main_branch')
            ->orderBy('cb.id')
            ->get();

        /**
         * ======================================================
         * BRANCH CONTACTS (branch_contacts)
         * Diambil sekaligus untuk semua branch, lalu dikelompokkan
         * per branch_id supaya tidak N+1 query.
         * ======================================================
         */
        $branchIds = $branches->pluck('id');

        $branchContacts = DB::table('branch_contacts')
            ->whereIn('branch_id', $branchIds)
            ->whereNull('deleted_at')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->groupBy('branch_id');

        $branches = $branches->map(function ($branch) use ($branchContacts) {
            $branch->contacts = $branchContacts->get($branch->id, collect())->values();
            return $branch;
        });

        /**
         * ======================================================
         * CUSTOMER OWNER ?
         * ======================================================
         */
        $isCustomerOwner =
            $customer->created_by == $userId ||
            $customer->assigned_to == $userId;

        /**
         * ======================================================
         * OWNER CUSTOMER
         * Lihat semua branch
         * ======================================================
         */
        if ($isCustomerOwner) {

            $customer->branches = $branches->values();

            $customer->branch_count = $branches->count();
        }

        /**
         * ======================================================
         * OWNER BRANCH
         * Hanya lihat branch miliknya
         * ======================================================
         */
        else {

            $myBranches = $branches
                ->filter(function ($branch) use ($userId) {

                    return
                        $branch->created_by == $userId ||
                        $branch->assigned_to == $userId;

                })
                ->values();

            if ($myBranches->isEmpty()) {

                return ApiResponse::error(
                    'Unauthorized',
                    [],
                    403
                );
            }

            $customer->branches = $myBranches;

            $customer->branch_count = $myBranches->count();
        }

        /**
         * ======================================================
         * RESPONSE
         * ======================================================
         */
        return ApiResponse::success(
            $customer,
            'Customer detail retrieved successfully.',
            200
        );

    } catch (\Illuminate\Database\QueryException $e) {

        return ApiResponse::error(
            'Failed to fetch customer detail (query error)',
            [
                'exception' => config('app.debug')
                    ? $e->getMessage()
                    : null
            ],
            422
        );

    } catch (\Exception $e) {

        return ApiResponse::error(
            'An error occurred while fetching customer detail.',
            [
                'exception' => config('app.debug')
                    ? $e->getMessage()
                    : null
            ],
            500
        );

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


public function branches($id)
{
    $customer = DB::table('customers')->where('id', $id)->first();

    if (! $customer) {
        return ApiResponse::error('Customer not found', 404);
    }

    $branches = DB::table('customer_branches as cb')
        ->select([
            'cb.id',
            'cb.customer_id',
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

            'cb.notes',
            'cb.created_at',
            'cb.updated_at',
        ])
        ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'cb.assigned_to')
        ->leftJoin('ms_users as creator', 'creator.id_user', '=', 'cb.created_by')
        ->where('cb.customer_id', $id)
        ->whereNull('cb.deleted_at')
        ->orderByDesc('cb.is_main_branch')
        ->orderBy('cb.branch_name')
        ->get();

    // ── ambil semua kontak sekaligus, kelompokkan per branch_id ──
    $branchIds = $branches->pluck('id');

    $branchContacts = DB::table('branch_contacts')
        ->whereIn('branch_id', $branchIds)
        ->whereNull('deleted_at')
        ->orderByDesc('is_primary')
        ->get()
        ->groupBy('branch_id');

    $branches = $branches->map(function ($branch) use ($branchContacts) {
        $branch->contacts = $branchContacts->get($branch->id, collect())->values();
        return $branch;
    });

    return ApiResponse::success(
        CustomerBranchResourceCollection::make($branches),
        $branches->isEmpty() ? 'Belum ada cabang' : 'Success'
    );
}



public function searchCompany(Request $request)
{
    try {

        $search = trim($request->search);

        if (strlen($search) < 2) {
            return ApiResponse::success([], 'Keyword terlalu pendek');
        }

        $customers = DB::table('customers as c')
            ->select([
                'c.id',
                'c.customer_code',
                'c.company_name',

                DB::raw("
                    (
                        SELECT COUNT(*)
                        FROM customer_branches cb
                        WHERE cb.customer_id = c.id
                        AND cb.deleted_at IS NULL
                    ) as branch_count
                "),
            ])
            ->whereNull('c.deleted_at')
            ->where('c.approval_status', 'approved')
            ->where('c.company_name', 'ILIKE', "%{$search}%")
            ->orderBy('c.company_name')
            ->limit(10)
            ->get();

        return ApiResponse::success(
            $customers,
            $customers->isEmpty()
                ? 'Company not found'
                : 'Success'
        );

    } catch (\Throwable $e) {

        return ApiResponse::error(
            'Failed search company',
            config('app.debug')
                ? ['exception' => $e->getMessage()]
                : null,
            500
        );
    }
}


public function storeBranch(Request $request, $id)
{
    try {
        $user   = auth()->user();
        $userId = $user->id_user;

        // Pastikan customer induk ada
        $customer = DB::table('customers')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (! $customer) {
            return ApiResponse::error('Customer not found', [], 404);
        }

        // Validasi input
        // $data = $request->validate([
        //     'branch_name'  => 'required|string|max:255',
        //     'city'         => 'nullable|string|max:255',
        //     'address'      => 'nullable|string',
        //     'notes'        => 'nullable|string',

        //     // ── CONTACTS (bisa banyak) ──
        //     'contacts'                  => 'required|array|min:1',
        //     'contacts.*.id'             => 'nullable|integer|exists:branch_contacts,id',
        //     'contacts.*.name'           => 'required|string|max:100',
        //     'contacts.*.position'       => 'nullable|string|max:100',
        //     'contacts.*.email'          => 'nullable|email|max:100',
        //     // 'contacts.*.phone'          => 'nullable|string|max:20',
        //     'contacts.*.phone'          => ['nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
        //     'contacts.*.is_primary'     => 'nullable|boolean',
        // ]);
        $data = $request->validate([
                'branch_name'  => 'required|string|max:255',
                'city'         => 'nullable|string|max:255',
                'address'      => 'nullable|string',
                'notes'        => 'nullable|string',

                // ── CONTACTS (bisa banyak) ──
                'contacts'                  => 'required|array|min:1',
                'contacts.*.id'             => 'nullable|integer|exists:branch_contacts,id',
                'contacts.*.name'           => 'required|string|max:100',
                'contacts.*.position'       => 'nullable|string|max:100',
                'contacts.*.email'          => 'nullable|email|max:100',
                'contacts.*.phone'          => ['nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
                'contacts.*.is_primary'     => 'nullable|boolean',
            ], [
                'contacts.*.phone.regex' => 'Format nomor telepon tidak valid. Gunakan 08xx, +628xx, atau 628xx.',
            ]);



        // ── CEK DUPLIKAT BRANCH_NAME (unik PER CUSTOMER, case-insensitive) ──
        $normalizedBranchName = strtolower(trim(preg_replace('/\s+/', ' ', $data['branch_name'])));

        $existingBranch = DB::table('customer_branches')
            ->where('customer_id', $id)
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(TRIM(branch_name)) = ?', [$normalizedBranchName])
            ->select('id', 'branch_code', 'branch_name')
            ->first();

        if ($existingBranch) {
            return ApiResponse::error(
                'Nama cabang sudah digunakan.',
                [
                    'branch_name' => ["Cabang \"{$existingBranch->branch_name}\" ({$existingBranch->branch_code}) sudah ada untuk customer ini."],
                ],
                422
            );
        }

        $contacts = collect($data['contacts']);

        if ($contacts->where('is_primary', true)->isEmpty()) {
            $contacts[0]['is_primary'] = true;
        }

        $primary = $contacts->firstWhere('is_primary', true);

        // Generate branch_code, contoh: BR-CUST-20260716-004-001
        $countBranch = DB::table('customer_branches')
            ->where('customer_id', $id)
            ->count();
        $branchCode = 'BR-' . $customer->customer_code . '-' . str_pad($countBranch + 1, 3, '0', STR_PAD_LEFT);

        $branchId = DB::transaction(function () use ($id, $data, $contacts, $primary, $customer, $userId, $branchCode) {

            $branchId = DB::table('customer_branches')->insertGetId([
                'customer_id'    => $id,
                'branch_code'    => $branchCode,
                'branch_name'    => $data['branch_name'],
                'is_main_branch' => false,
                'status'         => 'Active',
                'address'        => $data['address'] ?? null,
                'city'           => $data['city'] ?? null,

                // sinkron dari kontak primary (backward compat)
                'contact_name'   => $primary['name'],
                'email'          => $primary['email'] ?? null,
                'phone'          => $primary['phone'] ?? null,

                'assigned_to'    => $customer->assigned_to ?? null,
                'created_by'     => $userId,
                'notes'          => $data['notes'] ?? null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($contacts as $contact) {
                DB::table('branch_contacts')->insert([
                    'branch_id'  => $branchId,
                    'name'       => $contact['name'],
                    'position'   => $contact['position'] ?? null,
                    'email'      => $contact['email'] ?? null,
                    'phone'      => $contact['phone'] ?? null,
                    'is_primary' => $contact['is_primary'] ?? false,
                    'status'     => 'Active',
                    'created_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $branchId;
        });

        $branch = DB::table('customer_branches as cb')
            ->select([
                'cb.*',
                'sales.fullname as assigned_name',
                'creator.fullname as created_by_name',
            ])
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'cb.assigned_to')
            ->leftJoin('ms_users as creator', 'creator.id_user', '=', 'cb.created_by')
            ->where('cb.id', $branchId)
            ->first();

        $branch->contacts = DB::table('branch_contacts')
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->orderByDesc('is_primary')
            ->get();

        return ApiResponse::success(
            CustomerBranchResource::make($branch),
            'Success Add New Branch',
            201
        );

    } catch (\Illuminate\Validation\ValidationException $e) {
        return ApiResponse::error('Validasi gagal', $e->errors(), 422);
    } catch (\Throwable $e) {
        return ApiResponse::error('Failed to add branch', [
            'exception' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}


        //ini untuk timeline follow up customer tampil di customer detail (implementasi saat nanti semua sudah siap)
        // public function customerFollowUpTimeline(Request $request, $customerId)
        // {
        //     $userId = auth()->user()->id_user;

        //     try {
        //         /* ================= CEK AKSES CUSTOMER ================= */
        //         $customer = DB::table('customers')
        //             ->where('id', $customerId)
        //             ->where(function ($q) use ($userId) {
        //                 $q->where('created_by', $userId)
        //                 ->orWhere('assigned_to', $userId);
        //             })
        //             ->whereNull('deleted_at')
        //             ->first();

        //         if (!$customer) {
        //             return ApiResponse::error(
        //                 'Customer not found or access denied',
        //                 null,
        //                 404
        //             );
        //         }

        //         /* ================= TIMELINE FOLLOW UP ================= */
        //         $timeline = DB::table('follow_ups as fu')
        //             ->select([
        //                 'fu.id',
        //                 'fu.follow_up_at',
        //                 'fu.follow_up_type',
        //                 'fu.notes',
        //                 'fu.created_at',

        //                 'sales.fullname as sales_name',
        //             ])
        //             ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'fu.created_by')
        //             ->where('fu.customer_id', $customerId)
        //             ->whereNull('fu.deleted_at')
        //             ->orderBy('fu.follow_up_at', 'desc')
        //             ->get();

        //         return ApiResponse::success(
        //             $timeline,
        //             'Success Get Customer Follow Up Timeline'
        //         );

        //     } catch (\Throwable $e) {
        //         return ApiResponse::error(
        //             'Failed to get follow up timeline',
        //             config('app.debug') ? ['exception' => $e->getMessage()] : null,
        //             500
        //         );
        //     }
        // }

public function customerFollowUpTimeline(Request $request, $customerId)
{
    $userId = auth()->user()->id_user;

    try {
        /* ================= CEK AKSES CUSTOMER (pakai sales efektif) ================= */
        $customer = DB::table('customers')
            ->where('id', $customerId)
            ->whereRaw('COALESCE(assigned_to, created_by) = ?', [$userId])
            ->whereNull('deleted_at')
            ->first();

        if (!$customer) {
            return ApiResponse::error(
                'Customer not found or access denied',
                null,
                404
            );
        }

        /* ================= TIMELINE FOLLOW UP (tidak berubah) ================= */
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

public function updateBranch(Request $request, $id)
{
    try {
        $user   = auth()->user();
        $userId = $user->id_user;

        // $data = $request->validate([
        //     'branch_name'  => 'required|string|max:255',
        //     'city'         => 'nullable|string|max:255',
        //     'address'      => 'nullable|string',
        //     'notes'        => 'nullable|string',

        //     'contacts'                  => 'required|array|min:1',
        //     'contacts.*.id'             => 'nullable|integer|exists:branch_contacts,id',
        //     'contacts.*.name'           => 'required|string|max:100',
        //     'contacts.*.position'       => 'nullable|string|max:100',
        //     'contacts.*.email'          => 'nullable|email|max:100',
        //     'contacts.*.phone'          => 'nullable|string|max:20',
        //     'contacts.*.is_primary'     => 'nullable|boolean',
        // ]);
        $data = $request->validate([
                'branch_name'  => 'required|string|max:255',
                'city'         => 'nullable|string|max:255',
                'address'      => 'nullable|string',
                'notes'        => 'nullable|string',

                'contacts'                  => 'required|array|min:1',
                'contacts.*.id'             => 'nullable|integer|exists:branch_contacts,id',
                'contacts.*.name'           => 'required|string|max:100',
                'contacts.*.position'       => 'nullable|string|max:100',
                'contacts.*.email'          => 'nullable|email|max:100',
                'contacts.*.phone'          => ['nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
                'contacts.*.is_primary'     => 'nullable|boolean',
            ], [
                'contacts.*.phone.regex' => 'Format nomor telepon tidak valid. Gunakan 08xx, +628xx, atau 628xx.',
            ]);

        $branch = DB::table('customer_branches')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (! $branch) {
            return ApiResponse::error('Branch not found', [], 404);
        }

         // ── CEK DUPLIKAT BRANCH_NAME (unik PER CUSTOMER, exclude diri sendiri) ──
        $normalizedBranchName = strtolower(trim(preg_replace('/\s+/', ' ', $data['branch_name'])));

        $existingBranch = DB::table('customer_branches')
            ->where('customer_id', $branch->customer_id)
            ->where('id', '!=', $id)
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(TRIM(branch_name)) = ?', [$normalizedBranchName])
            ->select('id', 'branch_code', 'branch_name')
            ->first();

        if ($existingBranch) {
            return ApiResponse::error(
                'Nama cabang sudah digunakan.',
                [
                    'branch_name' => ["Cabang \"{$existingBranch->branch_name}\" ({$existingBranch->branch_code}) sudah ada untuk customer ini."],
                ],
                422
            );
        }

        $contacts = collect($data['contacts']);

        if ($contacts->where('is_primary', true)->isEmpty()) {
            $contacts[0]['is_primary'] = true;
        }

        $primary = $contacts->firstWhere('is_primary', true);

        DB::transaction(function () use ($id, $data, $contacts, $primary, $userId) {

            DB::table('customer_branches')
                ->where('id', $id)
                ->update([
                    'branch_name'  => $data['branch_name'],
                    'city'         => $data['city'] ?? null,
                    'address'      => $data['address'] ?? null,

                    // sinkron dari kontak primary (backward compat)
                    'contact_name' => $primary['name'],
                    'email'        => $primary['email'] ?? null,
                    'phone'        => $primary['phone'] ?? null,

                    'notes'        => $data['notes'] ?? null,
                    'updated_at'   => now(),
                ]);

            // hapus kontak yang tidak dikirim lagi (soft delete)
            $incomingIds = $contacts->pluck('id')->filter()->all();

            DB::table('branch_contacts')
                ->where('branch_id', $id)
                ->when(!empty($incomingIds), fn($q) => $q->whereNotIn('id', $incomingIds))
                ->update(['deleted_at' => now()]);

            foreach ($contacts as $contact) {
                if (!empty($contact['id'])) {
                    DB::table('branch_contacts')
                        ->where('id', $contact['id'])
                        ->update([
                            'name'       => $contact['name'],
                            'position'   => $contact['position'] ?? null,
                            'email'      => $contact['email'] ?? null,
                            'phone'      => $contact['phone'] ?? null,
                            'is_primary' => $contact['is_primary'] ?? false,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('branch_contacts')->insert([
                        'branch_id'  => $id,
                        'name'       => $contact['name'],
                        'position'   => $contact['position'] ?? null,
                        'email'      => $contact['email'] ?? null,
                        'phone'      => $contact['phone'] ?? null,
                        'is_primary' => $contact['is_primary'] ?? false,
                        'status'     => 'Active',
                        'created_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $branch = DB::table('customer_branches as cb')
            ->select([
                'cb.*',
                'sales.fullname as assigned_name',
                'creator.fullname as created_by_name',
            ])
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'cb.assigned_to')
            ->leftJoin('ms_users as creator', 'creator.id_user', '=', 'cb.created_by')
            ->where('cb.id', $id)
            ->first();

        $branch->contacts = DB::table('branch_contacts')
            ->where('branch_id', $id)
            ->whereNull('deleted_at')
            ->orderByDesc('is_primary')
            ->get();

        return ApiResponse::success(
            CustomerBranchResource::make($branch),
            'Success Update Branch'
        );

    } catch (\Illuminate\Validation\ValidationException $e) {
        return ApiResponse::error('Validation failed', $e->errors(), 422);
    } catch (\Throwable $e) {
        return ApiResponse::error('Failed update branch', [
            'exception' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}


// kode lama ini  dengan desain db lama
// public function destroyBranch($id)
// {
//     try {

//         $branch = DB::table('customer_branches')
//             ->where('id', $id)
//             ->whereNull('deleted_at')
//             ->first();

//         if (! $branch) {

//             return ApiResponse::error(
//                 'Branch not found',
//                 [],
//                 404
//             );
//         }

//         DB::table('customer_branches')
//             ->where('id', $id)
//             ->update([
//                 'deleted_at' => now(),
//                 'updated_at' => now(),
//             ]);

//         return ApiResponse::success(
//             null,
//             'Branch deleted successfully.'
//         );

//     } catch (\Throwable $e) {

//         return ApiResponse::error(
//             'Failed delete branch',
//             config('app.debug')
//                 ? ['exception' => $e->getMessage()]
//                 : null,
//             500
//         );

//     }
// }


public function destroyBranch($id)
{
    try {

        $branch = DB::table('customer_branches')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (! $branch) {
            return ApiResponse::error('Branch not found', [], 404);
        }

        DB::transaction(function () use ($id) {

            DB::table('customer_branches')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            // ikut soft-delete semua kontak branch ini
            DB::table('branch_contacts')
                ->where('branch_id', $id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
        });

        return ApiResponse::success(
            null,
            'Branch deleted successfully.'
        );

    } catch (\Throwable $e) {
        return ApiResponse::error('Failed delete branch', [
            'exception' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

}
