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

    public function __construct(
        VisitsModel $VisitsModel,
        MsCustomers $MsCustomers,
        MsLeadsModel $MsLeadsModel,
        MsFollowUp $MsFollowUp
    ) {
        $this->VisitsModel = $VisitsModel;
        $this->MsCustomers = $MsCustomers;
        $this->MsLeadsModel = $MsLeadsModel;
        $this->MsFollowUp = $MsFollowUp;
    }

    // untuk target map
    public function getVisitTargetMap(Request $request)
    {
        // Default hari ini, bisa dioverride via query param
        $dateFrom = $request->input('date_from', today()->toDateString());
        $dateTo   = $request->input('date_to', today()->toDateString());

        $query = DB::table('visits as v')
            ->select([
                'v.id',
                'v.visit_code',
                'v.no_reference',
                'v.visit_at',
                'v.check_in_at',
                'v.check_out_at',

                DB::raw("CAST(v.latitude AS DECIMAL(10,7)) as latitude"),
                DB::raw("CAST(v.longitude AS DECIMAL(10,7)) as longitude"),
                'v.gps_snapshot',
                'v.lead_id',
                'v.customer_id',
                'v.branch_id',

                // ── info branch mentah (opsional dipakai frontend) ──
                'b.branch_code',
                'b.branch_name',
                'b.city as branch_city',

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

                // ══════════════════════════════════════════════════════════
                // BARU — DETAIL HASIL VISIT (foto, catatan, hasil kunjungan,
                // komplain, potensi order, durasi, file check-out).
                // Dipakai frontend untuk modal detail kunjungan di Live
                // Field Tracker supaya tidak perlu hit endpoint lain lagi.
                // ══════════════════════════════════════════════════════════
                'v.photo',
                DB::raw("
                    CASE
                        WHEN v.photo IS NOT NULL AND v.photo != ''
                            THEN CONCAT('" . asset('storage') . "/', v.photo)
                        ELSE NULL
                    END as photo_url
                "),

                'v.check_out_file',
                DB::raw("
                    CASE
                        WHEN v.check_out_file IS NOT NULL AND v.check_out_file != ''
                            THEN CONCAT('" . asset('storage') . "/', v.check_out_file)
                        ELSE NULL
                    END as check_out_file_url
                "),

                'v.notes',
                'v.visit_result',
                'v.customer_response',

                'v.has_complaint',
                'v.complaint_detail',

                'v.has_potential_order',
                'v.potential_order_detail',

                // Durasi (PostgreSQL) — dari jadwal visit ke check-in,
                // dari check-in ke check-out, dan total durasi kunjungan.
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

                // ══════════════════════════════════════════════════════════
                // BARU — JADWAL & CATATAN KUNJUNGAN SELANJUTNYA (follow up)
                // Diambil dari tabel follow_ups yang dibuat otomatis saat
                // sales check-out (lihat checkOutVisit/checkOutCustomer).
                // Relasi lewat follow_ups.visit_id -> visits.id.
                // ══════════════════════════════════════════════════════════
                'fu.follow_up_at as next_visit_at',
                'fu.notes as next_visit_notes',
                'fu.follow_up_type as next_visit_type',
                'fu.status as next_visit_status',
                // ══════════════════════════════════════════════════════════
                // END BARU
                // ══════════════════════════════════════════════════════════

                // ── target_type: LEAD tetap LEAD, tapi CUSTOMER dipecah BRANCH vs HEAD_OFFICE ──
                DB::raw("
                    CASE
                        WHEN v.lead_id IS NOT NULL THEN 'LEAD'
                        WHEN v.branch_id IS NOT NULL THEN 'BRANCH'
                        WHEN v.customer_id IS NOT NULL THEN 'HEAD_OFFICE'
                        ELSE 'UNKNOWN'
                    END as target_type
                "),

                // ── target_name: kalau ke branch, tampilkan nama cabang + company induk ──
                DB::raw("
                    CASE
                        WHEN v.lead_id IS NOT NULL THEN l.company_name
                        WHEN v.branch_id IS NOT NULL THEN CONCAT(c.company_name, ' - ', b.branch_name)
                        WHEN v.customer_id IS NOT NULL THEN c.company_name
                        ELSE NULL
                    END as target_name
                "),

                // ── nama company induk terpisah, berguna kalau frontend mau tampilkan badge/label sendiri ──
                DB::raw("
                    CASE
                        WHEN v.customer_id IS NOT NULL THEN c.company_name
                        ELSE NULL
                    END as parent_company_name
                "),

                // ── target_contact: prioritas branch > lead/customer ──
                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN b.contact_name
                        WHEN v.lead_id IS NOT NULL THEN l.contact_name
                        WHEN v.customer_id IS NOT NULL THEN c.contact_name
                        ELSE NULL
                    END as target_contact
                "),

                // ── BARU: target_phone — prioritas branch > lead/customer ──
                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN b.phone
                        WHEN v.lead_id IS NOT NULL THEN l.phone
                        WHEN v.customer_id IS NOT NULL THEN c.phone
                        ELSE NULL
                    END as target_phone
                "),

                // ── BARU: target_email — branch belum tentu punya kolom email sendiri
                // (kontak cabang biasanya di tabel branch_contacts), jadi untuk branch
                // sementara NULL. Kalau customer_branches memang punya kolom email,
                // tinggal tambahkan baris "WHEN v.branch_id IS NOT NULL THEN b.email". ──
                DB::raw("
                    CASE
                        WHEN v.lead_id IS NOT NULL THEN l.email
                        WHEN v.customer_id IS NOT NULL THEN c.email
                        ELSE NULL
                    END as target_email
                "),

                // ── target_address: prioritas branch > lead/customer ──
                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN b.address
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
            ->leftJoin('customer_branches as b', 'b.id', '=', 'v.branch_id')

            ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')

            // BARU: follow up (jadwal & catatan kunjungan selanjutnya) yang
            // dibuat otomatis dari visit ini saat check-out. Filter
            // deleted_at ditaruh langsung di kondisi JOIN (bukan WHERE)
            // supaya visit yang belum/tidak punya follow up tetap muncul.
            ->leftJoin('follow_ups as fu', function ($join) {
                $join->on('fu.visit_id', '=', 'v.id')
                     ->whereNull('fu.deleted_at');
            })

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

            // ── SOFT DELETE ──
            ->whereNull('v.deleted_at')
            ->whereNull('l.deleted_at')
            ->whereNull('c.deleted_at')
            // branch soft-deleted harus di-exclude, TAPI jangan buang baris
            // yang branch_id-nya memang NULL (visit ke LEAD/HEAD_OFFICE)
            ->where(function ($q) {
                $q->whereNull('v.branch_id')
                  ->orWhereNull('b.deleted_at');
            })

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
                'v.branch_id',
                'v.sales_id',

                DB::raw("
                    COALESCE(l.company_name, c.company_name) as company_name
                "),

                // ── info branch (kalau visit ke cabang) ──
                'b.branch_code',
                'b.branch_name',
                'b.city as branch_city',

                DB::raw("
                    CASE
                        WHEN v.customer_id IS NOT NULL THEN 'CUSTOMER'
                        ELSE 'LEAD'
                    END as visit_type
                "),

                // ── target_type: bedakan HQ vs BRANCH khusus visit customer ──
                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN 'BRANCH'
                        WHEN v.customer_id IS NOT NULL THEN 'HEAD_OFFICE'
                        ELSE NULL
                    END as target_type
                "),

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

                // ── target_contact/phone/address: prioritas BRANCH > CUSTOMER > LEAD ──
                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN b.contact_name
                        WHEN v.lead_id IS NOT NULL THEN l.contact_name
                        WHEN v.customer_id IS NOT NULL THEN c.contact_name
                        ELSE NULL
                    END as target_contact
                "),

                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN b.phone
                        WHEN v.lead_id IS NOT NULL THEN l.phone
                        WHEN v.customer_id IS NOT NULL THEN c.phone
                        ELSE NULL
                    END as target_phone
                "),

                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN b.address
                        WHEN v.lead_id IS NOT NULL THEN l.address
                        WHEN v.customer_id IS NOT NULL THEN c.address
                        ELSE NULL
                    END as target_address
                "),

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

                DB::raw("
                    CASE
                        WHEN v.photo IS NOT NULL AND v.photo != ''
                            THEN CONCAT('" . asset('storage') . "/', v.photo)
                        ELSE NULL
                    END as photo_url
                "),

                'v.check_out_at',
                'v.latitude',
                'v.longitude',
                'v.gps_snapshot',
                'v.photo',
                'v.notes',
                'v.no_reference',
                'v.visit_result',
                'v.visit_status',
                'v.customer_response',
                'v.created_by',
                'v.created_at',
                'v.updated_at',
            ])

            ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->leftJoin('customer_branches as b', 'b.id', '=', 'v.branch_id')
            ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')

            ->where(function ($q) {
                $q->whereNotNull('v.lead_id')
                  ->orWhereNotNull('v.customer_id');
            })

            ->where(function ($q) use ($userId) {
                $q->where('v.created_by', $userId)
                  ->orWhere('v.sales_id', $userId);
            })

            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('l.company_name', 'ILIKE', "%{$search}%")
                       ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
                       ->orWhere('b.branch_name', 'ILIKE', "%{$search}%")
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
                'v.branch_id',
                'v.sales_id',

                DB::raw("
                    COALESCE(l.company_name, c.company_name) as company_name
                "),

                'b.branch_code',
                'b.branch_name',
                'b.city as branch_city',

                DB::raw("
                    CASE
                        WHEN v.customer_id IS NOT NULL THEN 'CUSTOMER'
                        ELSE 'LEAD'
                    END as visit_type
                "),

                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN 'BRANCH'
                        WHEN v.customer_id IS NOT NULL THEN 'HEAD_OFFICE'
                        ELSE NULL
                    END as target_type
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

                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN b.contact_name
                        WHEN v.lead_id IS NOT NULL THEN l.contact_name
                        WHEN v.customer_id IS NOT NULL THEN c.contact_name
                        ELSE NULL
                    END as target_contact
                "),

                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN b.phone
                        WHEN v.lead_id IS NOT NULL THEN l.phone
                        WHEN v.customer_id IS NOT NULL THEN c.phone
                        ELSE NULL
                    END as target_phone
                "),

                DB::raw("
                    CASE
                        WHEN v.branch_id IS NOT NULL THEN b.address
                        WHEN v.lead_id IS NOT NULL THEN l.address
                        WHEN v.customer_id IS NOT NULL THEN c.address
                        ELSE NULL
                    END as target_address
                "),

                'v.latitude',
                'v.longitude',
                'v.gps_snapshot',
                'v.photo',
                'v.check_out_file',
                'v.no_reference',
                'v.notes',
                'v.visit_result',
                'v.visit_status',
                'v.customer_response',
                'v.has_complaint',
                'v.complaint_detail',
                'v.has_potential_order',
                'v.potential_order_detail',
                'v.created_at',
                'v.updated_at',
            ])
            ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->leftJoin('customer_branches as b', 'b.id', '=', 'v.branch_id')
            ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')

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

    public function VisitCustomers(VisitCustomerDataIndex $request)
    {
        $validated = $request->validated();

        $search  = $validated['search'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 10);
        $page    = (int) ($validated['page'] ?? 1);

        $allowedSort = ['company_name', 'created_at', 'converted_at'];
        $sortByKey   = $validated['sort_by'] ?? 'created_at';
        $sortBy      = in_array($sortByKey, $allowedSort) ? $sortByKey : 'created_at';

        $sortDirInput = $validated['sort_dir'] ?? 'desc';
        $sortDir = in_array($sortDirInput, ['asc', 'desc']) ? $sortDirInput : 'desc';

        $userId = auth()->user()->id_user;

        /**
         * ──────────────────────────────────────────────────────────
         * QUERY 1: TARGET = HEAD OFFICE (customers)
         * Hanya customer yang di-handle langsung oleh user (bukan
         * lewat cabang), supaya sales cabang tidak melihat head
         * office-nya sebagai target visit.
         * ──────────────────────────────────────────────────────────
         */
        $customerContactSub = DB::table('customer_contacts as cc')
            ->select(
                'cc.customer_id',
                DB::raw("
                    json_agg(
                        json_build_object(
                            'id', cc.id,
                            'name', cc.name,
                            'position', cc.position,
                            'email', cc.email,
                            'phone', cc.phone,
                            'is_primary', cc.is_primary
                        )
                        ORDER BY cc.is_primary DESC, cc.id ASC
                    ) as contacts
                ")
            )
            ->whereNull('cc.deleted_at')
            ->groupBy('cc.customer_id');

        $customerQuery = DB::table('customers as c')
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

                // ── GEOLOKASI (Phase 1/3) — dipakai frontend buat nge-cek
                // apakah target ini udah punya titik lokasi sebelum
                // ngaktifin tombol "Visit Now" (lihat hasCoordinates() di
                // SalesVisitData.vue) ──
                'c.latitude',
                'c.longitude',
                'c.radius_meter',

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

                DB::raw("'customer' as target_type"),
                DB::raw('NULL::bigint as branch_id'),
                DB::raw('NULL::text as branch_code'),
                DB::raw('NULL::text as branch_name'),
                DB::raw('NULL::text as city'),

                DB::raw("COALESCE(cc.contacts, '[]'::json) as contacts"),
            ])
            ->leftJoin('lead_categories as cat', 'cat.id', '=', 'c.lead_category_id')
            ->leftJoin('lead_industries as ind', 'ind.id', '=', 'c.industry_id')
            ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'c.id_user')
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'c.assigned_to')
            ->leftJoinSub($customerContactSub, 'cc', 'cc.customer_id', '=', 'c.id')
            ->leftJoinSub(
                DB::table('visits')
                    ->select('id', 'customer_id', 'visit_status', 'check_in_at')
                    ->whereNull('branch_id') // visit ke head office
                    ->whereIn('visit_status', ['ONGOING', 'CHECKED_IN']),
                'v',
                'v.customer_id', '=', 'c.id'
            )
            ->whereNull('c.deleted_at')
            ->where(function ($q) use ($userId) {
                $q->where('c.created_by', $userId)
                  ->orWhere('c.assigned_to', $userId);
            })
            ->whereIn('c.customer_status', ['Active', 'Dormant'])
            ->where('c.approval_status', 'approved');

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
         * QUERY 2: TARGET = BRANCH (customer_branches)
         * Hanya branch yang di-handle langsung oleh user.
         * ──────────────────────────────────────────────────────────
         */
        $branchContactSub = DB::table('branch_contacts as bc')
            ->select(
                'bc.branch_id',
                DB::raw("
                    json_agg(
                        json_build_object(
                            'id', bc.id,
                            'name', bc.name,
                            'position', bc.position,
                            'email', bc.email,
                            'phone', bc.phone,
                            'is_primary', bc.is_primary
                        )
                        ORDER BY bc.is_primary DESC, bc.id ASC
                    ) as contacts
                ")
            )
            ->whereNull('bc.deleted_at')
            ->groupBy('bc.branch_id');

        $branchQuery = DB::table('customer_branches as b')
            ->select([
                'c.id',
                'c.customer_code',
                'c.company_name',
                'b.contact_name',
                'b.email',
                'b.phone',

                DB::raw('NULL::integer as lead_id'),
                DB::raw('NULL::integer as lead_category_id'),
                DB::raw('NULL::integer as industry_id'),
                DB::raw('NULL::integer as id_user'),

                'b.assigned_to',
                'b.created_by',

                DB::raw('NULL::text as lead_source'),
                'b.status as customer_status',
                DB::raw("'PRIVATE' as visibility_type"),
                'b.notes',
                'b.address',

                // ── GEOLOKASI CABANG (Phase 3) — kolomnya sudah ada sejak
                // migration add_geolocation_to_customer_branches_table,
                // urutan & tipe HARUS sinkron sama customerQuery di atas
                // karena dua-duanya digabung pakai unionAll() ──
                'b.latitude',
                'b.longitude',
                'b.radius_meter',

                DB::raw('NULL::timestamp as converted_at'),
                'b.created_at',
                'b.updated_at',

                DB::raw('NULL::text as category_name'),
                DB::raw('NULL::text as industry_name'),
                'owner.fullname as owner_name',
                'sales.fullname as assigned_name',

                'v.id as active_visit_id',
                'v.visit_status as visit_status',
                'v.check_in_at as active_check_in_at',

                DB::raw("'branch' as target_type"),
                'b.id as branch_id',
                'b.branch_code',
                'b.branch_name',
                'b.city',

                DB::raw("COALESCE(bc.contacts, '[]'::json) as contacts"),
            ])
            ->join('customers as c', 'c.id', '=', 'b.customer_id')
            ->leftJoin('ms_users as owner', 'owner.id_user', '=', 'b.created_by')
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'b.assigned_to')
            ->leftJoinSub($branchContactSub, 'bc', 'bc.branch_id', '=', 'b.id')
            ->leftJoinSub(
                DB::table('visits')
                    ->select('id', 'branch_id', 'visit_status', 'check_in_at')
                    ->whereNotNull('branch_id') // visit ke branch
                    ->whereIn('visit_status', ['ONGOING', 'CHECKED_IN']),
                'v',
                'v.branch_id', '=', 'b.id'
            )
            ->whereNull('b.deleted_at')
            ->where('b.approval_status', 'approved')
            ->where(function ($q) use ($userId) {
                $q->where('b.created_by', $userId)
                  ->orWhere('b.assigned_to', $userId);
            })
            ->whereIn('b.status', ['Active', 'Dormant']);

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
        $sortColumn = $sortBy === 'company_name' ? 'company_name' : $sortBy;

        $results = $customerQuery
            ->unionAll($branchQuery)
            ->orderBy($sortColumn, $sortDir)
            ->paginate($perPage, ['*'], 'page', $page);

        $results->setCollection(
            $results->getCollection()->map(function ($item) {
                $item->contacts = is_string($item->contacts)
                    ? json_decode($item->contacts, true)
                    : ($item->contacts ?? []);
                return $item;
            })
        );

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

                    $followUpId = DB::table('follow_ups')->insertGetId([
                        'lead_id'        => $lead->id,
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

    public function startVisitCustomer(Request $request, $customersId)
    {
        $request->validate([
            'branch_id' => 'nullable|integer|exists:customer_branches,id',
        ]);

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
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Masih ada visit yang sedang berjalan atau belum check out'
                ], 422);
            }

            $branchId = $request->branch_id;

            // ── Kalau branch_id dikirim, pastikan branch itu benar
            // milik customer ini (bukan customer lain) ──
            if ($branchId) {
                $branchValid = DB::table('customer_branches')
                    ->where('id', $branchId)
                    ->where('customer_id', $customersId)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$branchValid) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Branch tidak ditemukan atau bukan milik customer ini.'
                    ], 422);
                }
            }

            // Insert visit baru
            $visit = VisitsModel::create([
                'visit_code'   => VisitsModel::generateVisitCode(),
                'sales_id'     => $salesId,
                'customer_id'  => $customersId,
                'branch_id'    => $branchId, // null = HQ, terisi = branch
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

        // ── PHASE 3: CEK KOORDINAT & RADIUS (SEBELUM upload foto/nyimpen apapun) ──
        // Konsepnya DISAMAIN antara HEAD COMPANY customer (branch_id null)
        // dan CABANG (branch_id terisi) -- dua-duanya sekarang WAJIB punya
        // titik lokasi sendiri (latitude/longitude/radius_meter), dan
        // dua-duanya divalidasi jaraknya dengan cara yang sama persis.
        if ($visit->branch_id) {
            $location = DB::table('customer_branches')
                ->where('id', $visit->branch_id)
                ->select('latitude', 'longitude', 'radius_meter')
                ->first();
            $targetLabel = 'cabang';
        } else {
            $location = DB::table('customers')
                ->where('id', $visit->customer_id)
                ->select('latitude', 'longitude', 'radius_meter')
                ->first();
            $targetLabel = 'customer';
        }

        // ── WAJIB PUNYA KOORDINAT ──
        // Belum punya lat/lng keisi (alamat belum jelas / belum di-geocode /
        // belum diisi manual) -- BLOK check-in total, sales harus minta
        // Manager/Admin lengkapi dulu titik lokasinya di Customer Master
        // sebelum bisa check-in ke sini.
        if (!$location || $location->latitude === null || $location->longitude === null) {
            return response()->json([
                'success'              => false,
                'missing_coordinates'  => true,
                'message'              => ($visit->branch_id ? 'Cabang customer ini' : 'Customer ini')
                    . ' belum punya titik lokasi (Latitude/Longitude). '
                    . 'Lengkapi dulu data lokasinya di Customer Master sebelum bisa Check In.',
            ], 422); // 422 -- data belum lengkap, beda sama 500 (error)
        }

        $radiusMeter = $location->radius_meter ?? 100;

        $distanceMeter = $this->calculateDistanceMeters(
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $location->latitude,
            (float) $location->longitude
        );

        $isOutsideRadius = $distanceMeter > $radiusMeter;

        // ── DI LUAR RADIUS = TOLAK TOTAL, GAK ADA KONFIRMASI ──
        // Beda dari versi sebelumnya (masih bisa "confirm tetap checkin"
        // kalau di luar radius) -- sekarang GPS sales WAJIB berada di dalam
        // radius yang diizinkan buat bisa check-in, gak ada jalan pintas
        // lewat konfirmasi manual lagi.
        if ($isOutsideRadius) {
            return response()->json([
                'success'         => false,
                'outside_radius'  => true,
                'distance_meter'  => round($distanceMeter, 1),
                'radius_meter'    => (int) $radiusMeter,
                'message'         => 'Check-in ditolak: lokasi Anda berada '
                    . round($distanceMeter) . ' meter dari lokasi ' . $targetLabel . ' '
                    . '(radius yang diizinkan: ' . $radiusMeter . ' meter). '
                    . 'Pastikan Anda berada di lokasi yang benar sebelum check-in.',
            ], 422); // 422 -- hard block, bukan lagi 409 "butuh konfirmasi"
        }

        DB::beginTransaction();
        try {
            // upload photo
            $path = $request->file('photo')->store('visits/checkin', 'public');

            $visit->update([
                'check_in_at'            => now(),
                'latitude'               => $request->latitude,
                'longitude'              => $request->longitude,
                'gps_snapshot'           => $request->gps_snapshot,
                'photo'                  => $path,
                'visit_status'           => 'CHECKED_IN',

                // ── PHASE 2/3 ──
                // is_outside_radius akan selalu FALSE di titik ini karena
                // kasus TRUE sudah di-block & return duluan di atas -- tetap
                // disimpan biar histori/laporan Manager konsisten formatnya.
                'is_outside_radius'      => $isOutsideRadius,
                'distance_meter'         => round($distanceMeter, 2),
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

    /**
     * Hitung jarak antara 2 titik koordinat (meter) pakai formula Haversine
     * -- standar buat jarak "garis lurus" di permukaan bumi, cukup akurat
     * buat kebutuhan radius check-in (bukan jarak jalan/rute).
     */
    private function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMeters = 6371000;

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusMeters * $c;
    }

    public function checkOutCustomer(Request $request, $visitId)
    {
        $commonRules = [
            'no_reference'           => 'required|string|max:100',
            'check_out_file'         => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif|max:10240',
            'notes'                  => 'required|string',
            'customer_response'      => 'required|string',
            'has_complaint'          => 'nullable|boolean',
            'complaint_detail'       => 'nullable|string',
            'has_potential_order'    => 'nullable|boolean',
            'potential_order_detail' => 'nullable|string',
            'follow_up_at'           => 'required|date|after:today',
            'follow_up_notes'        => 'nullable|string',
            'follow_up_type'         => 'required|in:CALL,EMAIL,WHATSAPP,MEETING,VISIT,OTHER',
        ];

        // Request lama tetap didukung. Request baru memakai results[] untuk multi-reference.
        if ($request->has('results')) {
            $rules = ['results' => 'required|array|min:1'];
            foreach ($commonRules as $field => $rule) {
                $rules['results.*.' . $field] = $rule;
            }
            $validatedResults = $request->validate($rules)['results'];
        } else {
            $validatedResults = [$request->validate($commonRules)];
        }

        foreach ($validatedResults as $index => $result) {
            $hasComplaint = filter_var($result['has_complaint'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $hasPotentialOrder = filter_var($result['has_potential_order'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($hasComplaint && empty($result['complaint_detail'])) {
                return response()->json([
                    'message' => "Complaint detail wajib diisi pada result ke-" . ($index + 1) . '.',
                ], 422);
            }

            if ($hasPotentialOrder && empty($result['potential_order_detail'])) {
                return response()->json([
                    'message' => "Potential order detail wajib diisi pada result ke-" . ($index + 1) . '.',
                ], 422);
            }
        }

        $userId = auth()->user()->id_user;

        try {
            $savedVisits = DB::transaction(function () use ($request, $visitId, $userId, $validatedResults) {
                // Penting: checkout hanya boleh dilakukan oleh sales pemilik visit.
                $visit = VisitsModel::where('id', $visitId)
                    ->where('sales_id', $userId)
                    ->where('visit_status', 'CHECKED_IN')
                    ->lockForUpdate()
                    ->first();

                if (!$visit) {
                    throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                        'message' => 'Visit tidak ditemukan, bukan milik Anda, atau belum check in.',
                    ], 422));
                }

                $checkedOutAt = now();
                $savedVisits = [];

                foreach ($validatedResults as $index => $result) {
                    $hasComplaint = filter_var($result['has_complaint'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $hasPotentialOrder = filter_var($result['has_potential_order'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $checkOutFilePath = null;

                    if (!empty($result['check_out_file'])) {
                        $checkOutFilePath = $result['check_out_file']
                            ->store('visits/checkout/files', 'public');
                    }

                    $checkoutData = [
                        'check_out_at'           => $checkedOutAt,
                        'no_reference'           => $result['no_reference'],
                        'check_out_file'         => $checkOutFilePath,
                        'notes'                  => $result['notes'],
                        'customer_response'      => $result['customer_response'],
                        'visit_result'           => $result['customer_response'],
                        'has_complaint'          => $hasComplaint,
                        'complaint_detail'       => $hasComplaint ? $result['complaint_detail'] : null,
                        'has_potential_order'    => $hasPotentialOrder,
                        'potential_order_detail' => $hasPotentialOrder ? $result['potential_order_detail'] : null,
                        'visit_status'           => 'DONE',
                    ];

                    if ($index === 0) {
                        // Result pertama melengkapi visit asli yang sudah check in.
                        $visit->update($checkoutData);
                        $completedVisit = $visit->fresh();
                    } else {
                        // Result berikutnya menjadi visit baru, tetapi menyalin bukti kunjungan fisik yang sama.
                        $completedVisit = VisitsModel::create(array_merge([
                            'visit_code'   => VisitsModel::generateVisitCode(),
                            'lead_id'      => null,
                            'customer_id'  => $visit->customer_id,
                            'branch_id'    => $visit->branch_id,
                            'sales_id'     => $visit->sales_id,
                            'visit_at'     => $visit->visit_at,
                            'check_in_at'  => $visit->check_in_at,
                            'latitude'     => $visit->latitude,
                            'longitude'    => $visit->longitude,
                            'gps_snapshot' => $visit->gps_snapshot,
                            'photo'        => $visit->photo,
                            'created_by'   => $userId,
                        ], $checkoutData));
                    }

                    $followUp = MsFollowUp::create([
                        'follow_up_code' => $this->generateFollowUpCode(),
                        'customer_id'    => $completedVisit->customer_id,
                        'branch_id'      => $completedVisit->branch_id,
                        'visit_id'       => $completedVisit->id,   // <-- TAMBAHKAN INI
                        'follow_up_type' => $result['follow_up_type'],
                        'subject'        => sprintf(
                            '(Follow Up) Tindak lanjut visit %s (Nomor Ref: %s)',
                            $completedVisit->visit_code,
                            $result['no_reference']
                        ),
                        'notes'          => $result['follow_up_notes'] ?? null,
                        'follow_up_at'   => $result['follow_up_at'],
                        'status'         => 'PENDING',
                        'assigned_to'    => $completedVisit->sales_id,
                        'created_by'     => $userId,
                    ]);

                    DB::table('follow_up_activities')->insert([
                        'follow_up_id'  => $followUp->id,
                        'title'         => 'Follow Up berhasil dibuat berdasarkan hasil kunjungan Customer',
                        'description'   => 'Follow Up dibuat otomatis setelah visit ' . $completedVisit->visit_code,
                        'activity_type' => 'CREATE',
                        'scheduled_for' => $result['follow_up_at'],
                        'activity_at'   => now(),
                        'created_at'    => now(),
                        'created_by'    => $userId,
                    ]);

                    $savedVisits[] = $completedVisit;
                }

                return $savedVisits;
            });

            return response()->json([
                'message' => count($savedVisits) . ' visit berhasil di-check out dan follow up dibuat.',
                'data'    => $savedVisits,
            ]);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            // Response 4xx dari dalam transaction (misalnya visit tidak valid) jangan diubah menjadi 500.
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Checkout failed.',
            ], 500);
        }
    }

}