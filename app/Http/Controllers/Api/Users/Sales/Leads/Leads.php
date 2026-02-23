<?php

namespace App\Http\Controllers\Api\Users\Sales\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;
use App\Models\MsLeadsModel;
use App\Models\MsLeadsCategory;
use App\Models\MsLeadsIndustries;
use App\Http\Requests\LeadsValidationIndex;
use App\Http\Requests\LeadsValidationRequest;
use App\Http\Resources\LeadsResources;
use App\Http\Resources\LeadsResourcesCollection;
use App\Http\Requests\LeadsValidationRequestBulk;
use App\Imports\LeadsImport;
use Maatwebsite\Excel\Facades\Excel;


class Leads extends Controller
{
      protected $MsLeadsModel;
      protected $MsLeadsCategory;
      protected $MsLeadsIndustries;

      public function __construct(MsLeadsModel $MsLeadsModel,
        MsLeadsCategory $MsLeadsCategory,
        MsLeadsIndustries $MsLeadsIndustries) {
        $this->MsLeadsModel = $MsLeadsModel;
        $this->MsLeadsCategory = $MsLeadsCategory;
        $this->MsLeadsIndustries = $MsLeadsIndustries;
      }



      /**
 * Show single lead by ID
 */
public function showLead($id)
{
    $user = auth()->user();
    $userId = $user->id_user;

    // Ambil lead berdasarkan ID
    $lead = DB::table('leads as l')
        ->select([
            'l.*',
            'cat.name as category_name',
            'ind.name as industry_name',
            'owner.fullname as owner_name',
            'sales.fullname as assigned_name',
        ])
        ->leftJoin('lead_categories as cat', 'cat.id', '=', 'l.lead_category_id')
        ->leftJoin('lead_industries as ind', 'ind.id', '=', 'l.industry_id')
        ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'l.id_user')
        ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'l.assigned_to')
        ->where('l.id', $id)
        ->first();

    if (!$lead) {
        return response()->json([
            'success' => false,
            'message' => 'Lead not found',
            'data' => null
        ], 404);
    }

    // Optional: bisa cek permission, misal hanya owner / assigned yang boleh lihat
    if ($lead->id_user != $userId && $lead->assigned_to != $userId) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized to view this lead',
            'data' => null
        ], 403);
    }

    return response()->json([
        'success' => true,
        'message' => 'Success',
        'data' => $lead
    ]);
}



    //  ambil data leads berdasarkan yang sales buat
    public function leads(LeadsValidationIndex $request)
        {
            $validated = $request->validated();

            $search   = $validated['search'] ?? null;
            $perPage  = $validated['per_page'] ?? 10;
            $sortBy   = $validated['sort_by'] ?? 'l.created_at';
            $sortDir  = $validated['sort_dir'] ?? 'desc';

            $user = auth()->user();
            $userId = $user->id_user;
  

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

                /**
                 * ==========================
                 * FILTER DATA BERDASARKAN
                 * LEAD YANG DIBUAT OLEH SALES
                 * ==========================
                 */
            ->where('l.created_by', $userId);

            /** SEARCH */
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('l.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('l.contact_name', 'ILIKE', "%{$search}%")
                    ->orWhere('l.email', 'ILIKE', "%{$search}%");
                });
            }

            /** SORT */
            $query->orderBy($sortBy, $sortDir);

            $results = $query->paginate($perPage);

            return ApiResponse::paginate(
                LeadsResourcesCollection::make($results),
                $results->isEmpty()
                    ? 'Data yang Anda cari tidak ditemukan'
                    : 'Success'
            );
            
        }



         //  ambil data leads berdasarkan yang admin buat atau yang di assign ke sales
         //  ambil data leads yang hanya di assign ke sales yang login
     public function leadsAssignByAdminOrManager(LeadsValidationIndex $request)
        {
            $validated = $request->validated();

            $search   = $validated['search'] ?? null;
            $perPage  = $validated['per_page'] ?? 10;
            $sortBy   = $validated['sort_by'] ?? 'l.created_at';
            $sortDir  = $validated['sort_dir'] ?? 'desc';

            $user = auth()->user();
            $userId = $user->id_user;

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

                /**
                 * ==========================
                 * FILTER DATA UNIVERSAL
                 * ==========================
                 * Tampilkan lead yang di-assign ke user login (sales)
                 */
                ->where('l.assigned_to', $userId);


            /**
             * ==========================
             * SEARCH
             * ==========================
             */
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('l.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('l.contact_name', 'ILIKE', "%{$search}%")
                    ->orWhere('l.email', 'ILIKE', "%{$search}%");
                });
            }

            /**
             * ==========================
             * SORTING
             * ==========================
             */
            $query->orderBy($sortBy, $sortDir);

            $results = $query->paginate($perPage);

            return ApiResponse::paginate(
                LeadsResourcesCollection::make($results),
                $results->isEmpty()
                    ? 'Data yang Anda cari tidak ditemukan'
                    : 'Success'
            );
        }



          //  ambil data leads berdasarkan yang sales buat atau yang di assign ke sales(ini untuk data yang tampil
        //    di page admin nantinya)
     public function leadsAssignByAdminCreated(LeadsValidationIndex $request)
        {
            $validated = $request->validated();

            $search   = $validated['search'] ?? null;
            $perPage  = $validated['per_page'] ?? 10;
            $sortBy   = $validated['sort_by'] ?? 'l.created_at';
            $sortDir  = $validated['sort_dir'] ?? 'desc';

            $user = auth()->user();
            $userId = $user->id_user;

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

                /**
                 * ==========================
                 * FILTER DATA UNIVERSAL
                 * ==========================
                 * Tampilkan lead:
                 * - yang dibuat oleh user login (manager)
                 * - atau di-assign ke user login (sales)
                 */
                ->where(function($q) use ($userId) {
                    $q->where('l.created_by', $userId)
                    ->orWhere('l.assigned_to', $userId);
                });

               


            /**
             * ==========================
             * SEARCH
             * ==========================
             */
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('l.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('l.contact_name', 'ILIKE', "%{$search}%")
                    ->orWhere('l.email', 'ILIKE', "%{$search}%");
                });
            }

            /**
             * ==========================
             * SORTING
             * ==========================
             */
            $query->orderBy($sortBy, $sortDir);

            $results = $query->paginate($perPage);

            return ApiResponse::paginate(
                LeadsResourcesCollection::make($results),
                $results->isEmpty()
                    ? 'Data yang Anda cari tidak ditemukan'
                    : 'Success'
            );
        }




         //  ambil data leads semua untuk admin dan manager
    public function allDataLeads(LeadsValidationIndex $request)
            {
                $validated = $request->validated();

                $search   = $validated['search'] ?? null;
                $perPage  = $validated['per_page'] ?? 10;
                $sortBy   = $validated['sort_by'] ?? 'l.created_at';
                $sortDir  = $validated['sort_dir'] ?? 'desc';

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
                    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'l.assigned_to');

                /**
                 * ==========================
                 * SEARCH (opsional)
                 * ==========================
                 */
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('l.company_name', 'ILIKE', "%{$search}%")
                        ->orWhere('l.contact_name', 'ILIKE', "%{$search}%")
                        ->orWhere('l.email', 'ILIKE', "%{$search}%");
                    });
                }

                /**
                 * ==========================
                 * SORTING
                 * ==========================
                 */
                $query->orderBy($sortBy, $sortDir);

                $results = $query->paginate($perPage);

                return ApiResponse::paginate(
                    LeadsResourcesCollection::make($results),
                    $results->isEmpty()
                        ? 'Data yang Anda cari tidak ditemukan'
                        : 'Success'
                );
            }



             //untuk store single lead
            public function storeLead(LeadsValidationRequest $request)
                {
                    $data = $request->validated();
                    try {
                        // Optional: cek duplicate (misal company_name + contact_name)
                        // $errors = DB::table('leads')
                        //     ->where('company_name', $data['company_name'])
                        //     ->where('contact_name', $data['contact_name'])
                        //     ->exists();

                        // if ($errors) {
                        //     return ApiResponse::error('Validation failed', ['company_name' => ['The lead already exists']], 400);
                        // }

                        $user = auth()->user();
                        $userId = $user->id_user;

                        // Insert lead
                        $leadId = DB::table('leads')->insertGetId([
                            'company_name'     => $data['company_name'],
                            'contact_name'     => $data['contact_name'],
                            'email'            => $data['email'] ?? null,
                            'phone'            => $data['phone'] ?? null,
                            'lead_source'      => $data['lead_source'] ?? null,
                            'lead_status'      => $data['lead_status'] ?? 'New',
                            'industry_id'      => $data['industry_id'] ?? null,
                            'lead_category_id' => $data['lead_category_id'] ?? null,
                            'assigned_to'      => $data['assigned_to'] ?? null,

                            'id_user'          => $userId, // owner
                            'created_by'       => $userId, // creator

                            'visibility_type'  => $data['visibility_type'] ?? 'PRIVATE',
                            'notes'            => $data['notes'] ?? null,
                            'address'          => $data['address'] ?? null,
                            'last_contacted_at'=> $data['last_contacted_at'] ?? null,

                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);

                        // Ambil data lead yang baru dibuat
                        $lead = DB::table('leads as l')
                            ->select([
                                'l.*',
                                'cat.name as category_name',
                                'ind.name as industry_name',
                                'owner.fullname as owner_name',
                                'sales.fullname as assigned_name',
                            ])
                            ->leftJoin('lead_categories as cat', 'cat.id', '=', 'l.lead_category_id')
                            ->leftJoin('lead_industries as ind', 'ind.id', '=', 'l.industry_id')
                            ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'l.id_user')
                            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'l.assigned_to')
                            ->where('l.id', $leadId)
                            ->first();

                        return ApiResponse::success($lead, 'Success Create New Lead', 201);

                    } catch (\Illuminate\Database\QueryException $e) {
                        return ApiResponse::error('Failed to create lead (query error)', [
                            'exception' => config('app.debug') ? $e->getMessage() : null
                        ], 422);
                    } catch (\Exception $e) {
                        return ApiResponse::error('An error occurred while creating the lead.', [
                            'exception' => config('app.debug') ? $e->getMessage() : null
                        ], 500);
                    }
                }




            //untuk store bulk leads
            public function storeBulkLead(LeadsValidationRequestBulk $request)
                {
                    $data = $request->validated();

                    // Pastikan data dikirim dalam bentuk array
                    if (!isset($data['leads']) || !is_array($data['leads'])) {
                        return ApiResponse::error('Invalid payload', ['leads' => ['Field leads must be an array']], 400);
                    }

                    $user = auth()->user();
                    $userId = $user->id_user;

                    try {
                        $insertedLeads = [];

                        // Gunakan transaction supaya semua insert aman
                        DB::transaction(function () use ($data, $userId, &$insertedLeads) {
                            foreach ($data['leads'] as $item) {
                                // Optional: cek duplicate berdasarkan company + contact
                                $exists = DB::table('leads')
                                    ->where('company_name', $item['company_name'])
                                    ->where('contact_name', $item['contact_name'])
                                    ->exists();

                                if ($exists) {
                                    continue; // skip jika duplicate
                                }

                                $leadId = DB::table('leads')->insertGetId([
                                    'company_name'     => $item['company_name'],
                                    'contact_name'     => $item['contact_name'],
                                    'email'            => $item['email'] ?? null,
                                    'phone'            => $item['phone'] ?? null,
                                    'lead_source'      => $item['lead_source'] ?? null,
                                    'lead_status'      => $item['lead_status'] ?? 'New',
                                    'industry_id'      => $item['industry_id'] ?? null,
                                    'lead_category_id' => $item['lead_category_id'] ?? null,
                                    'assigned_to'      => $item['assigned_to'] ?? null,
                                    'id_user'          => $userId, // owner
                                    'created_by'       => $userId, // creator
                                    'visibility_type'  => $item['visibility_type'] ?? 'PRIVATE',
                                    'address'          => $item['address'] ?? null,
                                    'notes'            => $item['notes'] ?? null,
                                    'last_contacted_at'=> $item['last_contacted_at'] ?? null,
                                    'created_at'       => now(),
                                    'updated_at'       => now(),
                                ]);

                                // Ambil data lead baru
                                $lead = DB::table('leads as l')
                                    ->select([
                                        'l.*',
                                        'cat.name as category_name',
                                        'ind.name as industry_name',
                                        'owner.fullname as owner_name',
                                        'sales.fullname as assigned_name',
                                    ])
                                    ->leftJoin('lead_categories as cat', 'cat.id', '=', 'l.lead_category_id')
                                    ->leftJoin('lead_industries as ind', 'ind.id', '=', 'l.industry_id')
                                    ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'l.id_user')
                                    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'l.assigned_to')
                                    ->where('l.id', $leadId)
                                    ->first();

                                $insertedLeads[] = $lead;
                            }
                        });

                        return ApiResponse::success($insertedLeads, 'Success Create Bulk Leads', 201);

                    } catch (\Illuminate\Database\QueryException $e) {
                        return ApiResponse::error('Failed to create bulk leads (query error)', [
                            'exception' => config('app.debug') ? $e->getMessage() : null
                        ], 422);
                    } catch (\Exception $e) {
                        return ApiResponse::error('An error occurred while creating bulk leads.', [
                            'exception' => config('app.debug') ? $e->getMessage() : null
                        ], 500);
                    }
                }



                            /**
             * Update lead by ID
             */
        public function updateLead(Request $request, $id)
        {
            $user = auth()->user();
            $userId = $user->id_user;

            // Validasi input
            $data = $request->validate([
                'company_name'     => 'sometimes|required|string|max:255',
                'contact_name'     => 'sometimes|required|string|max:255',
                'email'            => 'sometimes|required|email|max:255',
                'phone'            => 'sometimes|required|string|max:50',
                'lead_source'      => 'sometimes|required|string|max:255',
                'lead_status'      => 'sometimes|required|string|max:20',
                'industry_id'      => 'sometimes|required|integer',
                'lead_category_id' => 'sometimes|required|integer',
                'assigned_to'      => 'sometimes|nullable|integer',
                'visibility_type'  => 'sometimes|nullable|string|max:20',
                'notes'            => 'sometimes|nullable|string',
                'last_contacted_at'=> 'sometimes|nullable|date',
                'converted_at'     => 'sometimes|nullable|date',
            ]);

            // Ambil lead
            $lead = DB::table('leads')->where('id', $id)->first();

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found',
                    'data' => null
                ], 404);
            }

            // Optional: hanya owner / assigned yang bisa edit
            // if ($lead->id_user != $userId && $lead->assigned_to != $userId) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Unauthorized to update this lead',
            //         'data' => null
            //     ], 403);
            // }

            try {
                // Update data lead
                DB::table('leads')->where('id', $id)->update(array_merge($data, [
                    'updated_at' => now(),
                ]));

                // Ambil kembali data lead yang sudah diupdate
                $updatedLead = DB::table('leads as l')
                    ->select([
                        'l.*',
                        'cat.name as category_name',
                        'ind.name as industry_name',
                        'owner.fullname as owner_name',
                        'sales.fullname as assigned_name',
                    ])
                    ->leftJoin('lead_categories as cat', 'cat.id', '=', 'l.lead_category_id')
                    ->leftJoin('lead_industries as ind', 'ind.id', '=', 'l.industry_id')
                    ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'l.id_user')
                    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'l.assigned_to')
                    ->where('l.id', $id)
                    ->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Lead updated successfully',
                    'data' => $updatedLead
                ]);

            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update lead (query error)',
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 422);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the lead',
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }



        /**
         * Delete / soft delete lead by ID
        */
        // public function deleteLead($id)
        // {
        //     $user = auth()->user();
        //     $userId = $user->id_user;
        //     $userRole = $user->role; // misal: 'admin', 'manager', 'sales'

        //     // Ambil lead
        //     $lead = DB::table('leads')->where('id', $id)->first();

        //     if (!$lead) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Lead not found',
        //             'data' => null
        //         ], 404);
        //     }

        //     // Cek permission
        //     if ($userRole === 'sales' && $lead->id_user != $userId && $lead->assigned_to != $userId) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Unauthorized to delete this lead',
        //             'data' => null
        //         ], 403);
        //     }

        //     try {
        //         // Soft delete
        //         DB::table('leads')->where('id', $id)->update([
        //             'deleted_at' => now(),
        //             'updated_at' => now(),
        //         ]);

        //         return response()->json([
        //             'success' => true,
        //             'message' => 'Lead deleted successfully',
        //             'data' => null
        //         ]);

        //     } catch (\Illuminate\Database\QueryException $e) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Failed to delete lead (query error)',
        //             'exception' => config('app.debug') ? $e->getMessage() : null
        //         ], 422);
        //     } catch (\Exception $e) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'An error occurred while deleting the lead',
        //             'exception' => config('app.debug') ? $e->getMessage() : null
        //         ], 500);
        //     }
        // }

        public function deleteLead(Request $request, $id)
            {
                $user = auth()->user();
                $userId = $user->id_user;
                $userRole = $user->role; // contoh: 'admin', 'manager', 'sales'

                // Ambil tipe delete: soft (default) atau hard
                $deleteType = $request->query('type', 'hard'); // ?type=hard untuk delete permanen

                // Ambil lead
                $lead = DB::table('leads')->where('id', $id)->first();

                if (!$lead) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lead not found',
                        'data' => null
                    ], 404);
                }

                // Cek permission
                if ($userRole === 'sales' && $lead->id_user != $userId && $lead->assigned_to != $userId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to delete this lead',
                        'data' => null
                    ], 403);
                }

                try {
                    if ($deleteType === 'hard') {
                        // 🔥 Hard delete → hapus permanen
                        DB::table('leads')->where('id', $id)->delete();
                        $msg = 'Lead deleted permanently';
                    } else {
                        // Soft delete → tetap di DB tapi diberi deleted_at
                        DB::table('leads')->where('id', $id)->update([
                            'deleted_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $msg = 'Lead deleted (soft delete)';
                    }

                    return response()->json([
                        'success' => true,
                        'message' => $msg,
                        'data' => null
                    ]);

                } catch (\Illuminate\Database\QueryException $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to delete lead (query error)',
                        'exception' => config('app.debug') ? $e->getMessage() : null
                    ], 422);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An error occurred while deleting the lead',
                        'exception' => config('app.debug') ? $e->getMessage() : null
                    ], 500);
                }
            }





        public function selectCategoryLead()
                {
                    return response()->json(
                        DB::table('lead_categories')
                            ->select('id', 'name')
                            ->orderBy('name', 'asc')
                            ->get()
                    );
                }


         public function selectIndustryLead()
                {
                    return response()->json(
                        DB::table('lead_industries')
                            ->select('id', 'name')
                            ->orderBy('name', 'asc')
                            ->get()
                    );
                }  
                
                
             
                    public function selectUserByDivision()
                            {
                                return response()->json(
                                    DB::table('ms_users as mu')
                                        ->join('ms_division as md', 'mu.divisi_id', '=', 'md.id')
                                        ->where('mu.is_active', true)
                                        ->where('mu.divisi_id', 3)
                                        ->orderBy('mu.fullname', 'asc')
                                        ->select(
                                            'mu.id_user',
                                            'mu.fullname as name',
                                            'md.name_division'
                                            
                                        )
                                        ->get()
                                );
                            }







          //import excel leads (belum selesai)
             public function importLeads(Request $request)
                {
                    // $request->validate([
                    //     'file' => 'required|mimes:xlsx,xls,csv',
                    // ]);

                    $file = $request->file('file');
                    $user = auth()->user();
                    $userId = $user->id_user;

                    try {
                        // Import Excel ke database
                        Excel::import(new LeadsImport($userId), $file);

                        return response()->json([
                            'success' => true,
                            'message' => 'Leads imported successfully',
                        ]);
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Import failed: ' . $e->getMessage(),
                        ], 500);
                    }
                }


       //ini untuk timeline follow up lead tampil di lead detail (implementasi saat nanti semua sudah siap)
        public function leadFollowUpTimeline(Request $request, $leadId)
        {
            $userId = auth()->user()->id_user;

            try {
                /* ================= CEK AKSES LEAD ================= */
                $lead = DB::table('leads')
                    ->where('id', $leadId)
                    ->where(function ($q) use ($userId) {
                        $q->where('created_by', $userId)
                        ->orWhere('assigned_to', $userId);
                    })
                    ->whereNull('deleted_at')
                    ->first();

                if (!$lead) {
                    return ApiResponse::error(
                        'Lead not found or access denied',
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
                        'fu.status',        // ⬅️ status follow up
                        'fu.notes',
                        'fu.created_at',

                        'sales.fullname as sales_name',
                    ])
                    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'fu.created_by')
                    ->where('fu.lead_id', $leadId)
                    ->whereNull('fu.deleted_at')
                    ->orderBy('fu.follow_up_at', 'desc')
                    ->get();

                return ApiResponse::success(
                    $timeline,
                    'Success Get Lead Follow Up Timeline'
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
