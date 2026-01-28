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

class Visits extends Controller
{
    

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
                ])
                ->leftJoin('lead_categories as cat', 'cat.id', '=', 'l.lead_category_id')
                ->leftJoin('lead_industries as ind', 'ind.id', '=', 'l.industry_id')
                ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'l.id_user')
                ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'l.assigned_to')
                ->whereIn('l.lead_status', ['New', 'Contacted', 'Qualified'])
                ->where(function ($q) use ($userId) {
                    $q->where('l.created_by', $userId)
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





//        public function VisitCustomers(VisitCustomerDataIndex $request)
// {
//     $validated = $request->validated();

//     $search  = $validated['search'] ?? null;
//     $perPage = (int) ($validated['per_page'] ?? 10);
//     $page    = (int) ($validated['page'] ?? 1);

//     /**
//      * Whitelist kolom sorting
//      */
//     $allowedSort = [
//         'company_name' => 'c.company_name',
//         'created_at'   => 'c.created_at',
//         'converted_at' => 'c.converted_at',
//     ];

//     $sortByKey = $validated['sort_by'] ?? 'created_at';
//     $sortBy    = $allowedSort[$sortByKey] ?? 'c.created_at';

//     $sortDirInput = $validated['sort_dir'] ?? 'desc';
//     $sortDir = in_array($sortDirInput, ['asc', 'desc']) ? $sortDirInput : 'desc';

//     $userId = auth()->user()->id_user;

//     $query = DB::table('customers as c')
//         ->select([
//             'c.id',
//             'c.customer_code',
//             'c.company_name',
//             'c.contact_name',
//             'c.email',
//             'c.phone',
//             'c.lead_id',
//             'c.lead_category_id',
//             'c.industry_id',
//             'c.id_user',
//             'c.assigned_to',
//             'c.created_by',
//             'c.lead_source',
//             'c.customer_status',
//             'c.visibility_type',
//             'c.notes',
//             'c.address',
//             'c.converted_at',
//             'c.created_at',
//             'c.updated_at',
//             'cat.name as category_name',
//             'ind.name as industry_name',
//             'owner.fullname as owner_name',
//             'sales.fullname as assigned_name',
//         ])
//         ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
//         ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
//         ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.id_user')
//         ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')
//         ->whereNull('c.deleted_at')
//         ->where(function ($q) use ($userId) {
//             $q->where('c.created_by', $userId)
//               ->orWhere('c.assigned_to', $userId);
//         });

//     /**
//      * FILTER STATUS CUSTOMER (opsional)
//      */
//     $query->whereIn('c.customer_status', ['Active','Dormant'])
//       ->where(function ($q) use ($userId) {
//                     $q->where('l.created_by', $userId)
//                     ->orWhere('l.assigned_to', $userId);
//             });

//     /**
//      * SEARCH
//      */
//     if ($search) {
//         $query->where(function ($q) use ($search) {
//             $q->where('c.company_name', 'ILIKE', "%{$search}%")
//               ->orWhere('c.contact_name', 'ILIKE', "%{$search}%")
//               ->orWhere('c.email', 'ILIKE', "%{$search}%")
//               ->orWhere('c.customer_code', 'ILIKE', "%{$search}%");
//         });
//     }

//     /**
//      * SORT
//      */
//     $query->orderBy($sortBy, $sortDir);

//     $results = $query->paginate($perPage, ['*'], 'page', $page);

//     return ApiResponse::paginate(
//         VisitCustomersDataResourcesCollection::make($results),
//         $results->isEmpty()
//             ? 'Data customer tidak ditemukan'
//             : 'Success'
//     );
// }



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

}
