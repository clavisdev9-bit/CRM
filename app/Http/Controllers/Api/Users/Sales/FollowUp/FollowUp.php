<?php

namespace App\Http\Controllers\Api\Users\Sales\FollowUp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\MsFollowUp;
use App\Models\MsLeadsModel;
use App\Models\MsCustomers;
use App\Models\FollowUpActivity;
use App\Helpers\ApiResponse;
Use App\Http\Requests\FollowUpValidationIndex;
use App\Http\Requests\FollowUpValidationRequest;
use App\Http\Requests\FollowUpValidationUpdate;
use App\Http\Resources\FollowUpLeadResourcesCollection;
use App\Http\Resources\FollowUpLeadResources;
use App\Http\Resources\FollowUpCustomerResourcesCollection;
use App\Http\Resources\FollowUpCustomerResources;
use Carbon\Carbon;
use App\Http\Requests\SubmitFollowUpResultRequest;



class FollowUp extends Controller
{
     protected  $MsFollowUp;
     protected $MsLeadsModel;
     protected $MsCustomers;
     public function __construct(MsFollowUp $MsFollowUp, MsLeadsModel $MsLeadsModel, MsCustomers $MsCustomers) {
        $this->MsFollowUp = $MsFollowUp;
        $this->MsLeadsModel = $MsLeadsModel;
        $this->MsCustomers = $MsCustomers;
     }


          //code untuk menampilkan list follow up berdasarkan leads (dengan fitur search, filter tanggal, sorting, pagination, dan computed column overdue)
            public function followUpSalesByLeads(FollowUpValidationIndex $request)
            {
                $validated = $request->validated();
                $user = auth()->user();
                $search    = $validated['search'] ?? null;
                $perPage   = $validated['per_page'] ?? 10;
                $startDate = $validated['start_date'] ?? null;
                $endDate   = $validated['end_date'] ?? null;
                /* ================= SAFE SORTING ================= */
                $allowedSorts = [
                    'follow_up_at' => 'follow_ups.follow_up_at',
                    'created_at'   => 'follow_ups.created_at',
                    'company_name' => 'l.company_name',
                    'status'       => 'follow_ups.status',
                ];
                $sortKey = $validated['sort_by'] ?? 'follow_up_at';
                $sortBy  = $allowedSorts[$sortKey] ?? 'follow_ups.follow_up_at';
                $sortDir = $validated['sort_dir'] ?? 'desc';

                /* ================= OVERDUE LOGIC ================= */
                $overdueCondition = "
                follow_ups.follow_up_at < NOW()
                AND follow_ups.status = 'PENDING'
            ";
                $query = $this->MsFollowUp->query()
                    ->select([
                        'follow_ups.id',
                        'follow_ups.lead_id',
                        'follow_ups.follow_up_type',
                        'follow_ups.subject',
                        'follow_ups.notes',
                        'follow_ups.follow_up_at',
                        'follow_ups.status',
                        'follow_ups.created_at',
                        'follow_ups.follow_up_code',

                        'l.company_name as lead_company_name',
                        'l.lead_status',

                        'sales.fullname as sales_name',

                        DB::raw("CASE WHEN {$overdueCondition} THEN 1 ELSE 0 END as is_overdue"),
                        DB::raw("CASE WHEN {$overdueCondition} THEN 'OVERDUE' ELSE follow_ups.status END as computed_status"),
                    ])
                    ->join('leads as l', 'l.id', '=', 'follow_ups.lead_id')
                    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'follow_ups.created_by')
                    ->where('follow_ups.created_by', $user->id_user)
                    ->whereNull('follow_ups.deleted_at');

