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
use App\Helpers\ApiResponse;
Use App\Http\Requests\FollowUpValidationIndex;
use App\Http\Requests\FollowUpValidationRequest;
use App\Http\Requests\FollowUpValidationUpdate;
use App\Http\Resources\FollowUpLeadResourcesCollection;
use App\Http\Resources\FollowUpLeadResources;
use App\Http\Resources\FollowUpCustomerResourcesCollection;
use App\Http\Resources\FollowUpCustomerResources;
use Carbon\Carbon;



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


            //code untuk menampilkan list follow up berdasarkan customer (dengan fitur search, filter tanggal, sorting, pagination, dan computed column overdue)
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
                        'follow_ups.customer_id',
                        'follow_ups.follow_up_type',
                        'follow_ups.subject',
                        'follow_ups.notes',
                        'follow_ups.follow_up_at',
                        'follow_ups.status',
                        'follow_ups.created_at',
                        'follow_ups.follow_up_code',

                        'c.company_name as customer_company_name',
                        'c.customer_status',

                        'sales.fullname as sales_name',
                     

                        DB::raw("CASE WHEN {$overdueCondition} THEN 1 ELSE 0 END as is_overdue"),
                        DB::raw("CASE WHEN {$overdueCondition} THEN 'OVERDUE' ELSE follow_ups.status END as computed_status"),
                    ])
                    ->join('customers as c', 'c.id', '=', 'follow_ups.customer_id')
                    ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'follow_ups.created_by')
                    ->where('follow_ups.created_by', $user->id_user)
                    ->whereNotNull('follow_ups.customer_id')
                    ->whereNull('follow_ups.lead_id')
                    ->whereNull('follow_ups.deleted_at');

                /* ================= SEARCH ================= */
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('follow_ups.follow_up_code', 'ILIKE', "%{$search}%")
                        ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
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

                        // ❗ hanya follow up yang masih aktif
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




            // codeuntuk update kemungkinan ada banyak perubahan
            public function updateFollowUp(FollowUpValidationUpdate $request,$id) {
                $data = $request->validated();
                $userId = auth()->user()->id_user;

                try {
                    // Ambil follow up (pastikan milik sales login)
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
                    DB::table('follow_ups')
                ->where('id', $id)
                ->update([
                    'follow_up_at' => $data['follow_up_at'],
                    'notes'        => $data['notes'] ?? null,
                    'subject'       => $data['subject'] ?? null,
                    'updated_at'   => now(),
                ]);


                
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




                // code untuk menampilkan detail follow up (beserta info lead, customer, dan computed column overdue)
                public function showFollowUp($id)
                {
                    $user = auth()->user();

                    try {

                        /* ================= OVERDUE LOGIC (SAMA SEPERTI LIST) ================= */
                        $overdueCondition = "
                            fu.follow_up_at < NOW()
                            AND fu.status = 'PENDING'
                        ";

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
                                'fu.created_at',
                                'fu.updated_at',
                                

                                // Lead (SAMA DENGAN LIST)
                                'l.company_name as lead_company_name',
                                'l.contact_name as lead_contact_name',
                                'l.lead_status',

                                // Customer (optional, kalau ada)
                                'c.company_name as customer_company_name',
                                'c.contact_name as customer_contact_name',
                                'c.customer_status as customer_status',

                                // Sales
                                'sales.fullname as sales_name',

                                // computed column (WAJIB SAMA)
                                DB::raw("CASE WHEN {$overdueCondition} THEN 1 ELSE 0 END as is_overdue"),
                                DB::raw("CASE WHEN {$overdueCondition} THEN 'OVERDUE' ELSE fu.status END as computed_status"),
                            ])
                            ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
                            ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
                            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'fu.created_by')

                            // ownership guard (SAMA)
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

                        return ApiResponse::success(
                            new FollowUpLeadResources($followUp),
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
                | 🔵 CASE A : STATUS = PENDING (LANJUT FOLLOW-UP)
                |--------------------------------------------------------------------------
                */
                if ($request->status === 'PENDING') {

                    // 1️⃣ Close Follow-Up Lama
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

                    // 2️⃣ Update Lead Progress
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

                    // 3️⃣ Create Follow-Up Baru
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

                    // 🔥 SCHEDULE activity (bukan future activity!)
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
                | 🟢 CASE B : STATUS = DONE (FINAL)
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
        | ❗ Cegah double open follow up
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



}