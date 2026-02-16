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


//      public function followUpSales(FollowUpValidationIndex $request)
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


            public function storeFollowUp(FollowUpValidationRequest $request)
            {
                $data = $request->validated();
                $userId = auth()->user()->id_user;

                try {
                    DB::beginTransaction();
                 $followUpCode = $this->generateFollowUpCode();
                    /* ================= INSERT FOLLOW UP ================= */
                $followUpId = DB::table('follow_ups')->insertGetId([
                'lead_id'        => $data['lead_id'] ?? null,
                'customer_id'    => $data['customer_id'] ?? null,
                'follow_up_code' => $followUpCode,
                'subject'          => $data['subject'] ?? null,
                'follow_up_at'   => $data['follow_up_at'], 
                'follow_up_type' => $data['follow_up_type'],
                'notes'          => $data['notes'] ?? null,

                'created_by'     => $userId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);


        /* ================= AMBIL DATA ================= */
        $followUp = DB::table('follow_ups as fu')
            ->select([
                'fu.*',

                'l.company_name as lead_company_name',
                'c.company_name as customer_company_name',
                'l.lead_status',

                'sales.fullname as sales_name',
            ])
            ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
            ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'fu.created_by')
            ->where('fu.id', $followUpId)
            ->first();

        DB::commit();

        return ApiResponse::success(
            new FollowUpLeadResources($followUp),
            'Success Create Follow Up',
            201
        );

    } catch (\Throwable $e) {
        DB::rollBack();

        return ApiResponse::error(
            'Failed to create follow up',
            config('app.debug') ? ['exception' => $e->getMessage()] : null,
            500
        );
    }
}


public function getLeadsNeedFollowUp(Request $request)
{
    $userId = auth()->user()->id_user;

    $query = DB::table('leads as l')
        ->leftJoin('follow_ups as f', function ($join) {
            $join->on('f.lead_id', '=', 'l.id')
                 ->whereNull('f.deleted_at');
        })

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
                    WHEN f.id IS NULL THEN 'NO_SCHEDULE'
                    WHEN f.follow_up_at < NOW() THEN 'OVERDUE'
                    WHEN f.follow_up_at BETWEEN NOW() AND NOW() + INTERVAL '1 DAY' THEN 'DUE_SOON'
                    ELSE 'SCHEDULED'
                END as urgency_status
            "),

            DB::raw("
                CASE
                    WHEN f.follow_up_at IS NULL THEN NULL
                    ELSE EXTRACT(EPOCH FROM (f.follow_up_at - NOW()))
                END as seconds_remaining
            ")
        ])

        ->where(function ($q) use ($userId) {
            $q->where('l.created_by', $userId)
              ->orWhere('l.assigned_to', $userId);
        })

        ->whereNull('l.converted_at')
        ->whereNull('l.deleted_at')
        

           ->where(function ($q) {
    $q->whereNull('l.lead_status')
      ->orWhere('l.lead_status', '!=', 'failed');
})

// ->where(function ($q) {
//     $q->whereNull('f.id')
//       ->orWhere('f.status', 'New'); // ✅ ini fix utama
// })

        ->where(function ($q) {
            $q->whereNull('f.id')
              ->orWhereNotIn('f.status', ['DONE', 'CANCEL']);
        })

        ->orderByRaw("COALESCE(f.follow_up_at, l.created_at) ASC");

    $data = $query->limit(50)->get()->map(function ($item) {

        if (!$item->seconds_remaining) {
            $item->time_remaining_text = 'Data is created directly for follow-up purposes.';
            $item->follow_up_at_formatted = null;
            return $item;
        }

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

        $item->follow_up_at_formatted = $item->follow_up_at
            ? Carbon::parse($item->follow_up_at)->format('d M Y H:i')
            : null;

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
        'status'       => $data['status'] ?? null,
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




public function deleteFollowUp($id)
{
    $userId = auth()->user()->id_user;

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | 0️⃣ Validate Follow Up Ownership
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
        | 1️⃣ HARD DELETE activities (no soft delete)
        |--------------------------------------------------------------------------
        */
        DB::table('follow_up_activities')
            ->where('follow_up_id', $id)
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | 2️⃣ HARD DELETE follow up
        |--------------------------------------------------------------------------
        */
        DB::table('follow_ups')
            ->where('id', $id)
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Ambil semua visits untuk hapus FILE dulu
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
        | 4️⃣ HARD DELETE visits (setelah file aman dihapus)
        |--------------------------------------------------------------------------
        */
        DB::table('visits')
            ->where('lead_id', $followUp->lead_id)
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | 5️⃣ Reset Lead ke kondisi awal
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




public function timeline($id)
{
    // Ambil header follow up
    $followUp = DB::table('follow_ups')
        ->select('id', 'follow_up_code')
        ->where('id', $id)
        ->first();

    if (!$followUp) {
        return response()->json([
            'message' => 'Follow Up not found'
        ], 404);
    }

    // Ambil history activity
    $activities = DB::table('follow_up_activities')
        ->where('follow_up_id', $id)
        ->orderBy('activity_at', 'asc')
        ->get()
        ->map(function ($act) {
            return [
                'activity'     => $act->title,
                'description'  => $act->description,
                'activity_at'  => Carbon::parse($act->activity_at)->format('d M Y H:i'),
                'type'         => $act->activity_type,
            ];
        });

    return response()->json([
        'data' => [
            'follow_up_code' => $followUp->follow_up_code,
            'histories'      => $activities,
        ]
    ]);
}

}
