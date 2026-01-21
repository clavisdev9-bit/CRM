<?php

namespace App\Http\Controllers\Api\Users\Sales\FollowUp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MsFollowUp;
use App\Models\MsLeadsModel;
use App\Models\MsCustomers;
use App\Helpers\ApiResponse;
Use App\Http\Requests\FollowUpValidationIndex;
use App\Http\Requests\FollowUpValidationRequest;
use App\Http\Requests\FollowUpValidationUpdate;
use App\Http\Resources\FollowUpResources;
use App\Http\Resources\FollowUpResourcesCollection;


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


     public function followUpSales(FollowUpValidationIndex $request)
{
    $validated = $request->validated();
    $user = auth()->user();

    $search    = $validated['search'] ?? null;
    $perPage   = $validated['per_page'] ?? 10;
    // UBAH: dari follow_up_date menjadi follow_up_at
    $sortBy    = $validated['sort_by'] ?? 'follow_up_at'; 
    $sortDir   = $validated['sort_dir'] ?? 'desc';
    $startDate = $validated['start_date'] ?? null;
    $endDate   = $validated['end_date'] ?? null;

    $query = $this->MsFollowUp->query()
        ->select([
            'follow_ups.*',
            'l.company_name as lead_company_name',
            'l.contact_name as lead_contact_name',
            'c.company_name as customer_company_name',
            'c.contact_name as customer_contact_name',
            'sales.fullname as sales_name',
        ])
        ->leftJoin('leads as l', 'l.id', '=', 'follow_ups.lead_id')
        ->leftJoin('customers as c', 'c.id', '=', 'follow_ups.customer_id')
        ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'follow_ups.created_by')
        ->where('follow_ups.created_by', $user->id_user);

    /* ================= SEARCH ================= */
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('follow_ups.notes', 'ILIKE', "%{$search}%")
              ->orWhere('l.company_name', 'ILIKE', "%{$search}%")
              ->orWhere('c.company_name', 'ILIKE', "%{$search}%");
        });
    }

    /* ================= DATE FILTER ================= */
    // UBAH: Pastikan kolom di whereDate juga follow_up_at
    if ($startDate) {
        $query->whereDate('follow_ups.follow_up_at', '>=', $startDate);
    }

    if ($endDate) {
        $query->whereDate('follow_ups.follow_up_at', '<=', $endDate);
    }

    /* ================= SORT ================= */
    // Tambahkan prefix tabel agar tidak ambigu
    $query->orderBy("follow_ups.{$sortBy}", $sortDir);

    $results = $query->paginate($perPage);

    /* ================= RESPONSE ================= */
    $message = $results->isEmpty() ? "Data tidak ditemukan" : "Success Get Follow Up Sales";

    return ApiResponse::success(
        new FollowUpResourcesCollection($results),
        $message
    );
}


public function storeFollowUp(FollowUpValidationRequest $request)
{
    $data = $request->validated();
    $userId = auth()->user()->id_user;

    try {
        DB::beginTransaction();

        /* ================= INSERT FOLLOW UP ================= */
       $followUpId = DB::table('follow_ups')->insertGetId([
    'lead_id'        => $data['lead_id'] ?? null,
    'customer_id'    => $data['customer_id'] ?? null,

    'follow_up_at'   => $data['follow_up_date'], // ⬅️ FIX DI SINI
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

                'sales.fullname as sales_name',
            ])
            ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
            ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'fu.created_by')
            ->where('fu.id', $followUpId)
            ->first();

        DB::commit();

        return ApiResponse::success(
            new FollowUpResources($followUp),
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




public function getLeadsBySales(Request $request)
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
        ->whereNull('l.deleted_at');

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('l.company_name', 'ILIKE', "%{$search}%")
              ->orWhere('l.contact_name', 'ILIKE', "%{$search}%");
        });
    }

    return ApiResponse::success(
        $query->orderBy('l.company_name')->limit(20)->get(),
        'Success Get Leads By Sales'
    );
}



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

        // Update
        DB::table('follow_ups')
            ->where('id', $id)
            ->update([
                'follow_up_at'   => $data['follow_up_date'], // mapping
                'follow_up_type' => $data['follow_up_type'],
                'notes'          => $data['notes'] ?? null,
                'updated_at'     => now(),
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

    try {
        // Pastikan follow up milik sales login
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

        // Soft delete
        DB::table('follow_ups')
            ->where('id', $id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return ApiResponse::success(
            null,
            'Success Delete Follow Up'
        );

    } catch (\Throwable $e) {
        return ApiResponse::error(
            'Failed to delete follow up',
            config('app.debug') ? ['exception' => $e->getMessage()] : null,
            500
        );
    }
}




public function showFollowUp($id)
{
    $userId = auth()->user()->id_user;

    try {
        $followUp = DB::table('follow_ups as fu')
            ->select([
                'fu.*',

                // Lead
                'l.company_name as lead_company_name',
                'l.contact_name as lead_contact_name',

                // Customer
                'c.company_name as customer_company_name',
                'c.contact_name as customer_contact_name',

                // Sales
                'sales.fullname as sales_name',
            ])
            ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
            ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'fu.created_by')
            ->where('fu.id', $id)
            ->where('fu.created_by', $userId)
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



}
