<?php

namespace App\Http\Controllers\Api\Home\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{

public function homeStats()
{
    $totalLeads = DB::table('leads')
        ->whereNull('deleted_at')
        ->count();

    $totalCustomers = DB::table('customers')
        ->whereNull('deleted_at')
        ->count();

    $visitsToday = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereDate('visit_at', Carbon::today())
        ->count();

    $dealsClosed = DB::table('follow_ups')
        ->whereNull('deleted_at')
        ->where('result', 'DEAL')
        ->count();

    return ApiResponse::success([
        'total_leads'     => $totalLeads,
        'total_customers' => $totalCustomers,
        'visits_today'    => $visitsToday,
        'deals_closed'    => $dealsClosed,
    ], 'Success');
}




   /*
    |--------------------------------------------------------------------------
    | 1. SUMMARY CARDS
    |--------------------------------------------------------------------------
    */
   public function summary(Request $request)
{
    $month = $request->input('month', Carbon::now()->format('Y-m'));

    try {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    } catch (\Exception $e) {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();
    }

    // ✅ Ikut filter bulan
    $totalLeads = DB::table('leads')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    // ✅ Ikut filter bulan
    $totalCustomers = DB::table('customers')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    // ✅ Visit di bulan yang dipilih (bukan hari ini)
    $visitsToday = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->whereDate('visit_at', Carbon::today())
        ->count();

    $visitsThisMonth = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->count();

    $visitsLastMonth = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [
            $start->copy()->subMonth()->startOfMonth(),
            $start->copy()->subMonth()->endOfMonth(),
        ])
        ->count();

    $visitGrowth = $visitsLastMonth > 0
        ? round((($visitsThisMonth - $visitsLastMonth) / $visitsLastMonth) * 100, 1)
        : 0;

    // ✅ Sales aktif di bulan yang dipilih
    $activeSalesToday = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->distinct('sales_id')
        ->count('sales_id');