                /* ================= SEARCH ================= */
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('follow_ups.follow_up_code', 'ILIKE', "%{$search}%")
                        ->orWhere('l.company_name', 'ILIKE', "%{$search}%")
                        ->orWhere('follow_ups.status', 'ILIKE', "%{$search}%");
                    });
                }

                /* ================= DATE FILTER ================= */
                if ($startDate) {
                    $query->whereDate('follow_ups.follow_up_at', '>=', $startDate);
                }

                if ($endDate) {
                    $query->whereDate('follow_ups.follow_up_at', '<=', $endDate);
                }

                /* ================= SORT ================= */
                $query->orderBy($sortBy, $sortDir);

                $results = $query->paginate($perPage);

                return ApiResponse::success(
                    new FollowUpLeadResourcesCollection($results),
                    $results->isEmpty()
                        ? "Data Follow Up Lead tidak ditemukan"
                        : "Success Get Follow Up Lead"
                );
            }


         

            //  public function followUpSalesByCustomers(FollowUpValidationIndex $request)
            //     {
            //         $validated = $request->validated();
            //         $user = auth()->user();

            //         $search    = $validated['search'] ?? null;
            //         $perPage   = $validated['per_page'] ?? 10;
            //         $startDate = $validated['start_date'] ?? null;
            //         $endDate   = $validated['end_date'] ?? null;

            //         /* ================= SAFE SORTING ================= */
            //         $allowedSorts = [
            //             'follow_up_at' => 'follow_ups.follow_up_at',
            //             'created_at'   => 'follow_ups.created_at',
            //             'company_name' => 'c.company_name',
            //             'status'       => 'follow_ups.status',
            //         ];

            //         $sortKey = $validated['sort_by'] ?? 'follow_up_at';
            //         $sortBy  = $allowedSorts[$sortKey] ?? 'follow_ups.follow_up_at';
            //         $sortDir = $validated['sort_dir'] ?? 'desc';

            //         /* ================= OVERDUE LOGIC ================= */
            //         $overdueCondition = "
            //             follow_ups.follow_up_at < NOW()
            //             AND follow_ups.status = 'PENDING'
            //         ";

            //         $query = $this->MsFollowUp->query()
            //             ->select([
            //                 'follow_ups.id',
            //                 'follow_ups.customer_id',
            //                 'follow_ups.lead_id', // tetap diambil untuk histori
            //                 'follow_ups.follow_up_type',
            //                 'follow_ups.subject',
            //                 'follow_ups.notes',
            //                 'follow_ups.follow_up_at',
            //                 'follow_ups.status',
            //                 'follow_ups.created_at',
            //                 'follow_ups.follow_up_code',

            //                 'c.company_name as customer_company_name',
            //                 'c.contact_name as customer_contact_name',
            //                 'c.customer_status',

            //                 'sales.fullname as sales_name',

            //                 DB::raw("CASE WHEN {$overdueCondition} THEN 1 ELSE 0 END as is_overdue"),
            //                 DB::raw("CASE WHEN {$overdueCondition} THEN 'OVERDUE' ELSE follow_ups.status END as computed_status"),
            //             ])

            //             /* ================= JOIN ================= */
            //             ->join('customers as c', 'c.id', '=', 'follow_ups.customer_id')
            //             ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'follow_ups.created_by')

            //             /* ================= OWNERSHIP ================= */
            //             ->where('follow_ups.created_by', $user->id_user)

            //             /* ================= PENTING !!!
            //             Jangan filter lead_id NULL
            //             Karena follow up lama bisa punya lead_id + customer_id
            //             ================= */
            //             ->whereNotNull('follow_ups.customer_id')

            //             ->whereNull('follow_ups.deleted_at');

            //         /* ================= SEARCH ================= */
            //         if ($search) {
            //             $query->where(function ($q) use ($search) {
            //                 $q->where('follow_ups.follow_up_code', 'ILIKE', "%{$search}%")
            //                 ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
            //                 ->orWhere('follow_ups.subject', 'ILIKE', "%{$search}%")
            //                 ->orWhere('follow_ups.status', 'ILIKE', "%{$search}%");
            //             });
            //         }

            //         /* ================= DATE FILTER =================
            //         Jangan sampai overdue hilang karena filter tanggal.
            //         Maka overdue tetap ikut walau di luar range.
            //         ================= */
            //         if ($startDate || $endDate) {
            //             $query->where(function ($q) use ($startDate, $endDate, $overdueCondition) {

            //                 if ($startDate) {
            //                     $q->whereDate('follow_ups.follow_up_at', '>=', $startDate);
            //                 }

            //                 if ($endDate) {
            //                     $q->whereDate('follow_ups.follow_up_at', '<=', $endDate);
            //                 }

            //                 // overdue tetap tampil (CRM BEHAVIOUR)
            //                 $q->orWhereRaw($overdueCondition);
            //             });
            //         }

            //     $query->orderByRaw("
            //                 CASE 
            //                     WHEN {$overdueCondition} THEN 0
            //                     WHEN follow_ups.status = 'PENDING' 
            //                         AND follow_ups.follow_up_at <= NOW() + INTERVAL '1 day' THEN 1
            //                     WHEN follow_ups.status = 'PENDING' THEN 2
            //                     ELSE 3
            //                 END
            //             ");

            //     $query->orderBy('follow_ups.follow_up_at', 'asc');

            //         $results = $query->paginate($perPage);

            //         return ApiResponse::success(
            //             new FollowUpCustomerResourcesCollection($results),
            //             $results->isEmpty()
            //                 ? "Data Follow Up Customer tidak ditemukan"
            //                 : "Success Get Follow Up Customer"
            //         );
            //     }


            public function followUpSalesByCustomers(FollowUpValidationIndex $request)
                {
                    $validated = $request->validated();
                    $user = auth()->user();

                    $search    = $validated['search'] ?? null;
                    $perPage   = $validated['per_page'] ?? 10;
                    $startDate = $validated['start_date'] ?? null;
                    $endDate   = $validated['end_date'] ?? null;

                    /* ================= SAFE SORTING ================= */
                    $allowedSorts = [
                        'follow_up_at' => 'follow_ups.follow_up_at',
                        'created_at'   => 'follow_ups.created_at',
                        'company_name' => 'c.company_name',
                        'branch_name'  => 'br.branch_name',
                        'status'       => 'follow_ups.status',
                    ];

                    $sortKey = $validated['sort_by'] ?? 'follow_up_at';
                    $sortBy  = $allowedSorts[$sortKey] ?? 'follow_ups.follow_up_at';
                    $sortDir = $validated['sort_dir'] ?? 'desc';

                    /* ================= OVERDUE LOGIC ================= */
                    $overdueCondition = "
                        follow_ups.follow_up_at < NOW()
                        AND follow_ups.status = 'PENDING'
                    ";

                    /* ================= CONTACT RESOLUTION (subquery) ================= */
                    $contactNameSql = "
                        COALESCE(
                            (SELECT bc.name FROM branch_contacts bc
                                WHERE bc.branch_id = follow_ups.branch_id
                                AND bc.is_primary = true
                                AND bc.deleted_at IS NULL
                                LIMIT 1),
                            br.contact_name,
                            (SELECT cc.name FROM customer_contacts cc
                                WHERE cc.customer_id = follow_ups.customer_id
                                AND cc.is_primary = true
                                AND cc.deleted_at IS NULL
                                LIMIT 1),
                            c.contact_name
                        ) as resolved_contact_name
                    ";

                    $contactPhoneSql = "
                        COALESCE(
                            (SELECT bc.phone FROM branch_contacts bc
                                WHERE bc.branch_id = follow_ups.branch_id
                                AND bc.is_primary = true
                                AND bc.deleted_at IS NULL
                                LIMIT 1),
                            br.phone,
                            (SELECT cc.phone FROM customer_contacts cc
                                WHERE cc.customer_id = follow_ups.customer_id
                                AND cc.is_primary = true
                                AND cc.deleted_at IS NULL
                                LIMIT 1),
                            c.phone
                        ) as resolved_contact_phone
                    ";

                    $query = $this->MsFollowUp->query()
                        ->select([
                            'follow_ups.id',
                            'follow_ups.customer_id',
                            'follow_ups.branch_id',
                            'follow_ups.lead_id', // tetap diambil untuk histori
                            'follow_ups.follow_up_type',
                            'follow_ups.subject',
                            'follow_ups.notes',
                            'follow_ups.follow_up_at',
                            'follow_ups.status',
                            'follow_ups.created_at',
                            'follow_ups.follow_up_code',

                            // Head company (selalu ada)
                            'c.company_name as customer_company_name',
                            'c.customer_status',

                            // Branch (null kalau follow up ke head office)
                            'br.branch_name as branch_name',
                            'br.is_main_branch',
                            'br.city as branch_city',

                            'sales.fullname as sales_name',

                            DB::raw($contactNameSql),
                            DB::raw($contactPhoneSql),

                            DB::raw("CASE WHEN {$overdueCondition} THEN 1 ELSE 0 END as is_overdue"),
                            DB::raw("CASE WHEN {$overdueCondition} THEN 'OVERDUE' ELSE follow_ups.status END as computed_status"),
                        ])

                        /* ================= JOIN ================= */
                        ->join('customers as c', 'c.id', '=', 'follow_ups.customer_id')
                        ->leftJoin('customer_branches as br', 'br.id', '=', 'follow_ups.branch_id')
                        ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'follow_ups.created_by')

                        /* ================= OWNERSHIP ================= */
                        ->where('follow_ups.created_by', $user->id_user)
                        ->whereNotNull('follow_ups.customer_id')
                        ->whereNull('follow_ups.deleted_at');

                    /* ================= SEARCH ================= */
                    if ($search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('follow_ups.follow_up_code', 'ILIKE', "%{$search}%")
                            ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
                            ->orWhere('br.branch_name', 'ILIKE', "%{$search}%")
                            ->orWhere('follow_ups.subject', 'ILIKE', "%{$search}%")
                            ->orWhere('follow_ups.status', 'ILIKE', "%{$search}%");
                        });
                    }

                    /* ================= DATE FILTER (overdue tetap tampil) ================= */
                    if ($startDate || $endDate) {
                        $query->where(function ($q) use ($startDate, $endDate, $overdueCondition) {
                            if ($startDate) {
                                $q->whereDate('follow_ups.follow_up_at', '>=', $startDate);
                            }
                            if ($endDate) {
                                $q->whereDate('follow_ups.follow_up_at', '<=', $endDate);
                            }
                            $q->orWhereRaw($overdueCondition);
                        });
                    }

                    $query->orderByRaw("
                        CASE
                            WHEN {$overdueCondition} THEN 0
                            WHEN follow_ups.status = 'PENDING'
                                AND follow_ups.follow_up_at <= NOW() + INTERVAL '1 day' THEN 1
                            WHEN follow_ups.status = 'PENDING' THEN 2
                            ELSE 3
                        END
                    ");

                    $query->orderBy('follow_ups.follow_up_at', 'asc');

                    $results = $query->paginate($perPage);

                    return ApiResponse::success(
                        new FollowUpCustomerResourcesCollection($results),
                        $results->isEmpty()
                            ? "Data Follow Up Customer tidak ditemukan"
                            : "Success Get Follow Up Customer"
                    );
                }


                // code untuk menampilkan list follow up untuk form follow Up (hanya follow up yang masih aktif, dengan info lead, dan computed column overdue)
                public function getLeadsNeedFollowUp(Request $request)
                {
                    $userId = auth()->user()->id_user;

                    $query = DB::table('follow_ups as f') //  mulai dari follow_up (bukan leads)
                        ->join('leads as l', 'l.id', '=', 'f.lead_id')

                        ->select([
                            'l.id as lead_id',
                            'l.company_name',
                            'l.contact_name',

                            'f.id as follow_up_id',
                            'f.follow_up_code',
                            'f.follow_up_at',
                            'f.status',
                            'f.subject',

                            DB::raw("
                                CASE
                                    WHEN f.follow_up_at < NOW() THEN 'OVERDUE'
                                    WHEN f.follow_up_at BETWEEN NOW() AND NOW() + INTERVAL '1 DAY' THEN 'DUE_SOON'
                                    ELSE 'SCHEDULED'
                                END as urgency_status
                            "),

                            DB::raw("
                                EXTRACT(EPOCH FROM (f.follow_up_at - NOW()))
                                as seconds_remaining
                            ")
                        ])

                        // hanya milik sales login
                        ->where(function ($q) use ($userId) {
                            $q->where('l.created_by', $userId)
                            ->orWhere('l.assigned_to', $userId);
                        })

                        ->whereNull('f.deleted_at')
                        ->whereNull('l.deleted_at')
                        ->whereNull('l.converted_at')

                        // hanya follow up yang masih aktif
                        ->whereNotIn('f.status', ['DONE', 'CANCELLED'])

                        ->orderBy('f.follow_up_at', 'ASC');

                    $data = $query->limit(50)->get()->map(function ($item) {

                        $seconds = (int) $item->seconds_remaining;

                        if ($seconds < 0) {
                            $seconds = abs($seconds);

                            if ($seconds < 3600) {
                                $text = "Overdue " . floor($seconds / 60) . " min";
                            } elseif ($seconds < 86400) {
                                $text = "Overdue " . floor($seconds / 3600) . " hour";
                            } else {
                                $text = "Overdue " . floor($seconds / 86400) . " day";
                            }
                        } else {
                            if ($seconds < 3600) {
                                $text = floor($seconds / 60) . " min left";
                            } elseif ($seconds < 86400) {
                                $text = floor($seconds / 3600) . " hour left";
                            } else {
                                $text = floor($seconds / 86400) . " day left";
                            }
                        }

                        $item->time_remaining_text = $text;

                        $item->follow_up_at_formatted = Carbon::parse($item->follow_up_at)
                            ->format('d M Y H:i');

                        return $item;
                    });

                    return ApiResponse::success($data, 'Leads Need Follow Up');
                }

         

                    // ini kayanya perlu perubahan seperti leads juga nanati pas garap visit customer
                    public function getCustomersBySales(Request $request)
                    {
                        $userId = auth()->user()->id_user;
                        $search = $request->get('search');

                        $query = DB::table('customers as c')
                            ->select([
                                'c.id',
                                'c.company_name',
                                'c.contact_name',
                            ])
                            ->where(function ($q) use ($userId) {
                                $q->where('c.created_by', $userId)
                                ->orWhere('c.assigned_to', $userId);
                            })
                            ->whereNull('c.deleted_at');

                        if ($search) {
                            $query->where(function ($q) use ($search) {
                                $q->where('c.company_name', 'ILIKE', "%{$search}%")
                                ->orWhere('c.contact_name', 'ILIKE', "%{$search}%");
                            });
                        }

                        return ApiResponse::success(
                            $query->orderBy('c.company_name')->limit(20)->get(),
                            'Success Get Customers By Sales'
                        );
                    }



                    //code ini untuk  select2 di form follow up, untuk memilih lead untuk direct follow up, nanti kalau mau buat follow up langsung dari lead (tanpa visit) juga bisa pakai ini untuk cari customer yang mau difollow up
                    public function getLeadsForDirectFollowUp(Request $request)
                    {
                        $userId = auth()->user()->id_user;
                        $search = $request->get('search');

                        $query = DB::table('leads as l')
                            ->select([
                                'l.id',
                                'l.company_name',
                                'l.contact_name',
                            ])
                            ->where(function ($q) use ($userId) {
                                $q->where('l.created_by', $userId)
                                ->orWhere('l.assigned_to', $userId);
                            })
                            ->whereNull('l.deleted_at')
                            ->where('l.lead_status', 'New');

                        if ($search) {
                            $query->where(function ($q) use ($search) {
                                $q->where('l.company_name', 'ILIKE', "%{$search}%")
                                ->orWhere('l.contact_name', 'ILIKE', "%{$search}%");
                            });
                        }

                        return ApiResponse::success(
                            $query->orderBy('l.company_name')->limit(100)->get(),
                            'Success Get Leads For Direct Follow Up'
                        );
                    }




    

                 public function updateFollowUp(FollowUpValidationUpdate $request, $id) {
                    $data = $request->validated();
                    $userId = auth()->user()->id_user;

                    try {
                        $followUp = DB::table('follow_ups')
                            ->where('id', $id)
                            ->where('created_by', $userId)
                            ->whereNull('deleted_at')
                            ->first();

                        if (!$followUp) {
                            return ApiResponse::error(
                                'Follow up not found or access denied',
                                null,
                                404
                            );
                        }

                        DB::transaction(function () use ($id, $data, $userId, $followUp) {

                            /*
                            |------------------------------------------------------------------
                            | 1. UPDATE FOLLOW UP
                            |------------------------------------------------------------------
                            */
                            DB::table('follow_ups')
                                ->where('id', $id)
                                ->update([
                                    'follow_up_at' => $data['follow_up_at'],
                                    'notes'        => $data['notes'] ?? null,
                                    'subject'      => $data['subject'] ?? null,
                                    'updated_at'   => now(),
                                ]);

                            /*
                            |------------------------------------------------------------------
                            | 2. LOG PERUBAHAN (hanya field yang berubah)
                            |------------------------------------------------------------------
                            */
                            $changes = [];

                            if ($followUp->follow_up_at !== $data['follow_up_at']) {
                                $changes[] = 'Follow up date: '
                                    . Carbon::parse($followUp->follow_up_at)->format('d M Y')
                                    . ' → '
                                    . Carbon::parse($data['follow_up_at'])->format('d M Y');
                            }

                            if (($followUp->subject ?? '') !== ($data['subject'] ?? '')) {
                                $changes[] = 'Subject updated';
                            }

                            if (($followUp->notes ?? '') !== ($data['notes'] ?? '')) {
                                $changes[] = 'Notes updated';
                            }

                            $description = count($changes) > 0
                                ? implode(', ', $changes)
                                : 'Follow up updated';

                            /*
                            |------------------------------------------------------------------
                            | 3. INSERT ACTIVITY LOG
                            |------------------------------------------------------------------
                            */
                            DB::table('follow_up_activities')->insert([
                                'follow_up_id'  => $id,
                                'title'         => 'Follow Up Updated',
                                'description'   => $description,
                                'activity_type' => 'UPDATE',
                                'scheduled_for' => $data['follow_up_at'],
                                'activity_at'   => now(),
                                'created_at'    => now(),
                                'created_by'    => $userId,
                            ]);
                        });

                        // Ambil data terbaru
                        $updated = DB::table('follow_ups as fu')
                            ->select([
                                'fu.*',
                                'l.company_name as lead_company_name',
                                'c.company_name as customer_company_name',
                                'sales.fullname as sales_name',
                            ])
                            ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
                            ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
                            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'fu.created_by')
                            ->where('fu.id', $id)
                            ->first();

                        return ApiResponse::success(
                            $updated,
                            'Success Update Follow Up'
                        );

                    } catch (\Throwable $e) {
                        return ApiResponse::error(
                            'Failed to update follow up',
                            config('app.debug') ? ['exception' => $e->getMessage()] : null,
                            500
                        );
                    }
                }



                // code untuk delete follow up (beserta semua aktivitas, visits, dan file terkait) - ini untuk kebutuhan quick action di list leads
                public function deleteFollowUp($id)
                {
                    $userId = auth()->user()->id_user;

                    DB::beginTransaction();

                    try {

                        /*
                        |--------------------------------------------------------------------------
                        | 0️ Validate Follow Up Ownership
                        |--------------------------------------------------------------------------
                        */
                        $followUp = DB::table('follow_ups')
                            ->where('id', $id)
                            ->where('created_by', $userId)
                            ->whereNull('deleted_at')
                            ->first();

                        if (!$followUp) {
                            return ApiResponse::error(
                                'Follow up not found or access denied',
                                null,
                                404
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | 1️ HARD DELETE activities (no soft delete)
                        |--------------------------------------------------------------------------
                        */
                        DB::table('follow_up_activities')
                            ->where('follow_up_id', $id)
                            ->delete();


                        /*
                        |--------------------------------------------------------------------------
                        | 2️ HARD DELETE follow up
                        |--------------------------------------------------------------------------
                        */
                        DB::table('follow_ups')
                            ->where('id', $id)
                            ->delete();


                        /*
                        |--------------------------------------------------------------------------
                        | 3️ Ambil semua visits untuk hapus FILE dulu
                        |--------------------------------------------------------------------------
                        */
                        $visits = DB::table('visits')
                            ->where('lead_id', $followUp->lead_id)
                            ->get();

                        foreach ($visits as $visit) {

                            // contoh isi DB: visits/checkin/IMG123.jpg
                            if (!empty($visit->photo)) {

                                $filePath = $visit->photo;

                                if (Storage::disk('public')->exists($filePath)) {
                                    Storage::disk('public')->delete($filePath);
                                }
                            }
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | 4️ HARD DELETE visits (setelah file aman dihapus)
                        |--------------------------------------------------------------------------
                        */
                        DB::table('visits')
                            ->where('lead_id', $followUp->lead_id)
                            ->delete();


                        /*
                        |--------------------------------------------------------------------------
                        | 5️ Reset Lead ke kondisi awal
                        |--------------------------------------------------------------------------
                        */
                        DB::table('leads')
                            ->where('id', $followUp->lead_id)
                            ->update([
                                'lead_status'  => 'New',
                                'converted_at' => null,
                                'updated_at'   => now(),
                            ]);


                        DB::commit();

                        return ApiResponse::success(
                            null,
                            'Success Delete Follow Up, Visits & Images cleaned'
                        );

                    } catch (\Throwable $e) {

                        DB::rollBack();

                        return ApiResponse::error(
                            'Failed to delete follow up',
                            config('app.debug') ? ['exception' => $e->getMessage()] : null,
                            500
                        );
                    }
                }




                // code untuk menampilkan detail follow up (beserta info lead, customer, dan computed column overdue) old
                // public function showFollowUp($id)
                public function showFollowUp($id)
                    {
                        $user = auth()->user();

                        try {

                            /* ================= GET MAIN FOLLOW UP ================= */

                            $followUp = DB::table('follow_ups as fu')
                                ->select([
                                    'fu.id',
                                    'fu.follow_up_code',
                                    'fu.lead_id',
                                    'fu.customer_id',
                                    'fu.follow_up_type',
                                    'fu.subject',
                                    'fu.notes',
                                    'fu.follow_up_at',
                                    'fu.status',
                                    'fu.result',
                                    'fu.completed_at',
                                    'fu.closed_at',
                                    'fu.closed_reason',
                                    'fu.created_at',
                                    'fu.updated_at',

                                    // Lead
                                    'l.company_name as lead_company_name',
                                    'l.contact_name as lead_contact_name',
                                    'l.lead_status',

                                    // Customer
                                    'c.company_name as customer_company_name',
                                    'c.contact_name as customer_contact_name',
                                    'c.customer_status',

                                    // Sales
                                    'sales.fullname as sales_name',
                                ])
                                ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
                                ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
                                ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'fu.created_by')
                                ->where('fu.id', $id)
                                ->where('fu.created_by', $user->id_user)
                                ->whereNull('fu.deleted_at')
                                ->first();

                            if (!$followUp) {
                                return ApiResponse::error(
                                    'Follow up not found or access denied',
                                    null,
                                    404
                                );
                            }

                            /* ================= OVERDUE LOGIC ================= */

                            $isOverdue = false;
                            $computedStatus = $followUp->status;

                            if (
                                $followUp->status === 'PENDING' &&
                                $followUp->follow_up_at &&
                                now()->greaterThan($followUp->follow_up_at)
                            ) {
                                $isOverdue = true;
                                $computedStatus = 'OVERDUE';
                            }

                            $followUp->is_overdue = $isOverdue;
                            $followUp->computed_status = $computedStatus;


                            /* ================= GET ACTIVITIES ================= */

                            $activities = DB::table('follow_up_activities')
                                ->where('follow_up_id', $followUp->id)
                                ->orderByDesc('activity_at')
                                ->get();

                            $followUp->activities = $activities;


                            /* ================= GET COMPLAINT FROM VISITS ================= */

                            $complaints = collect();

                            if ($followUp->customer_id) {

                               
                                $complaints = DB::table('visits')
                            ->select([
                                'id as visit_id',
                                'visit_code',
                                'visit_at',
                                'check_in_at',
                                'check_out_at',
                                'latitude',
                                'longitude',
                                'gps_snapshot',
                                'has_complaint',
                                'complaint_detail',
                                'has_potential_order',
                                'potential_order_detail',
                                'visit_result',
                                'visit_status',
                                'notes as visit_notes',
                                'created_at'
                            ])
                            ->where('customer_id', $followUp->customer_id)
                            ->where(function($query) {
                                $query->whereNotNull('complaint_detail')  // ada complaint
                                    ->orWhere('has_potential_order', true); // ATAU ada potential order
                            })
                            ->orderByDesc('created_at')
                            ->get();
                                                    }

                            $followUp->complaint_details = $complaints;


                            /* ================= RETURN RESPONSE ================= */

                            return ApiResponse::success(
                                $followUp,
                                'Success Get Follow Up Detail'
                            );

                        } catch (\Throwable $e) {

                            return ApiResponse::error(
                                'Failed to get follow up detail',
                                config('app.debug') ? ['exception' => $e->getMessage()] : null,
                                500
                            );
                        }
                    }



            // code untuk menampilkan timeline aktivitas follow up (semua aktivitas dari semua follow up dalam 1 lead)
            public function timeline($id)
            {
                /*
                |--------------------------------------------------------------------------
                | Ambil Follow Up yang diklik
                |--------------------------------------------------------------------------
                */
                $followUp = DB::table('follow_ups')
                    ->select('id', 'lead_id', 'follow_up_code')
                    ->where('id', $id)
                    ->first();

                if (!$followUp) {
                    return response()->json([
                        'message' => 'Follow Up not found'
                    ], 404);
                }

                /*
                |--------------------------------------------------------------------------
                | Ambil semua follow-up dalam 1 lead (Journey Lead)
                |--------------------------------------------------------------------------
                */
                $followUps = DB::table('follow_ups')
                    ->where('lead_id', $followUp->lead_id)
                    ->orderBy('created_at', 'asc')
                    ->get(['id', 'follow_up_code']);

                $followUpIds = $followUps->pluck('id');


                

                /*
                |--------------------------------------------------------------------------
                | Ambil semua aktivitas dari seluruh follow-up tsb
                |--------------------------------------------------------------------------
                */
                $activities = DB::table('follow_up_activities as act')
                    ->join('follow_ups as fu', 'fu.id', '=', 'act.follow_up_id')
                    ->whereIn('act.follow_up_id', $followUpIds)
                    ->orderBy('act.activity_at', 'asc') // URUT BERDASARKAN WAKTU KEJADIAN
                    ->select([
                        'act.id',
                        'act.activity_type',
                        'act.title',
                        'act.description',
                        'act.activity_at',
                        'fu.follow_up_code',
                        'fu.id as follow_up_id'
                    ])
                    ->get();

                /*
                |--------------------------------------------------------------------------
                | Mapping → format siap pakai UI
                |--------------------------------------------------------------------------
                */
                $histories = $activities->map(function ($act) {

                    return [
                        'follow_up_code' => $act->follow_up_code,
                        'follow_up_id'   => $act->follow_up_id,

                        'activity'       => $act->title,
                        'description'    => $act->description,

                        'type'           => $act->activity_type,

                        // waktu asli (untuk sorting FE jika perlu)
                        'activity_raw'   => $act->activity_at,

                        // waktu formatted (untuk display)
                        'activity_at'    => Carbon::parse($act->activity_at)
                                                ->timezone('Asia/Jakarta')
                                                ->format('d M Y H:i'),
                    ];
                });

                /*
                |--------------------------------------------------------------------------
                | Response sebagai LEAD JOURNEY
                |--------------------------------------------------------------------------
                */
                return response()->json([
                    'data' => [
                        'lead_id'          => $followUp->lead_id,
                        'current_followup' => $followUp->follow_up_code,
                        'total_followups'  => $followUps->count(),
                        'histories'        => $histories,
                    ]
                ]);
            }



                // public function customerTimeline($id)
                //     {
                //         /*
                //         |--------------------------------------------------------------------------
                //         | Ambil Follow Up yang diklik
                //         |--------------------------------------------------------------------------
                //         */
                //         $followUp = DB::table('follow_ups')
                //             ->select('id', 'customer_id', 'follow_up_code')
                //             ->where('id', $id)
                //             ->first();

                //         if (!$followUp) {
                //             return response()->json(['message' => 'Follow Up not found'], 404);
                //         }

                //         $customerId = $followUp->customer_id;

                //         /*
                //         |--------------------------------------------------------------------------
                //         | Ambil semua follow-up milik customer ini
                //         |--------------------------------------------------------------------------
                //         */
                //         $followUps = DB::table('follow_ups')
                //             ->where('customer_id', $customerId)
                //             ->whereNull('deleted_at')
                //             ->orderBy('created_at', 'asc')
                //             ->get(['id', 'follow_up_code']);

                //         $followUpIds = $followUps->pluck('id');

                //         /*
                //         |--------------------------------------------------------------------------
                //         | 1. Follow Up Activities
                //         |--------------------------------------------------------------------------
                //         */
                //         $activities = DB::table('follow_up_activities as act')
                //             ->join('follow_ups as fu', 'fu.id', '=', 'act.follow_up_id')
                //             ->whereIn('act.follow_up_id', $followUpIds)
                //             ->select([
                //                 'act.id',
                //                 'act.activity_type as type',
                //                 'act.title',
                //                 'act.description',
                //                 'act.activity_at',
                //                 'fu.follow_up_code',
                //                 'fu.id as follow_up_id',
                //             ])
                //             ->get()
                //             ->map(fn($a) => [
                //                 'source'         => 'FOLLOW_UP',
                //                 'type'           => $a->type,
                //                 'follow_up_code' => $a->follow_up_code,
                //                 'follow_up_id'   => $a->follow_up_id,
                //                 'title'          => $a->title,
                //                 'description'    => $a->description,
                //                 'activity_raw'   => $a->activity_at,
                //                 'activity_at'    => Carbon::parse($a->activity_at)
                //                                         ->timezone('Asia/Jakarta')
                //                                         ->format('d M Y H:i'),
                //             ]);

                //                 /*
                //                 |--------------------------------------------------------------------------
                //                 | 2. Visit History (termasuk complaint & potential order)
                //                 |--------------------------------------------------------------------------
                //                 */
                //                 $visits = DB::table('visits')
                //                     ->where('customer_id', $customerId)
                //                     ->whereNull('deleted_at')
                //                     ->orderBy('visit_at', 'asc')
                //                     ->select([
                //                         'id',
                //                         'visit_code',
                //                         'visit_at',
                //                         'check_in_at',
                //                         'check_out_at',
                //                         'visit_status',
                //                         'visit_result',
                //                         'notes',
                //                         'has_complaint',
                //                         'complaint_detail',
                //                         'has_potential_order',
                //                         'potential_order_detail',
                //                     ])
                //                     ->get()
                //                     ->map(fn($v) => [
                //                         'source'                 => 'VISIT',
                //                         'type'                   => 'VISIT',
                //                         'visit_code'             => $v->visit_code,
                //                         'title'                  => 'Visit - ' . $v->visit_code,
                //                         'description'            => $v->notes ?? '-',
                //                         'visit_status'           => $v->visit_status,
                //                         'visit_result'           => $v->visit_result,
                //                         'check_in_at'            => $v->check_in_at
                //                                                         ? Carbon::parse($v->check_in_at)->timezone('Asia/Jakarta')->format('d M Y H:i')
                //                                                         : null,
                //                         'check_out_at'           => $v->check_out_at
                //                                                         ? Carbon::parse($v->check_out_at)->timezone('Asia/Jakarta')->format('d M Y H:i')
                //                                                         : null,
                //                         'has_complaint'          => (bool) $v->has_complaint,
                //                         'complaint_detail'       => $v->complaint_detail,
                //                         'has_potential_order'    => (bool) $v->has_potential_order,
                //                         'potential_order_detail' => $v->potential_order_detail,
                //                         'activity_raw'           => $v->visit_at,
                //                         'activity_at'            => Carbon::parse($v->visit_at)
                //                                                         ->timezone('Asia/Jakarta')
                //                                                         ->format('d M Y H:i'),
                //                     ]);

                //                 /*
                //                 |--------------------------------------------------------------------------
                //                 | Merge & Sort semua event berdasarkan waktu
                //                 |--------------------------------------------------------------------------
                //                 */
                //                 $histories = $activities
                //                     ->concat($visits)
                //                     ->sortBy('activity_raw')
                //                     ->values();

                //                 return response()->json([
                //                     'data' => [
                //                         'customer_id'     => $customerId,
                //                         'current_followup'=> $followUp->follow_up_code,
                //                         'total_followups' => $followUps->count(),
                //                         'total_visits'    => $visits->count(),
                //                         'histories'       => $histories,
                //                     ]
                //                 ]);
                //             }

                public function customerTimeline($id)
{
    /*
    |--------------------------------------------------------------------------
    | Ambil Follow Up yang diklik
    |--------------------------------------------------------------------------
    */
    $followUp = DB::table('follow_ups')
        ->select('id', 'customer_id', 'follow_up_code')
        ->where('id', $id)
        ->first();

    if (!$followUp) {
        return response()->json([
            'message' => 'Follow Up not found'
        ], 404);
    }

    $customerId = $followUp->customer_id;

    /*
    |--------------------------------------------------------------------------
    | Semua Follow Up Customer
    |--------------------------------------------------------------------------
    */
    $followUps = DB::table('follow_ups')
        ->where('customer_id', $customerId)
        ->whereNull('deleted_at')
        ->orderBy('created_at', 'asc')
        ->get([
            'id',
            'follow_up_code'
        ]);

    $followUpIds = $followUps->pluck('id');

    /*
    |--------------------------------------------------------------------------
    | Follow Up yang masih aktif
    |--------------------------------------------------------------------------
    */
    $openFollowUps = DB::table('follow_ups')
        ->where('customer_id', $customerId)
        ->whereNull('deleted_at')
        ->whereNotIn('status', [
            'DONE',
            'CLOSED',
            'CANCELLED'
        ])
        ->orderBy('follow_up_at', 'asc')
        ->get([
            'id',
            'follow_up_code',
            'subject',
            'status',
            'follow_up_at'
        ]);

    /*
    |--------------------------------------------------------------------------
    | Activity Follow Up
    |--------------------------------------------------------------------------
    */
    $activities = DB::table('follow_up_activities as act')
        ->join('follow_ups as fu', 'fu.id', '=', 'act.follow_up_id')
        ->whereIn('act.follow_up_id', $followUpIds)
        ->select([
            'act.id',
            'act.activity_type as type',
            'act.title',
            'act.description',
            'act.activity_at',
            'fu.follow_up_code',
            'fu.id as follow_up_id',
        ])
        ->get()
        ->map(function ($a) {

            return [
                'source'         => 'FOLLOW_UP',
                'type'           => $a->type,
                'follow_up_code' => $a->follow_up_code,
                'follow_up_id'   => $a->follow_up_id,
                'title'          => $a->title,
                'description'    => $a->description,
                'activity_raw'   => $a->activity_at,
                'activity_at'    => Carbon::parse($a->activity_at)
                                        ->timezone('Asia/Jakarta')
                                        ->format('d M Y H:i'),
            ];
        });

    /*
    |--------------------------------------------------------------------------
    | Visit History
    |--------------------------------------------------------------------------
    */
    $visits = DB::table('visits')
        ->where('customer_id', $customerId)
        ->whereNull('deleted_at')
        ->orderBy('visit_at', 'asc')
        ->select([
            'id',
            'visit_code',
            'visit_at',
            'check_in_at',
            'check_out_at',
            'visit_status',
            'visit_result',
            'notes',
            'has_complaint',
            'complaint_detail',
            'has_potential_order',
            'potential_order_detail',
        ])
        ->get()
        ->map(function ($v) {

            return [

                'source'                 => 'VISIT',
                'type'                   => 'VISIT',

                'visit_code'             => $v->visit_code,

                'title'                  => 'Visit - ' . $v->visit_code,
                'description'            => $v->notes ?? '-',

                'visit_status'           => $v->visit_status,
                'visit_result'           => $v->visit_result,

                'check_in_at' => $v->check_in_at
                    ? Carbon::parse($v->check_in_at)
                        ->timezone('Asia/Jakarta')
                        ->format('d M Y H:i')
                    : null,

                'check_out_at' => $v->check_out_at
                    ? Carbon::parse($v->check_out_at)
                        ->timezone('Asia/Jakarta')
                        ->format('d M Y H:i')
                    : null,

                'has_complaint'          => (bool) $v->has_complaint,
                'complaint_detail'       => $v->complaint_detail,

                'has_potential_order'    => (bool) $v->has_potential_order,
                'potential_order_detail' => $v->potential_order_detail,

                'activity_raw' => $v->visit_at,

                'activity_at' => Carbon::parse($v->visit_at)
                    ->timezone('Asia/Jakarta')
                    ->format('d M Y H:i'),

            ];
        });

    /*
    |--------------------------------------------------------------------------
    | Merge Timeline
    |--------------------------------------------------------------------------
    */
    $histories = $activities
        ->concat($visits)
        ->sortBy('activity_raw')
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */
    return response()->json([
        'data' => [

            'customer_id'      => $customerId,
            'current_followup' => $followUp->follow_up_code,

            'total_followups'  => $followUps->count(),
            'total_visits'     => $visits->count(),

            /*
            |--------------------------------------------------------------
            | Digunakan oleh Vue untuk panel "Follow Up Aktif"
            |--------------------------------------------------------------
            */
            'open_follow_ups'  => $openFollowUps,

            /*
            |--------------------------------------------------------------
            | Timeline Customer
            |--------------------------------------------------------------
            */
            'histories'        => $histories,

        ]
    ]);
}


            // code generate follow up code dengan format FUP-20260121-0001-XXXXXX (6 digit urutan + 6 digit random)
            public function generateFollowUpCode(): string
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

                    //code generate customer code
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




        // code untuk submit hasil follow up (baik DONE atau PENDING dengan jadwal lanjutan)
        public function submitResultFollowUp(Request $request, $follow_up_id)
        {
            $request->validate([
                'status'          => 'required|in:DONE,PENDING',
                'done_action'     => 'required_if:status,DONE|in:convert,failed',
                'follow_up_at'    => 'required_if:status,PENDING|date',
                'subject'         => 'nullable|string',
                'follow_up_type'  => 'nullable|string',
                'lead_category'   => 'nullable|in:potential_customers,consideration_stage,prospective_customers',
                'notes'           => 'nullable|string'
            ]);

            DB::beginTransaction();

            try {

                $followUp = DB::table('follow_ups')
                    ->lockForUpdate()
                    ->where('id', $follow_up_id)
                    ->first();

                if (!$followUp) {
                    throw new \Exception('Follow up tidak ditemukan');
                }

                $salesId = auth()->user()->id_user;// 

                /*
                |--------------------------------------------------------------------------
                |  CASE A : STATUS = PENDING (LANJUT FOLLOW-UP)
                |--------------------------------------------------------------------------
                */
                if ($request->status === 'PENDING') {

                    // 1️ Close Follow-Up Lama
                    DB::table('follow_ups')
                        ->where('id', $follow_up_id)
                        ->update([
                            'status'       => 'DONE',
                            'completed_at' => now(),
                            'notes'        => $request->notes,
                            'updated_at'   => now(),
                        ]);

                    // Activity EXECUTION (terjadi sekarang)
                    DB::table('follow_up_activities')->insert([
                        'follow_up_id'  => $follow_up_id,
                        'title'         => 'Follow Up Executed',
                        'description'   => 'Sales melakukan follow up dan membuat jadwal lanjutan',
                        'activity_type' => 'EXECUTED',
                        'activity_at'   => now(),
                        'created_by'    => $salesId,
                        'created_at'    => now(),
                    ]);

                    // 2️ Update Lead Progress
                    $leadUpdate = [
                        'last_contacted_at' => now(),
                        'updated_at'        => now(),
                    ];

                    if ($request->lead_category) {
                        $leadUpdate['lead_status'] = $request->lead_category;
                    }

                    DB::table('leads')
                        ->where('id', $followUp->lead_id)
                        ->update($leadUpdate);

                    // 3️ Create Follow-Up Baru
                    $newFollowUpId = DB::table('follow_ups')->insertGetId([
                        'follow_up_code' => $this->generateFollowUpCode(),
                        'lead_id'        => $followUp->lead_id,
                        'subject'        => $request->subject ?? $followUp->subject,
                        'follow_up_type' => $request->follow_up_type ?? $followUp->follow_up_type,
                        'follow_up_at'   => $request->follow_up_at,
                        'status'         => 'PENDING',
                        'created_by'     => $salesId,
                        'notes'          => $request->notes,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);

                    // CREATED activity (sekarang)
                    DB::table('follow_up_activities')->insert([
                        'follow_up_id'  => $newFollowUpId,
                        'title'         => 'Follow Up Created',
                        'description'   => 'Follow up lanjutan dibuat dari aktivitas sebelumnya',
                        'activity_type' => 'CREATED',
                        'activity_at'   => now(),
                        'created_by'    => $salesId,
                        'created_at'    => now(),
                    ]);

                    //  SCHEDULE activity (bukan future activity!)
                    DB::table('follow_up_activities')->insert([
                        'follow_up_id'  => $newFollowUpId,
                        'title'         => 'Next Follow Up Scheduled',
                        'description'   => 'Follow up dijadwalkan pada ' .
                            \Carbon\Carbon::parse($request->follow_up_at)->format('d M Y H:i'),
                        'activity_type' => 'SCHEDULED',

                        // activity terjadi SEKARANG
                        'activity_at'   => now(),

                        // waktu rencana asli
                        'scheduled_for' => $request->follow_up_at,

                        'created_by'    => $salesId,
                        'created_at'    => now(),
                    ]);

                    DB::commit();
                    return ApiResponse::success(null, "Follow Up lanjutan berhasil dijadwalkan");
                }

                /*
                |--------------------------------------------------------------------------
                |  CASE B : STATUS = DONE (FINAL)
                |--------------------------------------------------------------------------
                */

                DB::table('follow_ups')
                    ->where('id', $follow_up_id)
                    ->update([
                        'status'       => 'DONE',
                        'completed_at' => now(),
                        'notes'        => $request->notes,
                        'updated_at'   => now(),
                    ]);

                DB::table('follow_up_activities')->insert([
                    'follow_up_id'  => $follow_up_id,
                    'title'         => 'Follow Up Done',
                    'description'   => 'Follow up telah diselesaikan',
                    'activity_type' => 'EXECUTED',
                    'activity_at'   => now(),
                    'created_by'    => $salesId,
                    'created_at'    => now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | HANDLE RESULT
                |--------------------------------------------------------------------------
                */

                if ($request->done_action === 'convert') {

                    DB::table('leads')->where('id', $followUp->lead_id)->update([
                        'lead_status' => 'converted',
                        'updated_at'  => now(),
                    ]);

                    $lead = DB::table('leads')->where('id', $followUp->lead_id)->first();

                    DB::table('customers')->insert([
                        'customer_code'   => $this->generateCustomerCode(),
                        'lead_id'         => $lead->id,
                        'company_name'    => $lead->company_name,
                        'contact_name'    => $lead->contact_name,
                        'industry_id'     => $lead->industry_id,
                        'email'           => $lead->email,
                        'phone'           => $lead->phone,
                        'address'         => $lead->address,
                        'customer_status' => 'Active',
                        'converted_at'    => now(),
                        'created_by'      => $salesId,
                        'id_user'         => $salesId,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    DB::table('follow_up_activities')->insert([
                        'follow_up_id'  => $follow_up_id,
                        'title'         => 'Lead Converted',
                        'description'   => 'Lead berhasil dikonversi menjadi customer',
                        'activity_type' => 'LEAD_CONVERTED',
                        'activity_at'   => now(),
                        'created_by'    => $salesId,
                        'created_at'    => now(),
                    ]);

                } else {

                    DB::table('leads')->where('id', $followUp->lead_id)->update([
                        'lead_status' => 'failed',
                        'updated_at'  => now(),
                    ]);

                    DB::table('follow_up_activities')->insert([
                        'follow_up_id'  => $follow_up_id,
                        'title'         => 'Lead Failed',
                        'description'   => 'Lead dinyatakan gagal',
                        'activity_type' => 'LEAD_FAILED',
                        'activity_at'   => now(),
                        'created_by'    => $salesId,
                        'created_at'    => now(),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | CLOSE ALL OTHER OPEN FOLLOW UPS
                |--------------------------------------------------------------------------
                */

                DB::table('follow_ups')
                    ->where('lead_id', $followUp->lead_id)
                    ->whereNull('completed_at')
                    ->where('id', '!=', $follow_up_id)
                    ->update([
                        'status'       => 'DONE',
                        'completed_at' => now(),
                        'updated_at'   => now(),
                    ]);

                DB::commit();
                return ApiResponse::success(null, "Follow Up berhasil disimpan");

            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }




// code for create follow up langsung dari lead (tanpa visit) - ini untuk kebutuhan quick action di list leads
public function createDirectFollowUpFromLead(Request $request, $leadId)
{
    $request->validate([
        'subject'        => 'required|string',
        'follow_up_type' => 'required|string',
        'follow_up_at'   => 'required|date',
        'notes'          => 'nullable|string'
    ]);


    DB::beginTransaction();

    try {

        $salesId = auth()->user()->id_user;

        $lead = DB::table('leads')
            ->lockForUpdate()
            ->where('id', $leadId)
            ->first();

        if (!$lead) {
            throw new \Exception('Lead tidak ditemukan');
        }

        /*
        |------------------------------------------
        |  Cegah double open follow up
        |------------------------------------------
        */
        $existing = DB::table('follow_ups')
            ->where('lead_id', $leadId)
            ->whereNull('completed_at')
            ->exists();

        if ($existing) {
            throw new \Exception('Lead masih punya follow up aktif');
        }

        /*
        |------------------------------------------
        | CREATE FOLLOW UP PERTAMA
        |------------------------------------------
        */
        $followUpId = DB::table('follow_ups')->insertGetId([
            'follow_up_code' => $this->generateFollowUpCode(),
            'lead_id'        => $leadId,
            'subject'        => $request->subject,
            'follow_up_type' => $request->follow_up_type,
            'follow_up_at'   => $request->follow_up_at,
            'status'         => 'PENDING',
            'created_by'     => $salesId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        /*
        |------------------------------------------
        | TIMELINE CREATED
        |------------------------------------------
        */
        DB::table('follow_up_activities')->insert([
            'follow_up_id'  => $followUpId,
            'title'         => 'Follow Up Created',
            'description'   => 'Follow up dibuat langsung dari lead (tanpa visit)',
            'activity_type' => 'CREATED',
            'activity_at'   => now(),
            'created_by'    => $salesId,
            'created_at'    => now(),
        ]);

        /*
        |------------------------------------------
        | TIMELINE SCHEDULED
        |------------------------------------------
        */
        DB::table('follow_up_activities')->insert([
            'follow_up_id'  => $followUpId,
            'title'         => 'Follow Up Scheduled',
            'description'   => 'Follow up dijadwalkan pada ' .
             Carbon::parse($request->follow_up_at)->format('d M Y H:i'),
            'activity_type' => 'SCHEDULED',
            'activity_at'   => now(),
            'scheduled_for' => $request->follow_up_at,
            'created_by'    => $salesId,
            'created_at'    => now(),
        ]);

        /*
        |------------------------------------------
        | UPDATE LEAD STATUS
        |------------------------------------------
        */
        DB::table('leads')->where('id', $leadId)->update([
            'lead_status'       => 'No Visit (Direct)',
            'last_contacted_at' => now(),
            'updated_at'        => now(),
        ]);

        DB::commit();

        return ApiResponse::success(null, 'Direct Follow Up berhasil dibuat');
    } catch (\Throwable $e) {
        DB::rollBack();
        throw $e;
    }
}




// start code follow up bagian customer
   //ini code untuk submit result customer



 public function submitResultCustomers(SubmitFollowUpResultRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {

            $followUp = MsFollowUp::lockForUpdate()->findOrFail($id);

            if ($followUp->status === 'DONE') {
                return response()->json([
                    'message' => 'Follow up already closed'
                ], 422);
            }

            // 1️ Close Current Follow Up
            $followUp->update([
                'result'        => 'DONE',
                'notes'         => $request->notes,
                'status'        => 'DONE',
                'completed_at'  => now(),
                'closed_at'     => now(),
                'closed_reason' => $this->mapClosedReason($request->result)
            ]);

            //  Record Activity
            $this->recordActivity(
                $followUp,
                'RESULT_SUBMITTED',
                'Follow Up Completed',
                $request->notes
            );

            // 2️ Decision Engine
            $this->handleResultDecision($followUp, $request);

            return response()->json([
                'message' => 'Result submitted successfully'
            ]);
        });
    }

    protected function mapClosedReason(string $result): string
    {
        return match ($result) {
            'success'        => 'activity_completed',
            'need_followup'  => 'continued_to_next_followup',
            'reschedule'     => 'rescheduled',
            'no_meet'        => 'failed_to_meet',
            'dealing'        => 'moved_to_negotiation',
            'closed'         => 'converted_to_deal',
            'cancelled'      => 'opportunity_lost',
            default          => 'unknown'
        };
    }

    protected function handleResultDecision($followUp, $request)
    {
        switch ($request->result) {

            case 'need_followup':
                $this->recordActivity(
                    $followUp,
                    'NEED_FOLLOW_UP',
                    'Need Another Follow Up',
                    'Customer requires further follow up',
                    $request->next_follow_up_at
                );

                $this->createNextFollowUp($followUp, $request->next_follow_up_at);
                break;

            case 'reschedule':
                $this->recordActivity(
                    $followUp,
                    'RESCHEDULED',
                    'Follow Up Rescheduled',
                    'Rescheduled by sales',
                    $request->next_follow_up_at
                );

                $this->createNextFollowUp($followUp, $request->next_follow_up_at);
                break;

            case 'closed':
                $this->recordActivity(
                    $followUp,
                    'DEAL_CLOSED',
                    'Deal Closed Successfully',
                    'Customer converted to deal'
                );

                $this->closeAllOpenFollowUps($followUp);
                $this->markCustomerWon($followUp);
                break;

            case 'cancelled':
                $this->recordActivity(
                    $followUp,
                    'OPPORTUNITY_LOST',
                    'Opportunity Lost',
                    $request->notes
                );

                $this->cancelAllOpenFollowUps($followUp);
                break;

            case 'success':
            $this->recordActivity(
                $followUp,
                'SUCCESS',
                'Follow Up Diselesaikan',
                $request->notes ?? 'Aktivitas selesai, belum ada respons dari customer'
            );
            break;

        

        case 'no_meet':
            $this->recordActivity(
                $followUp,
                'NO_MEET',
                'Tidak Berhasil Bertemu Customer',
                $request->notes ?? 'Customer tidak dapat ditemui / dihubungi'
            );

            // Jika sales set next date → otomatis buat follow up baru
            if ($request->next_follow_up_at) {
                $this->createNextFollowUp($followUp, $request->next_follow_up_at);
            }
            break;


        case 'dealing':
            $this->recordActivity(
                $followUp,
                'DEALING',
                'Masuk Tahap Negosiasi',
                $request->notes ?? 'Sedang dalam proses pembahasan / penawaran'
            );

            // Wajib ada next follow up — karena negosiasi butuh tindak lanjut
            if ($request->next_follow_up_at) {
                $this->createNextFollowUp($followUp, $request->next_follow_up_at);
            }

            break;
        }
    }


    protected function createNextFollowUp($followUp, $date)
    {
        $newFollowUp = MsFollowUp::create([
            'follow_up_code' => $this->generateFollowUpCode(),
            'customer_id'    => $followUp->customer_id,
            'branch_id'      => $followUp->branch_id,
            'lead_id'        => $followUp->lead_id,
            'notes'          => $followUp->notes,
            'follow_up_at'   => $date,
            'subject'        => 'Auto Follow Up',
            'status'         => 'PENDING',
            'created_by'     => auth()->id(),
            'follow_up_type' => 'WHATSAPP'
        ]);

        // Log di follow up lama
        $this->recordActivity(
            $followUp,
            'NEXT_FOLLOW_UP_CREATED',
            'Next Follow Up Scheduled',
            'System created next follow up',
            $date
        );

        // Log di follow up baru
        $this->recordActivity(
            $newFollowUp,
            'FOLLOW_UP_CREATED',
            'New Follow Up Created',
            'Generated automatically',
            $date
        );
    }

    protected function closeAllOpenFollowUps($followUp)
    {
        $items = MsFollowUp::where('customer_id', $followUp->customer_id)
            ->where('status', 'open')
            ->get();

        foreach ($items as $item) {

            $item->update([
                'status' => 'done',
                'closed_at' => now(),
                'closed_reason' => 'auto_closed_after_deal'
            ]);

            $this->recordActivity(
                $item,
                'AUTO_CLOSED',
                'Follow Up Closed Automatically',
                'Closed because another deal was achieved'
            );
        }
    }

    protected function cancelAllOpenFollowUps($followUp)
    {
        $items = MsFollowUp::where('customer_id', $followUp->customer_id)
            ->where('status', 'open')
            ->get();

        foreach ($items as $item) {

            $item->update([
                'status' => 'cancelled',
                'closed_at' => now(),
                'closed_reason' => 'opportunity_lost'
            ]);

            $this->recordActivity(
                $item,
                'AUTO_CANCELLED',
                'Follow Up Cancelled',
                'Opportunity lost'
            );
        }
    }

    protected function markCustomerWon($followUp)
    {
        if ($followUp->customer) {
            $followUp->customer->update([
                'lifecycle_status' => 'active',
                'last_deal_at' => now()
            ]);
        }
    }

    /**
     * CODE UNTUK CENTRAL ACTIVITY LOGGER
     */
    protected function recordActivity(
        MsFollowUp $followUp,
        string $type,
        string $title,
        ?string $description = null,
        $scheduledFor = null
    ) {
        FollowUpActivity::create([
            'follow_up_id'  => $followUp->id,
            'activity_type' => $type,
            'title'         => $title,
            'description'   => $description,
            'activity_at'   => now(),
            'scheduled_for' => $scheduledFor,
            'created_by'    => auth()->id(),
        ]);
    }



    //  code untuk ambil data customer 
    function getCustomerForDirect(Request $request) {

                    {
                        $userId = auth()->user()->id_user;
                        $search = $request->get('search');

                        $query = DB::table('customers as c')
                            ->select([
                                'c.id',
                                'c.company_name',
                                'c.contact_name',
                            ])
                            ->where(function ($q) use ($userId) {
                                $q->where('c.created_by', $userId)
                                ->orWhere('c.assigned_to', $userId);
                            })
                            ->whereNull('c.deleted_at')
                            ->where('c.lead_status', 'New');

                        if ($search) {
                            $query->where(function ($q) use ($search) {
                                $q->where('c.company_name', 'ILIKE', "%{$search}%")
                                ->orWhere('c.contact_name', 'ILIKE', "%{$search}%");
                            });
                        }

                        return ApiResponse::success(
                            $query->orderBy('c.company_name')->limit(100)->get(),
                            'Success Get Customers For Direct Follow Up'
                        );
                    }
    }




    // code untuk  store direct follow up customer 
    //  public function storeDirectCustomer(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'customer_id'     => 'required|exists:customers,id',
    //             'branch_id'       => 'nullable|exists:customer_branches,id',
    //             'follow_up_type'  => 'required|in:CALL,EMAIL,WHATSAPP,MEETING,OTHER',
    //             'subject'         => 'required|string|max:255',
    //             'notes'           => 'required|string',
    //             'follow_up_at'    => 'required|date',
    //         ]);
    //          $salesId = auth()->user()->id_user;

    //         /**
    //          * CEK FOLLOW UP AKTIF
    //          */
    //             $existingFollowUp = MsFollowUp::where('customer_id', $validated['customer_id'])
    //             ->whereNotIn('status', ['CLOSED', 'DONE']) // yang BELUM selesai
    //             ->exists();

    //             if ($existingFollowUp) {
    //                 return ApiResponse::error(
    //                     'Customer masih memiliki follow up yang aktif'
    //                 );
    //             }

    //         /**
    //          * CREATE FOLLOW UP BARU
    //          */
    //         $followUp = MsFollowUp::create([
    //             'follow_up_code' => $this->generateFollowUpCode(),
    //             'lead_id'        => null,
    //             'customer_id'    => $validated['customer_id'],
    //             'branch_id'      => $validated['branch_id'] ?? null,
    //             'follow_up_type' => $validated['follow_up_type'], // bukan 'CALL'
    //             'subject'        => $validated['subject'],
    //             'notes'          => $validated['notes'],
    //             'follow_up_at'   => $validated['follow_up_at'],
    //             'status'         => 'PENDING',
    //             // 'assigned_to'    => $user->id,
    //             'created_by'     => $salesId,
    //         ]);

    //         // Tambah ini
    //             $this->recordActivity(
    //                 $followUp,
    //                 'FOLLOW_UP_CREATED (DIRECT FOLLOW UP)',
    //                 'Direct Follow Up Dibuat',
    //                 $validated['notes'],
    //                 $validated['follow_up_at']
    //             );

    //         return ApiResponse::success($followUp, 'Follow up berhasil dibuat');

    //     } catch (\Exception $e) {

    //         return ApiResponse::error($e->getMessage());
    //     }
    // }
    // end code follow up bagian customer


    public function storeDirectCustomer(Request $request)
{
    try {
        $validated = $request->validate([
            'customer_id'     => 'required|exists:customers,id',
            'branch_id'       => 'nullable|exists:customer_branches,id',
            'follow_up_type'  => 'required|in:CALL,EMAIL,WHATSAPP,MEETING,OTHER',
            'subject'         => 'required|string|max:255',
            'notes'           => 'required|string',
            'follow_up_at'    => 'required|date',
        ]);
        $salesId = auth()->user()->id_user;

        // ── DIHAPUS: block "Customer masih memiliki follow up yang aktif" ──
        // Sales sekarang boleh punya banyak follow up aktif sekaligus
        // untuk customer yang sama (beda urusan/beda waktu kunjungan).
        // Penutupan follow up lama sepenuhnya manual lewat tombol
        // "Close" di journey/timeline (lihat closeFollowUpManually).

        $followUp = MsFollowUp::create([
            'follow_up_code' => $this->generateFollowUpCode(),
            'lead_id'        => null,
            'customer_id'    => $validated['customer_id'],
            'branch_id'      => $validated['branch_id'] ?? null,
            'follow_up_type' => $validated['follow_up_type'],
            'subject'        => $validated['subject'],
            'notes'          => $validated['notes'],
            'follow_up_at'   => $validated['follow_up_at'],
            'status'         => 'PENDING',
            'created_by'     => $salesId,
        ]);

        $this->recordActivity(
            $followUp,
            'FOLLOW_UP_CREATED (DIRECT FOLLOW UP)',
            'Direct Follow Up Dibuat',
            $validated['notes'],
            $validated['follow_up_at']
        );

        return ApiResponse::success($followUp, 'Follow up berhasil dibuat');

    } catch (\Exception $e) {
        return ApiResponse::error($e->getMessage());
    }
}


// code untuk sales menutup follow up secara manual dari journey/timeline
// (menggantikan auto-close sistem — keputusan penuh di tangan sales)
public function closeFollowUpManually(Request $request, $id)
{
    $request->validate([
        'closed_reason' => 'nullable|string|max:255',
    ]);

    $userId = auth()->user()->id_user;

    DB::beginTransaction();

    try {
        $followUp = DB::table('follow_ups')
            ->where('id', $id)
            ->where('created_by', $userId)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if (!$followUp) {
            DB::rollBack();
            return ApiResponse::error('Follow up not found or access denied', null, 404);
        }

        if (in_array($followUp->status, ['DONE', 'CLOSED', 'CANCELLED'])) {
            DB::rollBack();
            return ApiResponse::error('Follow up ini sudah tertutup', null, 422);
        }

        DB::table('follow_ups')
            ->where('id', $id)
            ->update([
                'status'        => 'CLOSED',
                'completed_at'  => now(),
                'closed_at'     => now(),
                'closed_reason' => $request->closed_reason ?? 'closed_manually_by_sales',
                'updated_at'    => now(),
            ]);

        DB::table('follow_up_activities')->insert([
            'follow_up_id'  => $id,
            'title'         => 'Follow Up Closed (Manual)',
            'description'   => $request->closed_reason ?? 'Ditutup manual oleh sales',
            'activity_type' => 'MANUAL_CLOSED',
            'activity_at'   => now(),
            'created_by'    => $userId,
            'created_at'    => now(),
        ]);

        DB::commit();

        return ApiResponse::success(null, 'Follow up berhasil ditutup');

    } catch (\Throwable $e) {
        DB::rollBack();
        return ApiResponse::error(
            'Failed to close follow up',
            config('app.debug') ? ['exception' => $e->getMessage()] : null,
            500
        );
    }
}

}