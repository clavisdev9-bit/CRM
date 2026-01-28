<?php

namespace App\Http\Controllers\Api\Users\Sales\Visits;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\VisitLeadsDataIndex;
use App\Http\Resources\VisitLeadsDataResourcesCollection;

class Visits extends Controller
{
    // public function VisitLeads(VisitLeadsDataIndex $request)
    // {
    //     $validated = $request->validated();

    //     $search   = $validated['search'] ?? null;
    //     $perPage  = $validated['per_page'] ?? 10;
    //     $sortBy   = $validated['sort_by'] ?? 'l.created_at';
    //     $sortDir  = $validated['sort_dir'] ?? 'desc';

    //     $user = auth()->user();
    //     $userId = $user->id_user;

    //     $query = DB::table('leads as l')
    // ->select([
    //     'l.id',
    //     'l.company_name',
    //     'l.contact_name',
    //     'l.email',
    //     'l.phone',
    //     'l.lead_category_id',
    //     'l.industry_id',
    //     'l.id_user',
    //     'l.assigned_to',
    //     'l.created_by',
    //     'l.lead_source',
    //     'l.lead_status',
    //     'l.visibility_type',
    //     'l.notes',
    //     'l.address',
    //     'l.last_contacted_at',
    //     'l.converted_at',
    //     'l.created_at',
    //     'l.updated_at',
    //     'l.deleted_at',
    //     'cat.name as category_name',
    //     'ind.name as industry_name',
    //     'owner.fullname as owner_name',
    //     'sales.fullname as assigned_name',
    // ])
    // ->leftJoin('lead_categories as cat', 'cat.id', '=', 'l.lead_category_id')
    // ->leftJoin('lead_industries as ind', 'ind.id', '=', 'l.industry_id')
    // ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'l.id_user')
    // ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'l.assigned_to')

    // // Filter lead status + created_by / assigned_to
    // ->whereIn('l.lead_status', ['New', 'Contacted', 'Qualified'])
    // ->where(function($q) use ($userId) {
    //     $q->where('l.created_by', $userId)
    //       ->orWhere('l.assigned_to', $userId);
    // });


       
    //     /**
    //      * ==========================
    //      * SEARCH
    //      * ==========================
    //      */
    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('l.company_name', 'ILIKE', "%{$search}%")
    //               ->orWhere('l.contact_name', 'ILIKE', "%{$search}%")
    //               ->orWhere('l.email', 'ILIKE', "%{$search}%");
    //         });
    //     }

    //     /**
    //      * ==========================
    //      * SORTING
    //      * ==========================
    //      */
    //     if ($sortBy === 'last_contacted_at') {
    //         $query->orderByRaw('l.last_contacted_at ASC NULLS FIRST');
    //     } else {
    //         $query->orderBy($sortBy, $sortDir);
    //     }

    //     $results = $query->paginate($perPage);

    //     return ApiResponse::paginate(
    //         VisitLeadsDataResourcesCollection::make($results),
    //         $results->isEmpty()
    //             ? 'Data yang Anda cari tidak ditemukan'
    //             : 'Success'
    //     );
    // }



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

}