    return ApiResponse::success([
        'total_leads'        => $totalLeads,
        'total_customers'    => $totalCustomers,
        'visits_today'       => $visitsToday,
        'visits_this_month'  => $visitsThisMonth,
        'visits_last_month'  => $visitsLastMonth,
        'visit_growth'       => $visitGrowth,
        'active_sales_today' => $activeSalesToday,
    ], 'Success');
}
    /*
    |--------------------------------------------------------------------------
    | 2. VISIT CHART (daily / weekly / monthly)
    |--------------------------------------------------------------------------
    */
    public function visitChart(Request $request)
{
    $period = $request->input('period', 'daily');
    $month  = $request->input('month', Carbon::now()->format('Y-m'));

    try {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    } catch (\Exception $e) {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();
    }

    switch ($period) {
        case 'weekly':
            $data = DB::select("
                SELECT
                    TO_CHAR(DATE_TRUNC('week', visit_at), 'DD Mon') as label,
                    COUNT(*) as total,
                    SUM(CASE WHEN check_out_at IS NOT NULL THEN 1 ELSE 0 END) as done,
                    SUM(CASE WHEN check_in_at IS NOT NULL AND check_out_at IS NULL THEN 1 ELSE 0 END) as ongoing,
                    SUM(CASE WHEN check_in_at IS NULL THEN 1 ELSE 0 END) as planned
                FROM visits
                WHERE deleted_at IS NULL
                    AND visit_at BETWEEN ? AND ?
                GROUP BY DATE_TRUNC('week', visit_at)
                ORDER BY DATE_TRUNC('week', visit_at) ASC
            ", [$start, $end]);
            break;

        case 'monthly':
            $data = DB::select("
                SELECT
                    TO_CHAR(DATE_TRUNC('month', visit_at), 'Mon YYYY') as label,
                    COUNT(*) as total,
                    SUM(CASE WHEN check_out_at IS NOT NULL THEN 1 ELSE 0 END) as done,
                    SUM(CASE WHEN check_in_at IS NOT NULL AND check_out_at IS NULL THEN 1 ELSE 0 END) as ongoing,
                    SUM(CASE WHEN check_in_at IS NULL THEN 1 ELSE 0 END) as planned
                FROM visits
                WHERE deleted_at IS NULL
                    AND visit_at BETWEEN ? AND ?
                GROUP BY DATE_TRUNC('month', visit_at)
                ORDER BY DATE_TRUNC('month', visit_at) ASC
            ", [$start, $end]);
            break;

        default: // daily
            $data = DB::select("
                SELECT
                    TO_CHAR(visit_at::date, 'DD Mon') as label,
                    COUNT(*) as total,
                    SUM(CASE WHEN check_out_at IS NOT NULL THEN 1 ELSE 0 END) as done,
                    SUM(CASE WHEN check_in_at IS NOT NULL AND check_out_at IS NULL THEN 1 ELSE 0 END) as ongoing,
                    SUM(CASE WHEN check_in_at IS NULL THEN 1 ELSE 0 END) as planned
                FROM visits
                WHERE deleted_at IS NULL
                    AND visit_at BETWEEN ? AND ?
                GROUP BY visit_at::date
                ORDER BY visit_at::date ASC
            ", [$start, $end]);
            break;
    }

    return ApiResponse::success([
        'period'  => $period,
        'labels'  => array_column($data, 'label'),
        'total'   => array_map('intval', array_column($data, 'total')),
        'done'    => array_map('intval', array_column($data, 'done')),
        'ongoing' => array_map('intval', array_column($data, 'ongoing')),
        'planned' => array_map('intval', array_column($data, 'planned')),
    ], 'Success');
}

    /*
    |--------------------------------------------------------------------------
    | 3. TOP SALES PERFORMANCE
    |--------------------------------------------------------------------------
    */
    public function topSales(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $data = DB::table('visits as v')
            ->select([
                'u.id_user as sales_id',
                'u.fullname as sales_name',
                'u.image as sales_photo',
                DB::raw("COUNT(v.id) as total_visits"),
                DB::raw("SUM(CASE WHEN v.check_out_at IS NOT NULL THEN 1 ELSE 0 END) as done"),
                DB::raw("SUM(CASE WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NULL THEN 1 ELSE 0 END) as ongoing"),
                DB::raw("SUM(CASE WHEN v.check_in_at IS NULL THEN 1 ELSE 0 END) as planned"),
                DB::raw("SUM(CASE WHEN v.customer_id IS NOT NULL THEN 1 ELSE 0 END) as customer_visits"),
                DB::raw("SUM(CASE WHEN v.lead_id IS NOT NULL THEN 1 ELSE 0 END) as lead_visits"),
                DB::raw("
                    CASE
                        WHEN COUNT(v.id) > 0
                        THEN ROUND(
                            SUM(CASE WHEN v.check_out_at IS NOT NULL THEN 1 ELSE 0 END)::numeric
                            / COUNT(v.id) * 100, 1
                        )
                        ELSE 0
                    END as completion_rate
                "),
            ])
            ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')
            ->whereNull('v.deleted_at')
            ->whereBetween('v.visit_at', [$start, $end])
            ->groupBy('u.id_user', 'u.fullname', 'u.image')
            ->orderBy('total_visits', 'desc')
            ->limit(10)
            ->get();

        $data = $data->map(function ($item) {
            $item->sales_photo_url = $item->sales_photo
                ? asset('storage/users/' . $item->sales_photo)
                : asset('storage/users/default.png');
            return $item;
        });

        return ApiResponse::success($data, 'Success');
    }

    /*
    |--------------------------------------------------------------------------
    | 4. CONVERSION RATE (Lead → Customer)
    |--------------------------------------------------------------------------
    */
    // public function conversionRate(Request $request)
    // {
    //     $month = $request->input('month', Carbon::now()->format('Y-m'));
    //     $start = Carbon::parse($month)->startOfMonth();
    //     $end   = Carbon::parse($month)->endOfMonth();

    //     // Total leads aktif
    //     $totalLeads = DB::table('leads')
    //         ->whereNull('deleted_at')
    //         ->count();

      

    //     // Total leads yang dibuat di bulan tersebut
    //     $totalLeads = DB::table('leads')
    //         ->whereNull('deleted_at')
    //         ->whereBetween('created_at', [$start, $end]) // 👈 filter bulan
    //         ->count();

    //     // Total yang converted di bulan tersebut
    //     $totalConverted = DB::table('leads')
    //         ->whereNull('deleted_at')
    //         ->whereNotNull('converted_at')
    //         ->whereBetween('converted_at', [$start, $end]) // 👈 filter bulan
    //         ->count();

    //     $conversionRate = $totalLeads > 0
    //         ? round(($totalConverted / $totalLeads) * 100, 1)
    //         : 0;

    //             // Data per bulan (6 bulan terakhir)
    //             $monthlyData = DB::select("
    //                 SELECT
    //                     TO_CHAR(DATE_TRUNC('month', converted_at), 'Mon YYYY') as label,
    //                     COUNT(*) as converted
    //                 FROM leads
    //                 WHERE deleted_at IS NULL
    //                     AND converted_at IS NOT NULL
    //                     AND converted_at >= NOW() - INTERVAL '6 months'
    //                 GROUP BY DATE_TRUNC('month', converted_at)
    //                 ORDER BY DATE_TRUNC('month', converted_at) ASC
    //             ");

    //             return ApiResponse::success([
    //                 'total_leads'          => $totalLeads,
    //                 'total_converted'      => $totalConverted,
    //                 'converted_this_month' => $convertedThisMonth,
    //                 'conversion_rate'      => $conversionRate,
    //                 'monthly_labels'       => array_column($monthlyData, 'label'),
    //                 'monthly_converted'    => array_map('intval', array_column($monthlyData, 'converted')),
    //             ], 'Success');
    //         }
    public function conversionRate(Request $request)
{
    $month = $request->input('month', Carbon::now()->format('Y-m'));

    try {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    } catch (\Exception $e) {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();
    }

    // Total leads yang dibuat di bulan ini
    $totalLeads = DB::table('leads')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    // Dari leads bulan ini, yang sudah converted (kapanpun converted-nya)
    $totalConverted = DB::table('leads')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->whereNotNull('converted_at')
        ->count();

    // Yang belum converted
    $totalNotConverted = $totalLeads - $totalConverted;

    // Rate
    $conversionRate = $totalLeads > 0
        ? round(($totalConverted / $totalLeads) * 100, 1)
        : 0;

    // Tren 6 bulan terakhir (berapa leads per bulan yang akhirnya converted)
    $monthlyData = DB::select("
        SELECT
            TO_CHAR(DATE_TRUNC('month', created_at), 'Mon YYYY') as label,
            COUNT(*) as total_leads,
            SUM(CASE WHEN converted_at IS NOT NULL THEN 1 ELSE 0 END) as converted,
            CASE
                WHEN COUNT(*) > 0
                THEN ROUND(
                    SUM(CASE WHEN converted_at IS NOT NULL THEN 1 ELSE 0 END)::numeric
                    / COUNT(*) * 100, 1
                )
                ELSE 0
            END as rate
        FROM leads
        WHERE deleted_at IS NULL
            AND created_at >= NOW() - INTERVAL '6 months'
        GROUP BY DATE_TRUNC('month', created_at)
        ORDER BY DATE_TRUNC('month', created_at) ASC
    ");

    return ApiResponse::success([
        'total_leads'       => $totalLeads,
        'total_converted'   => $totalConverted,
        'total_not_converted' => $totalNotConverted,
        'converted_this_month' => $totalConverted, // leads bulan ini yang converted
        'conversion_rate'   => $conversionRate,
        'monthly_labels'    => array_column($monthlyData, 'label'),
        'monthly_converted' => array_map('intval', array_column($monthlyData, 'converted')),
        'monthly_rates'     => array_map('floatval', array_column($monthlyData, 'rate')),
    ], 'Success');
}

    /*
    |--------------------------------------------------------------------------
    | 5. VISIT STATUS SUMMARY (untuk pie/donut chart)
    |--------------------------------------------------------------------------
    */
    public function visitStatus(Request $request)
    {
        $dateFrom = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo   = $request->input('date_to', Carbon::today()->toDateString());

        $data = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [
                $dateFrom . ' 00:00:00',
                $dateTo   . ' 23:59:59',
            ])
            ->select([
                DB::raw("SUM(CASE WHEN check_in_at IS NULL THEN 1 ELSE 0 END) as planned"),
                DB::raw("SUM(CASE WHEN check_in_at IS NOT NULL AND check_out_at IS NULL THEN 1 ELSE 0 END) as ongoing"),
                DB::raw("SUM(CASE WHEN check_out_at IS NOT NULL THEN 1 ELSE 0 END) as done"),
                DB::raw("SUM(CASE WHEN lead_id IS NOT NULL AND customer_id IS NULL THEN 1 ELSE 0 END) as lead_visits"),
                DB::raw("SUM(CASE WHEN customer_id IS NOT NULL THEN 1 ELSE 0 END) as customer_visits"),
            ])
            ->first();

        return ApiResponse::success([
            'planned'         => (int) $data->planned,
            'ongoing'         => (int) $data->ongoing,
            'done'            => (int) $data->done,
            'lead_visits'     => (int) $data->lead_visits,
            'customer_visits' => (int) $data->customer_visits,
            'total'           => (int) $data->planned + (int) $data->ongoing + (int) $data->done,
        ], 'Success');
    }

    /*
    |--------------------------------------------------------------------------
    | 6. RECENT ACTIVITY
    |--------------------------------------------------------------------------
    */
    public function recentActivity(Request $request)
{
    $month = $request->input('month', Carbon::now()->format('Y-m'));

    try {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    } catch (\Exception $e) {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();
    }

    $data = DB::table('visits as v')
        ->select([
            'v.id',
            'v.visit_code',
            'v.visit_at',
            'v.check_in_at',
            'v.check_out_at',
            'u.fullname as sales_name',
            'u.image as sales_photo',
            DB::raw("COALESCE(l.company_name, c.company_name) as company_name"),
            DB::raw("
                CASE
                    WHEN v.customer_id IS NOT NULL THEN 'CUSTOMER'
                    ELSE 'LEAD'
                END as visit_type
            "),
            DB::raw("
                CASE
                    WHEN v.check_in_at IS NULL THEN 'PLANNED'
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NULL THEN 'ONGOING'
                    WHEN v.check_out_at IS NOT NULL THEN 'DONE'
                END as visit_progress
            "),
            DB::raw("
                CASE
                    WHEN u.image IS NOT NULL AND u.image != ''
                        THEN CONCAT('" . asset('storage/users') . "/', u.image)
                    ELSE '" . asset('storage/users/default.png') . "'
                END as sales_photo_url
            "),
        ])
        ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
        ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
        ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end]) // 👈 filter bulan
        ->orderBy('v.visit_at', 'desc')
        ->limit(10)
        ->get();

    return ApiResponse::success($data, 'Success');
}


public function conversionRateCustomers(Request $request)
{
    $month = $request->input('month', Carbon::now()->format('Y-m'));

    try {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    } catch (\Exception $e) {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();
    }

    $totalCustomers = DB::table('customers')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    $totalDeal = DB::table('customers as c')
        ->join('follow_ups as f', 'f.customer_id', '=', 'c.id')
        ->whereNull('c.deleted_at')
        ->whereNull('f.deleted_at')
        ->where('f.result', 'DEAL')
        ->whereBetween('c.created_at', [$start, $end])
        ->distinct('c.id')
        ->count('c.id');

    $conversionRate = $totalCustomers > 0
        ? round(($totalDeal / $totalCustomers) * 100, 1)
        : 0;

    $monthlyData = DB::select("
        SELECT
            TO_CHAR(DATE_TRUNC('month', c.created_at), 'Mon YYYY') as label,
            COUNT(DISTINCT c.id) as total_customers,
            COUNT(DISTINCT CASE WHEN f.result = 'DEAL' THEN c.id END) as deal
        FROM customers c
        LEFT JOIN follow_ups f ON f.customer_id = c.id AND f.deleted_at IS NULL AND f.result = 'DEAL'
        WHERE c.deleted_at IS NULL
            AND c.created_at >= NOW() - INTERVAL '6 months'
        GROUP BY DATE_TRUNC('month', c.created_at)
        ORDER BY DATE_TRUNC('month', c.created_at) ASC
    ");

    return ApiResponse::success([
        'total_customers'   => $totalCustomers,
        'total_deal'        => $totalDeal,
        'total_not_deal'    => $totalCustomers - $totalDeal,
        'conversion_rate'   => $conversionRate,
        'monthly_labels'    => array_column($monthlyData, 'label'),
        'monthly_converted' => array_map('intval', array_column($monthlyData, 'deal')),
    ], 'Success');
}





/*
|--------------------------------------------------------------------------
| 7. ACTIVITY FEED - VISITS
|--------------------------------------------------------------------------
*/
public function activityVisits(Request $request)
{
    $month   = $request->input('month', Carbon::now()->format('Y-m'));
    $page    = $request->input('page', 1);
    $perPage = 10;

    try {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    } catch (\Exception $e) {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();
    }

    $total = DB::table('visits as v')
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->count();

    $data = DB::table('visits as v')
        ->select([
            'v.id',
            'v.visit_code',
            'v.visit_at',
            'v.check_in_at',
            'v.check_out_at',
            'v.visit_result',
            'u.fullname as sales_name',
            'u.image as sales_photo',
            DB::raw("COALESCE(l.company_name, c.company_name) as company_name"),
            DB::raw("CASE WHEN v.customer_id IS NOT NULL THEN 'CUSTOMER' ELSE 'LEAD' END as target_type"),
            DB::raw("
                CASE
                    WHEN v.check_in_at IS NULL THEN 'PLANNED'
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NULL THEN 'ONGOING'
                    ELSE 'DONE'
                END as visit_progress
            "),
            DB::raw("
                CASE
                    WHEN u.image IS NOT NULL AND u.image != ''
                        THEN CONCAT('" . asset('storage/users') . "/', u.image)
                    ELSE '" . asset('storage/users/default.png') . "'
                END as sales_photo_url
            "),
        ])
        ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
        ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
        ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->orderBy('v.visit_at', 'desc')
        ->offset(($page - 1) * $perPage)
        ->limit($perPage)
        ->get();

    return ApiResponse::success([
        'data'        => $data,
        'total'       => $total,
        'page'        => (int) $page,
        'per_page'    => $perPage,
        'has_more'    => ($page * $perPage) < $total,
    ], 'Success');
}

/*
|--------------------------------------------------------------------------
| 8. ACTIVITY FEED - FOLLOW UPS
|--------------------------------------------------------------------------
*/
public function activityFollowUps(Request $request)
{
    $month   = $request->input('month', Carbon::now()->format('Y-m'));
    $page    = $request->input('page', 1);
    $perPage = 10;

    try {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    } catch (\Exception $e) {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();
    }

    $total = DB::table('follow_ups as f')
        ->whereNull('f.deleted_at')
        ->whereBetween('f.follow_up_at', [$start, $end])
        ->count();

    $data = DB::table('follow_ups as f')
        ->select([
            'f.id',
            'f.follow_up_code',
            'f.follow_up_at',
            'f.follow_up_type',
            'f.status',
            'f.result',
            'f.subject',
            'f.completed_at',
            'u.fullname as sales_name',
            'u.image as sales_photo',
            DB::raw("COALESCE(l.company_name, c.company_name) as company_name"),
            DB::raw("CASE WHEN f.customer_id IS NOT NULL THEN 'CUSTOMER' ELSE 'LEAD' END as target_type"),
            DB::raw("
                CASE
                    WHEN u.image IS NOT NULL AND u.image != ''
                        THEN CONCAT('" . asset('storage/users') . "/', u.image)
                    ELSE '" . asset('storage/users/default.png') . "'
                END as sales_photo_url
            "),
        ])
        ->leftJoin('leads as l', 'l.id', '=', 'f.lead_id')
        ->leftJoin('customers as c', 'c.id', '=', 'f.customer_id')
        ->leftJoin('ms_users as u', 'u.id_user', '=', 'f.assigned_to')
        ->whereNull('f.deleted_at')
        ->whereBetween('f.follow_up_at', [$start, $end])
        ->orderBy('f.follow_up_at', 'desc')
        ->offset(($page - 1) * $perPage)
        ->limit($perPage)
        ->get();

    return ApiResponse::success([
        'data'     => $data,
        'total'    => $total,
        'page'     => (int) $page,
        'per_page' => $perPage,
        'has_more' => ($page * $perPage) < $total,
    ], 'Success');
}









/*
|--------------------------------------------------------------------------
| SALES PERSONAL DASHBOARD
|--------------------------------------------------------------------------
*/
public function salesDashboard(Request $request)
{
    $userId = $request->input('user_id');
    $today  = Carbon::today();
    $start  = Carbon::now()->startOfMonth();
    $end    = Carbon::now()->endOfMonth();

    // --- VISIT HARI INI ---
    $visitsToday = DB::table('visits as v')
        ->select([
            'v.id', 'v.visit_code', 'v.visit_at',
            'v.check_in_at', 'v.check_out_at',
            DB::raw("COALESCE(l.company_name, c.company_name) as company_name"),
            DB::raw("CASE WHEN v.customer_id IS NOT NULL THEN 'CUSTOMER' ELSE 'LEAD' END as target_type"),
            DB::raw("
                CASE
                    WHEN v.check_in_at IS NULL THEN 'PLANNED'
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NULL THEN 'ONGOING'
                    ELSE 'DONE'
                END as visit_progress
            "),
        ])
        ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
        ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
        ->whereNull('v.deleted_at')
        ->where('v.sales_id', $userId)
        ->whereDate('v.visit_at', $today)
        ->orderBy('v.visit_at', 'asc')
        ->get();

    // --- FOLLOW UP PENDING & OVERDUE ---
    // $followUpsPending = DB::table('follow_ups as f')
    //     ->select([
    //         'f.id', 'f.follow_up_code', 'f.follow_up_at',
    //         'f.follow_up_type', 'f.status', 'f.subject',
    //         DB::raw("COALESCE(l.company_name, c.company_name) as company_name"),
    //         DB::raw("CASE WHEN f.customer_id IS NOT NULL THEN 'CUSTOMER' ELSE 'LEAD' END as target_type"),
    //         DB::raw("
    //             CASE
    //                 WHEN f.follow_up_at < NOW() AND f.status = 'PENDING' THEN true
    //                 ELSE false
    //             END as is_overdue
    //         "),
    //     ])
    //     ->leftJoin('leads as l', 'l.id', '=', 'f.lead_id')
    //     ->leftJoin('customers as c', 'c.id', '=', 'f.customer_id')
    //     ->whereNull('f.deleted_at')
    //     ->where('f.assigned_to', $userId)
    //     ->where('f.status', 'PENDING')
    //     ->orderByRaw('f.follow_up_at ASC')
    //     ->limit(10)
    //     ->get();
    $followUpsPending = DB::table('follow_ups as f')
    ->select([
        'f.id', 'f.follow_up_code', 'f.follow_up_at',
        'f.follow_up_type', 'f.status', 'f.subject',
        DB::raw("COALESCE(l.company_name, c.company_name) as company_name"),
        DB::raw("CASE WHEN f.customer_id IS NOT NULL THEN 'CUSTOMER' ELSE 'LEAD' END as target_type"),
        DB::raw("
            CASE
                WHEN f.follow_up_at < NOW() AND f.status = 'PENDING' THEN true
                ELSE false
            END as is_overdue
        "),
    ])
    ->leftJoin('leads as l', 'l.id', '=', 'f.lead_id')
    ->leftJoin('customers as c', 'c.id', '=', 'f.customer_id')
    ->leftJoin('ms_users as u', 'u.id_user', '=', 'f.assigned_to')
    ->whereNull('f.deleted_at')
    ->where('f.status', 'PENDING')
    ->where(function ($q) use ($userId) {
        $q->where('f.assigned_to', $userId)           // assigned langsung ke dia
          ->orWhere(function ($q2) use ($userId) {
              // follow up lead yang assigned_to null, tapi lead-nya milik dia
              $q2->whereNull('f.assigned_to')
                 ->whereNotNull('f.lead_id')
                 ->where(function ($q3) use ($userId) {
                     $q3->where('l.assigned_to', $userId)
                        ->orWhere('l.id_user', $userId);
                 });
          })
          ->orWhere(function ($q2) use ($userId) {
              // follow up customer yang assigned_to null, tapi customer-nya milik dia
              $q2->whereNull('f.assigned_to')
                 ->whereNotNull('f.customer_id')
                 ->where(function ($q3) use ($userId) {
                     $q3->where('c.assigned_to', $userId)
                        ->orWhere('c.id_user', $userId);
                 });
          });
    })
    ->orderByRaw('f.follow_up_at ASC')
    ->limit(10)
    ->get();

    // --- RANKING BULAN INI ---
    $rankings = DB::table('visits as v')
        ->select([
            'v.sales_id',
            'u.fullname as sales_name',
            'u.image as sales_photo',
            DB::raw("COUNT(v.id) as total_visits"),
            DB::raw("SUM(CASE WHEN v.check_out_at IS NOT NULL THEN 1 ELSE 0 END) as done"),
            DB::raw("
                CASE
                    WHEN u.image IS NOT NULL AND u.image != ''
                        THEN CONCAT('" . asset('storage/users') . "/', u.image)
                    ELSE '" . asset('storage/users/default.png') . "'
                END as sales_photo_url
            "),
        ])
        ->leftJoin('ms_users as u', 'u.id_user', '=', 'v.sales_id')
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->groupBy('v.sales_id', 'u.fullname', 'u.image')
        ->orderBy('total_visits', 'desc')
        ->get();

    // Cari ranking user ini
    $myRank = $rankings->search(fn($r) => $r->sales_id == $userId);
    $myRank = $myRank !== false ? $myRank + 1 : '-';
    $myStats = $rankings->firstWhere('sales_id', $userId);

    // --- TARGET VS AKTUAL ---
    // Asumsi target default 20 visit/bulan (bisa dari config/settings)
    $target = 20;
    $actual = $myStats->total_visits ?? 0;
    $achievement = $target > 0 ? round(($actual / $target) * 100, 1) : 0;

    // Visit per hari bulan ini (untuk mini chart)
    $visitPerDay = DB::select("
        SELECT
            TO_CHAR(visit_at::date, 'DD') as day,
            COUNT(*) as total
        FROM visits
        WHERE deleted_at IS NULL
            AND sales_id = ?
            AND visit_at BETWEEN ? AND ?
        GROUP BY visit_at::date
        ORDER BY visit_at::date ASC
    ", [$userId, $start, $end]);


    // --- TOTAL LEADS YANG DIKELOLA ---
$totalLeads = DB::table('leads')
    ->whereNull('deleted_at')
    ->where(function ($q) use ($userId) {
        $q->where('assigned_to', $userId)
          ->orWhere('id_user', $userId);
    })
    ->count();

// --- TOTAL CUSTOMERS YANG DIKELOLA ---
$totalCustomers = DB::table('customers')
    ->whereNull('deleted_at')
    ->where(function ($q) use ($userId) {
        $q->where('assigned_to', $userId)
          ->orWhere('id_user', $userId);
    })
    ->count();

    return ApiResponse::success([
        'visits_today'     => $visitsToday,
        'follow_ups'       => $followUpsPending,
        'ranking' => [
            'rank'         => $myRank,
            'total_sales'  => $rankings->count(),
            'total_visits' => $actual,
            'done_visits'  => $myStats->done ?? 0,
            'leaderboard'  => $rankings->take(5)->values(),
        ],
        'target' => [
            'target'      => $target,
            'actual'      => $actual,
            'achievement' => $achievement,
            'per_day'     => $visitPerDay,
        ],
        'total_leads'     => $totalLeads,  
        'total_customers' => $totalCustomers,
    ], 'Success');
}
}
