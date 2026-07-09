<?php

namespace App\Http\Controllers\Api\Manager\Dashboard;


use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardManagerController extends Controller
{
     /**
     * Dashboard Summary
     */
    public function summary(Request $request)
    {
        [$start, $end] = $this->getPeriod($request);

        /*
        |--------------------------------------------------------------------------
        | Lead
        |--------------------------------------------------------------------------
        */

        $totalLeads = DB::table('leads')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $totalCustomers = DB::table('customers')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Visit
        |--------------------------------------------------------------------------
        */

        $visitsToday = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereDate('visit_at', today())
            ->count();

        $visitsThisMonth = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->count();

        $lastMonthStart = $start->copy()->subMonth()->startOfMonth();
        $lastMonthEnd   = $start->copy()->subMonth()->endOfMonth();

        $visitsLastMonth = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $visitGrowth = $visitsLastMonth > 0
            ? round((($visitsThisMonth - $visitsLastMonth) / $visitsLastMonth) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Follow Up
        |--------------------------------------------------------------------------
        */

        $followUpsToday = DB::table('follow_ups')
            ->whereNull('deleted_at')
            ->whereDate('follow_up_at', today())
            ->count();

        $overdueFollowUps = DB::table('follow_ups')
            ->whereNull('deleted_at')
            ->whereDate('follow_up_at', '<', today())
            ->whereNotIn('status', ['completed', 'closed'])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Sales
        |--------------------------------------------------------------------------
        */

        $activeSales = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->distinct()
            ->count('sales_id');

        return ApiResponse::success([
            'period' => [
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
            ],

            'lead' => [
                'total' => $totalLeads,
            ],

            'customer' => [
                'total' => $totalCustomers,
            ],

            'visit' => [
                'today'       => $visitsToday,
                'this_month'  => $visitsThisMonth,
                'last_month'  => $visitsLastMonth,
                'growth'      => $visitGrowth,
            ],

            'follow_up' => [
                'today'   => $followUpsToday,
                'overdue' => $overdueFollowUps,
            ],

            'sales' => [
                'active' => $activeSales,
            ],

        ], 'Dashboard summary retrieved successfully.');
    }



    /**
     * Sales Performance
     */
public function salesPerformance(Request $request)
{
    [$start, $end] = $this->getPeriod($request);

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    $totalSales = DB::table('ms_users')
        ->whereNull('deleted_at')
        ->where('is_active', true)
        ->count();

    $activeSales = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->distinct()
        ->count('sales_id');

    $inactiveSales = max(0, $totalSales - $activeSales);

    $totalVisit = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->count();

    $totalFollowUp = DB::table('follow_ups')
        ->whereNull('deleted_at')
        ->whereBetween('follow_up_at', [$start, $end])
        ->count();

    $totalCustomer = DB::table('customers')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Ranking Sales
    |--------------------------------------------------------------------------
    */

    $ranking = DB::table('ms_users as u')
        ->leftJoin('visits as v', function ($join) use ($start, $end) {
            $join->on('u.id_user', '=', 'v.sales_id')
                ->whereNull('v.deleted_at')
                ->whereBetween('v.visit_at', [$start, $end]);
        })
        ->leftJoin('follow_ups as f', function ($join) use ($start, $end) {
            $join->on('u.id_user', '=', 'f.assigned_to')
                ->whereNull('f.deleted_at')
                ->whereBetween('f.follow_up_at', [$start, $end]);
        })
        ->leftJoin('customers as c', function ($join) use ($start, $end) {
            $join->on('u.id_user', '=', 'c.id_user')
                ->whereNull('c.deleted_at')
                ->whereBetween('c.created_at', [$start, $end]);
        })
        ->whereNull('u.deleted_at')
        ->where('u.is_active', true)
        ->groupBy(
            'u.id_user',
            'u.fullname',
            'u.email',
            'u.image'
        )
        ->select(
            'u.id_user',
            'u.fullname',
            'u.email',
            'u.image',

            DB::raw('COUNT(DISTINCT v.id) as total_visit'),

            DB::raw('COUNT(DISTINCT f.id) as total_follow_up'),

            DB::raw('COUNT(DISTINCT c.id) as total_customer'),

            DB::raw('
                (
                    COUNT(DISTINCT v.id)
                    +
                    COUNT(DISTINCT f.id)
                    +
                    COUNT(DISTINCT c.id)
                ) as performance_score
            ')
        )
        ->orderByDesc('performance_score')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Top Performer
    |--------------------------------------------------------------------------
    */

    $topPerformer = $ranking->first();

    return ApiResponse::success([

        'summary' => [

            'total_sales' => $totalSales,

            'active_sales' => $activeSales,

            'inactive_sales' => $inactiveSales,

            'total_visit' => $totalVisit,

            'total_follow_up' => $totalFollowUp,

            'total_customer' => $totalCustomer,

        ],

        'top_performer' => $topPerformer,

        'ranking' => $ranking,

    ], 'Sales performance retrieved successfully.');
}





    /**
     * Follow Up Report
     */
    /**
 * Follow Up Report
 */
public function followUp(Request $request)
{
    [$start, $end] = $this->getPeriod($request);

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    $totalFollowUp = DB::table('follow_ups')
        ->whereNull('deleted_at')
        ->whereBetween('follow_up_at', [$start, $end])
        ->count();

    $pending = DB::table('follow_ups')
        ->whereNull('deleted_at')
        ->whereBetween('follow_up_at', [$start, $end])
        ->where('status', 'PENDING')
        ->count();

    $done = DB::table('follow_ups')
        ->whereNull('deleted_at')
        ->whereBetween('follow_up_at', [$start, $end])
        ->where('status', 'DONE')
        ->count();

    $cancelled = DB::table('follow_ups')
        ->whereNull('deleted_at')
        ->whereBetween('follow_up_at', [$start, $end])
        ->where('status', 'CANCELLED')
        ->count();

    $closed = DB::table('follow_ups')
        ->whereNull('deleted_at')
        ->whereBetween('follow_up_at', [$start, $end])
        ->where('status', 'CLOSED')
        ->count();

    $overdue = DB::table('follow_ups')
        ->whereNull('deleted_at')
        ->where('status', 'PENDING')
        ->whereDate('follow_up_at', '<', today())
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Follow Up Type
    |--------------------------------------------------------------------------
    */

    $activity = DB::table('follow_ups')
        ->select(
            'follow_up_type',
            DB::raw('COUNT(*) as total')
        )
        ->whereNull('deleted_at')
        ->whereBetween('follow_up_at', [$start, $end])
        ->groupBy('follow_up_type')
        ->orderBy('follow_up_type')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Follow Up Result
    |--------------------------------------------------------------------------
    */

    $result = DB::table('follow_ups')
        ->select(
            'result',
            DB::raw('COUNT(*) as total')
        )
        ->whereNull('deleted_at')
        ->whereBetween('follow_up_at', [$start, $end])
        ->whereNotNull('result')
        ->groupBy('result')
        ->orderBy('result')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Top Sales
    |--------------------------------------------------------------------------
    */

    $topSales = DB::table('follow_ups as f')
        ->join('ms_users as u', 'u.id_user', '=', 'f.assigned_to')
        ->select(
            'u.id_user',
            'u.fullname',
            DB::raw('COUNT(f.id) as total_follow_up')
        )
        ->whereNull('f.deleted_at')
        ->whereBetween('f.follow_up_at', [$start, $end])
        ->groupBy(
            'u.id_user',
            'u.fullname'
        )
        ->orderByDesc('total_follow_up')
        ->limit(10)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Overdue List
    |--------------------------------------------------------------------------
    */

    $overdueList = DB::table('follow_ups as f')
        ->leftJoin('customers as c', 'c.id', '=', 'f.customer_id')
        ->leftJoin('leads as l', 'l.id', '=', 'f.lead_id')
        ->leftJoin('ms_users as u', 'u.id_user', '=', 'f.assigned_to')
        ->select(
            'f.follow_up_code',
            DB::raw("COALESCE(c.company_name,l.company_name) as customer_name"),
            'u.fullname as sales_name',
            'f.follow_up_type',
            'f.follow_up_at',
            'f.status',
            DB::raw("CURRENT_DATE - DATE(f.follow_up_at) as overdue_days")
        )
        ->whereNull('f.deleted_at')
        ->where('f.status', 'PENDING')
        ->whereDate('f.follow_up_at', '<', today())
        ->orderBy('f.follow_up_at')
        ->limit(20)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Daily Trend
    |--------------------------------------------------------------------------
    */

    $trend = DB::table('follow_ups')
        ->select(
            DB::raw("DATE(follow_up_at) as date"),
            DB::raw("COUNT(*) as total")
        )
        ->whereNull('deleted_at')
        ->whereBetween('follow_up_at', [$start, $end])
        ->groupBy(DB::raw("DATE(follow_up_at)"))
        ->orderBy(DB::raw("DATE(follow_up_at)"))
        ->get();

    return ApiResponse::success([

        'summary' => [
            'total_follow_up' => $totalFollowUp,
            'pending' => $pending,
            'done' => $done,
            'cancelled' => $cancelled,
            'closed' => $closed,
            'overdue' => $overdue,
        ],

        'activity' => $activity,

        'result' => $result,

        'top_sales' => $topSales,

        'overdue_list' => $overdueList,

        'daily_trend' => $trend,

    ], 'Follow up report retrieved successfully.');
}

    /**
     * Visit Report
     */
       
    public function visit(Request $request)
    {
        [$start, $end] = $this->getPeriod($request);

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $totalVisit = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->count();

        $doneVisit = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->where('visit_status', 'DONE')
            ->count();

        $ongoingVisit = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->where('visit_status', 'ONGOING')
            ->count();

        $checkedInVisit = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->where('visit_status', 'CHECKED_IN')
            ->count();

        $cancelVisit = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->where('visit_status', 'CANCELED')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Complaint
        |--------------------------------------------------------------------------
        */

        $complaint = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->where('has_complaint', true)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Potential Order
        |--------------------------------------------------------------------------
        */

        $potentialOrder = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->where('has_potential_order', true)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Visit Result
        |--------------------------------------------------------------------------
        */

        $visitResult = DB::table('visits')
            ->select(
                'visit_result',
                DB::raw('COUNT(*) as total')
            )
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->whereNotNull('visit_result')
            ->groupBy('visit_result')
            ->orderBy('visit_result')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Top Sales
        |--------------------------------------------------------------------------
        */

        $topSales = DB::table('visits as v')
            ->join('ms_users as u', 'u.id_user', '=', 'v.sales_id')
            ->select(
                'u.id_user',
                'u.fullname',
                DB::raw('COUNT(v.id) as total_visit')
            )
            ->whereNull('v.deleted_at')
            ->whereBetween('v.visit_at', [$start, $end])
            ->groupBy(
                'u.id_user',
                'u.fullname'
            )
            ->orderByDesc('total_visit')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Daily Trend
        |--------------------------------------------------------------------------
        */

        $dailyTrend = DB::table('visits')
            ->select(
                DB::raw('DATE(visit_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->groupBy(DB::raw('DATE(visit_at)'))
            ->orderBy(DB::raw('DATE(visit_at)'))
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Visit Duration
        |--------------------------------------------------------------------------
        */

        $averageDuration = DB::table('visits')
            ->selectRaw("
                AVG(
                    EXTRACT(EPOCH FROM (check_out_at - check_in_at))/60
                ) as avg_duration
            ")
            ->whereNull('deleted_at')
            ->whereBetween('visit_at', [$start, $end])
            ->whereNotNull('check_in_at')
            ->whereNotNull('check_out_at')
            ->value('avg_duration');

        /*
        |--------------------------------------------------------------------------
        | Detail Visit
        |--------------------------------------------------------------------------
        */

        $visitList = DB::table('visits as v')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
            ->join('ms_users as u', 'u.id_user', '=', 'v.sales_id')
            ->select(
                'v.visit_code',
                DB::raw("COALESCE(c.company_name,l.company_name) as customer_name"),
                'u.fullname as sales_name',
                'v.visit_at',
                'v.visit_status',
                'v.visit_result',
                'v.has_complaint',
                'v.has_potential_order'
            )
            ->whereNull('v.deleted_at')
            ->whereBetween('v.visit_at', [$start, $end])
            ->latest('v.visit_at')
            ->limit(20)
            ->get();

        return ApiResponse::success([

            'summary' => [

                'total_visit' => $totalVisit,

                'done' => $doneVisit,

                'ongoing' => $ongoingVisit,

                'checked_in' => $checkedInVisit,

                'cancelled' => $cancelVisit,

                'complaint' => $complaint,

                'potential_order' => $potentialOrder,

                'average_duration_minutes' => round($averageDuration ?? 0, 2),

            ],

            'visit_result' => $visitResult,

            'top_sales' => $topSales,

            'daily_trend' => $dailyTrend,

            'visit_list' => $visitList,

        ], 'Visit report retrieved successfully.');
    }



    /**
     * Sales Pipeline
     */
    public function pipeline(Request $request)
{
    [$start, $end] = $this->getPeriod($request);

    /*
    |--------------------------------------------------------------------------
    | Lead Summary
    |--------------------------------------------------------------------------
    */

    $totalLead = DB::table('leads')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    $convertedLead = DB::table('customers')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->whereNotNull('lead_id')
        ->count();

    $openLead = max(0, $totalLead - $convertedLead);

    $conversionRate = $totalLead > 0
        ? round(($convertedLead / $totalLead) * 100, 2)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Lead Source
    |--------------------------------------------------------------------------
    */

    $leadSource = DB::table('customers')
        ->select(
            'lead_source',
            DB::raw('COUNT(*) as total')
        )
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->groupBy('lead_source')
        ->orderByDesc('total')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Customer Status
    |--------------------------------------------------------------------------
    */

    $customerStatus = DB::table('customers')
        ->select(
            'customer_status',
            DB::raw('COUNT(*) as total')
        )
        ->whereNull('deleted_at')
        ->groupBy('customer_status')
        ->orderByDesc('total')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Pipeline Per Sales
    |--------------------------------------------------------------------------
    */

    $pipelinePerSales = DB::table('ms_users as u')
        ->leftJoin('customers as c', 'u.id_user', '=', 'c.id_user')
        ->select(
            'u.id_user',
            'u.fullname',
            DB::raw('COUNT(c.id) as total_customer')
        )
        ->whereNull('u.deleted_at')
        ->where('u.is_active', true)
        ->groupBy(
            'u.id_user',
            'u.fullname'
        )
        ->orderByDesc('total_customer')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Monthly Conversion
    |--------------------------------------------------------------------------
    */

    $monthlyConversion = DB::table('customers')
        ->select(
            DB::raw("DATE(created_at) as date"),
            DB::raw("COUNT(*) as total")
        )
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->groupBy(DB::raw("DATE(created_at)"))
        ->orderBy(DB::raw("DATE(created_at)"))
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Latest Converted Customer
    |--------------------------------------------------------------------------
    */

    $latestCustomer = DB::table('customers as c')
        ->leftJoin('ms_users as u', 'u.id_user', '=', 'c.id_user')
        ->select(
            'c.customer_code',
            'c.company_name',
            'u.fullname as sales_name',
            'c.customer_status',
            'c.created_at'
        )
        ->whereNull('c.deleted_at')
        ->whereBetween('c.created_at', [$start, $end])
        ->latest('c.created_at')
        ->limit(20)
        ->get();

    return ApiResponse::success([

        'summary' => [

            'total_lead' => $totalLead,

            'converted_lead' => $convertedLead,

            'open_lead' => $openLead,

            'conversion_rate' => $conversionRate,

        ],

        'lead_source' => $leadSource,

        'customer_status' => $customerStatus,

        'pipeline_per_sales' => $pipelinePerSales,

        'monthly_conversion' => $monthlyConversion,

        'latest_customer' => $latestCustomer,

    ], 'Pipeline report retrieved successfully.');
}

    /**
 * Activity Report
 */
public function activity(Request $request)
{
    [$start, $end] = $this->getPeriod($request);

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    $totalActivity = DB::table('follow_up_activities')
        ->whereBetween('activity_at', [$start, $end])
        ->count();

    $todayActivity = DB::table('follow_up_activities')
        ->whereDate('activity_at', today())
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Activity Type
    |--------------------------------------------------------------------------
    */

    $activityType = DB::table('follow_up_activities')
        ->select(
            'activity_type',
            DB::raw('COUNT(*) as total')
        )
        ->whereBetween('activity_at', [$start, $end])
        ->groupBy('activity_type')
        ->orderByDesc('total')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Top Sales Activity
    |--------------------------------------------------------------------------
    */

    $topSales = DB::table('follow_up_activities as fa')
        ->join('ms_users as u', 'u.id_user', '=', 'fa.created_by')
        ->select(
            'u.id_user',
            'u.fullname',
            DB::raw('COUNT(fa.id) as total_activity')
        )
        ->whereBetween('fa.activity_at', [$start, $end])
        ->groupBy(
            'u.id_user',
            'u.fullname'
        )
        ->orderByDesc('total_activity')
        ->limit(10)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Daily Trend
    |--------------------------------------------------------------------------
    */

    $dailyTrend = DB::table('follow_up_activities')
        ->select(
            DB::raw('DATE(activity_at) as date'),
            DB::raw('COUNT(*) as total')
        )
        ->whereBetween('activity_at', [$start, $end])
        ->groupBy(DB::raw('DATE(activity_at)'))
        ->orderBy(DB::raw('DATE(activity_at)'))
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Latest Activity
    |--------------------------------------------------------------------------
    */

    $latestActivity = DB::table('follow_up_activities as fa')
        ->join('ms_users as u', 'u.id_user', '=', 'fa.created_by')
        ->join('follow_ups as fu', 'fu.id', '=', 'fa.follow_up_id')
        ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
        ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
        ->select(
            'fa.id',
            'fa.activity_type',
            'fa.title',
            'fa.description',
            'fa.activity_at',
            'u.fullname as sales_name',
            DB::raw("COALESCE(c.company_name,l.company_name) as customer_name")
        )
        ->whereBetween('fa.activity_at', [$start, $end])
        ->latest('fa.activity_at')
        ->limit(20)
        ->get();

    return ApiResponse::success([

        'summary' => [

            'total_activity' => $totalActivity,

            'today_activity' => $todayActivity,

        ],

        'activity_type' => $activityType,

        'top_sales' => $topSales,

        'daily_trend' => $dailyTrend,

        'latest_activity' => $latestActivity,

    ], 'Activity report retrieved successfully.');
}

    /**
 * Conversion Report
 */
public function conversion(Request $request)
{
    [$start, $end] = $this->getPeriod($request);

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    $totalLead = DB::table('leads')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    $converted = DB::table('customers')
        ->whereNull('deleted_at')
        ->whereNotNull('lead_id')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    $notConverted = max(0, $totalLead - $converted);

    $conversionRate = $totalLead > 0
        ? round(($converted / $totalLead) * 100, 2)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Conversion By Sales
    |--------------------------------------------------------------------------
    */

    $salesConversion = DB::table('ms_users as u')
        ->leftJoin('customers as c', function ($join) use ($start, $end) {

            $join->on('u.id_user', '=', 'c.id_user')
                ->whereNull('c.deleted_at')
                ->whereBetween('c.created_at', [$start, $end]);

        })
        ->select(

            'u.id_user',

            'u.fullname',

            DB::raw('COUNT(c.id) as total_conversion')

        )
        ->whereNull('u.deleted_at')
        ->where('u.is_active', true)
        ->groupBy(
            'u.id_user',
            'u.fullname'
        )
        ->orderByDesc('total_conversion')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Daily Conversion
    |--------------------------------------------------------------------------
    */

    $dailyConversion = DB::table('customers')
        ->select(

            DB::raw('DATE(created_at) as date'),

            DB::raw('COUNT(*) as total')

        )
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy(DB::raw('DATE(created_at)'))
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Customer Status
    |--------------------------------------------------------------------------
    */

    $customerStatus = DB::table('customers')
        ->select(

            'customer_status',

            DB::raw('COUNT(*) as total')

        )
        ->whereNull('deleted_at')
        ->groupBy('customer_status')
        ->orderByDesc('total')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Latest Converted Customer
    |--------------------------------------------------------------------------
    */

    $latestConversion = DB::table('customers as c')

        ->join('ms_users as u', 'u.id_user', '=', 'c.id_user')

        ->select(

            'c.customer_code',

            'c.company_name',

            'u.fullname as sales_name',

            'c.customer_status',

            'c.created_at'

        )

        ->whereNull('c.deleted_at')

        ->whereBetween('c.created_at', [$start, $end])

        ->latest('c.created_at')

        ->limit(20)

        ->get();

    return ApiResponse::success([

        'summary' => [

            'total_lead' => $totalLead,

            'converted' => $converted,

            'not_converted' => $notConverted,

            'conversion_rate' => $conversionRate,

        ],

        'sales_conversion' => $salesConversion,

        'daily_conversion' => $dailyConversion,

        'customer_status' => $customerStatus,

        'latest_conversion' => $latestConversion,

    ], 'Conversion report retrieved successfully.');
}

    /**
     * Complaint Report
     */
    /**
 * Complaint Report
 */
public function complaint(Request $request)
{
    [$start, $end] = $this->getPeriod($request);

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    $totalComplaint = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->where('has_complaint', true)
        ->count();

    $totalVisit = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->count();

    $complaintRate = $totalVisit > 0
        ? round(($totalComplaint / $totalVisit) * 100, 2)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Complaint Trend
    |--------------------------------------------------------------------------
    */

    $dailyTrend = DB::table('visits')
        ->select(
            DB::raw('DATE(visit_at) as date'),
            DB::raw('COUNT(*) as total')
        )
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->where('has_complaint', true)
        ->groupBy(DB::raw('DATE(visit_at)'))
        ->orderBy(DB::raw('DATE(visit_at)'))
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Complaint Per Sales
    |--------------------------------------------------------------------------
    */

    $complaintPerSales = DB::table('visits as v')
        ->join('ms_users as u', 'u.id_user', '=', 'v.sales_id')
        ->select(
            'u.id_user',
            'u.fullname',
            DB::raw('COUNT(v.id) as total_complaint')
        )
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->where('v.has_complaint', true)
        ->groupBy(
            'u.id_user',
            'u.fullname'
        )
        ->orderByDesc('total_complaint')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Complaint Per Customer
    |--------------------------------------------------------------------------
    */

    $complaintPerCustomer = DB::table('visits as v')
        ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
        ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
        ->select(
            DB::raw("COALESCE(c.company_name,l.company_name) as customer_name"),
            DB::raw('COUNT(v.id) as total_complaint')
        )
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->where('v.has_complaint', true)
        ->groupBy(DB::raw("COALESCE(c.company_name,l.company_name)"))
        ->orderByDesc('total_complaint')
        ->limit(10)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Latest Complaint
    |--------------------------------------------------------------------------
    */

    $latestComplaint = DB::table('visits as v')
        ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
        ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
        ->join('ms_users as u', 'u.id_user', '=', 'v.sales_id')
        ->select(
            'v.visit_code',
            'u.fullname as sales_name',
            DB::raw("COALESCE(c.company_name,l.company_name) as customer_name"),
            'v.complaint_detail',
            'v.visit_result',
            'v.visit_at'
        )
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->where('v.has_complaint', true)
        ->latest('v.visit_at')
        ->limit(20)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Complaint Percentage Per Sales
    |--------------------------------------------------------------------------
    */

    $complaintPercentage = DB::table('visits as v')
        ->join('ms_users as u', 'u.id_user', '=', 'v.sales_id')
        ->select(
            'u.id_user',
            'u.fullname',
            DB::raw('COUNT(v.id) as total_visit'),
            DB::raw("
                SUM(
                    CASE
                        WHEN has_complaint = true THEN 1
                        ELSE 0
                    END
                ) as total_complaint
            "),
            DB::raw("
                ROUND(
                    (
                        SUM(
                            CASE
                                WHEN has_complaint = true THEN 1
                                ELSE 0
                            END
                        )::numeric
                        /
                        COUNT(v.id)
                    ) * 100,
                    2
                ) as complaint_rate
            ")
        )
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->groupBy(
            'u.id_user',
            'u.fullname'
        )
        ->orderByDesc('complaint_rate')
        ->get();

    return ApiResponse::success([

        'summary' => [

            'total_visit' => $totalVisit,

            'total_complaint' => $totalComplaint,

            'complaint_rate' => $complaintRate,

        ],

        'daily_trend' => $dailyTrend,

        'complaint_per_sales' => $complaintPerSales,

        'complaint_per_customer' => $complaintPerCustomer,

        'complaint_percentage' => $complaintPercentage,

        'latest_complaint' => $latestComplaint,

    ], 'Complaint report retrieved successfully.');
}

    /**
 * Potential Order Report
 */
public function potentialOrder(Request $request)
{
    [$start, $end] = $this->getPeriod($request);

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    $totalVisit = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->count();

    $totalPotential = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->where('has_potential_order', true)
        ->count();

    $potentialRate = $totalVisit > 0
        ? round(($totalPotential / $totalVisit) * 100, 2)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Daily Trend
    |--------------------------------------------------------------------------
    */

    $dailyTrend = DB::table('visits')
        ->select(
            DB::raw('DATE(visit_at) as date'),
            DB::raw('COUNT(*) as total')
        )
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->where('has_potential_order', true)
        ->groupBy(DB::raw('DATE(visit_at)'))
        ->orderBy(DB::raw('DATE(visit_at)'))
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Potential Order Per Sales
    |--------------------------------------------------------------------------
    */

    $potentialPerSales = DB::table('visits as v')
        ->join('ms_users as u', 'u.id_user', '=', 'v.sales_id')
        ->select(
            'u.id_user',
            'u.fullname',
            DB::raw('COUNT(v.id) as total_potential')
        )
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->where('v.has_potential_order', true)
        ->groupBy(
            'u.id_user',
            'u.fullname'
        )
        ->orderByDesc('total_potential')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Potential Order Per Customer
    |--------------------------------------------------------------------------
    */

    $potentialPerCustomer = DB::table('visits as v')
        ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
        ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
        ->select(
            DB::raw("COALESCE(c.company_name,l.company_name) as customer_name"),
            DB::raw('COUNT(v.id) as total_potential')
        )
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->where('v.has_potential_order', true)
        ->groupBy(DB::raw("COALESCE(c.company_name,l.company_name)"))
        ->orderByDesc('total_potential')
        ->limit(10)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Latest Potential Order
    |--------------------------------------------------------------------------
    */

    $latestPotential = DB::table('visits as v')
        ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
        ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
        ->join('ms_users as u', 'u.id_user', '=', 'v.sales_id')
        ->select(
            'v.visit_code',
            'u.fullname as sales_name',
            DB::raw("COALESCE(c.company_name,l.company_name) as customer_name"),
            'v.potential_order_detail',
            'v.visit_result',
            'v.visit_at'
        )
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->where('v.has_potential_order', true)
        ->latest('v.visit_at')
        ->limit(20)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Potential Percentage Per Sales
    |--------------------------------------------------------------------------
    */

    $potentialPercentage = DB::table('visits as v')
        ->join('ms_users as u', 'u.id_user', '=', 'v.sales_id')
        ->select(
            'u.id_user',
            'u.fullname',
            DB::raw('COUNT(v.id) as total_visit'),
            DB::raw("
                SUM(
                    CASE
                        WHEN has_potential_order = true THEN 1
                        ELSE 0
                    END
                ) as total_potential
            "),
            DB::raw("
                ROUND(
                    (
                        SUM(
                            CASE
                                WHEN has_potential_order = true THEN 1
                                ELSE 0
                            END
                        )::numeric
                        /
                        COUNT(v.id)
                    ) * 100,
                    2
                ) as potential_rate
            ")
        )
        ->whereNull('v.deleted_at')
        ->whereBetween('v.visit_at', [$start, $end])
        ->groupBy(
            'u.id_user',
            'u.fullname'
        )
        ->orderByDesc('potential_rate')
        ->get();

    return ApiResponse::success([

        'summary' => [

            'total_visit' => $totalVisit,

            'total_potential_order' => $totalPotential,

            'potential_rate' => $potentialRate,

        ],

        'daily_trend' => $dailyTrend,

        'potential_per_sales' => $potentialPerSales,

        'potential_per_customer' => $potentialPerCustomer,

        'potential_percentage' => $potentialPercentage,

        'latest_potential_order' => $latestPotential,

    ], 'Potential order report retrieved successfully.');
}



/**
 * Customer Report
 */
public function customers(Request $request)
{
    [$start, $end] = $this->getPeriod($request);

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    $totalCustomer = DB::table('customers')
        ->whereNull('deleted_at')
        ->count();

    $newCustomer = DB::table('customers')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    $activeCustomer = DB::table('customers')
        ->whereNull('deleted_at')
        ->where('customer_status', 'Active')
        ->count();

    $dormantCustomer = DB::table('customers')
        ->whereNull('deleted_at')
        ->where('customer_status', 'Dormant')
        ->count();

    $inactiveCustomer = DB::table('customers')
        ->whereNull('deleted_at')
        ->where('customer_status', 'Inactive')
        ->count();

    $lostCustomer = DB::table('customers')
        ->whereNull('deleted_at')
        ->where('customer_status', 'Lost')
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Customer Status
    |--------------------------------------------------------------------------
    */

    $customerStatus = DB::table('customers')
        ->select(
            'customer_status',
            DB::raw('COUNT(*) as total')
        )
        ->whereNull('deleted_at')
        ->groupBy('customer_status')
        ->orderByDesc('total')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Lead Source
    |--------------------------------------------------------------------------
    */

    $leadSource = DB::table('customers')
        ->select(
            'lead_source',
            DB::raw('COUNT(*) as total')
        )
        ->whereNull('deleted_at')
        ->groupBy('lead_source')
        ->orderByDesc('total')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Top Sales
    |--------------------------------------------------------------------------
    */

    $topSales = DB::table('customers as c')
        ->join('ms_users as u', 'u.id_user', '=', 'c.id_user')
        ->select(
            'u.id_user',
            'u.fullname',
            DB::raw('COUNT(c.id) as total_customer')
        )
        ->whereNull('c.deleted_at')
        ->groupBy(
            'u.id_user',
            'u.fullname'
        )
        ->orderByDesc('total_customer')
        ->limit(10)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Customer Growth
    |--------------------------------------------------------------------------
    */

    $customerGrowth = DB::table('customers')
        ->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy(DB::raw('DATE(created_at)'))
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Top Customer (Most Visited)
    |--------------------------------------------------------------------------
    */

    $topCustomer = DB::table('visits as v')
        ->join('customers as c', 'c.id', '=', 'v.customer_id')
        ->select(
            'c.customer_code',
            'c.company_name',
            DB::raw('COUNT(v.id) as total_visit')
        )
        ->whereNull('v.deleted_at')
        ->groupBy(
            'c.id',
            'c.customer_code',
            'c.company_name'
        )
        ->orderByDesc('total_visit')
        ->limit(10)
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Customer List
    |--------------------------------------------------------------------------
    */

    $customerList = DB::table('customers as c')
        ->leftJoin('ms_users as u', 'u.id_user', '=', 'c.id_user')
        ->select(
            'c.customer_code',
            'c.company_name',
            'c.contact_name',
            'c.phone',
            'c.customer_status',
            'c.lead_source',
            'u.fullname as sales_name',
            'c.created_at'
        )
        ->whereNull('c.deleted_at')
        ->latest('c.created_at')
        ->limit(20)
        ->get();

    return ApiResponse::success([

        'summary' => [

            'total_customer' => $totalCustomer,

            'new_customer' => $newCustomer,

            'active_customer' => $activeCustomer,

            'dormant_customer' => $dormantCustomer,

            'inactive_customer' => $inactiveCustomer,

            'lost_customer' => $lostCustomer,

        ],

        'customer_status' => $customerStatus,

        'lead_source' => $leadSource,

        'top_sales' => $topSales,

        'customer_growth' => $customerGrowth,

        'top_customer' => $topCustomer,

        'customer_list' => $customerList,

    ], 'Customer report retrieved successfully.');
}

    /**
     * Get Filter Period
     */
    private function getPeriod(Request $request): array
    {
        $month = $request->input('month');

        if ($month) {
            try {
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

                return [$start, $end];
            } catch (\Throwable $e) {
                //
            }
        }

        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        return [$start, $end];
    }



    /**
 * Dashboard KPI
 */
public function kpi(Request $request)
{
    [$start, $end] = $this->getPeriod($request);

    /*
    |--------------------------------------------------------------------------
    | Lead
    |--------------------------------------------------------------------------
    */

    $totalLead = DB::table('leads')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    $totalCustomer = DB::table('customers')
        ->whereNull('deleted_at')
        ->whereBetween('created_at', [$start, $end])
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Visit
    |--------------------------------------------------------------------------
    */

    $totalVisit = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->count();

    $visitToday = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereDate('visit_at', today())
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Follow Up
    |--------------------------------------------------------------------------
    */

    $totalFollowUp = DB::table('follow_ups')
        ->whereNull('deleted_at')
        ->whereBetween('follow_up_at', [$start, $end])
        ->count();

    $overdueFollowUp = DB::table('follow_ups')
        ->whereNull('deleted_at')
        ->where('status', 'PENDING')
        ->whereDate('follow_up_at', '<', today())
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Complaint
    |--------------------------------------------------------------------------
    */

    $totalComplaint = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->where('has_complaint', true)
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Potential Order
    |--------------------------------------------------------------------------
    */

    $totalPotentialOrder = DB::table('visits')
        ->whereNull('deleted_at')
        ->whereBetween('visit_at', [$start, $end])
        ->where('has_potential_order', true)
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Conversion Rate
    |--------------------------------------------------------------------------
    */

    $conversionRate = $totalLead > 0
        ? round(($totalCustomer / $totalLead) * 100, 2)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Complaint Rate
    |--------------------------------------------------------------------------
    */

    $complaintRate = $totalVisit > 0
        ? round(($totalComplaint / $totalVisit) * 100, 2)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Potential Rate
    |--------------------------------------------------------------------------
    */

    $potentialRate = $totalVisit > 0
        ? round(($totalPotentialOrder / $totalVisit) * 100, 2)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Top Sales
    |--------------------------------------------------------------------------
    */

    $topSales = DB::table('ms_users as u')
        ->leftJoin('visits as v', function ($join) use ($start, $end) {

            $join->on('u.id_user', '=', 'v.sales_id')
                ->whereNull('v.deleted_at')
                ->whereBetween('v.visit_at', [$start, $end]);

        })
        ->select(
            'u.id_user',
            'u.fullname',
            DB::raw('COUNT(v.id) as total_visit')
        )
        ->whereNull('u.deleted_at')
        ->groupBy(
            'u.id_user',
            'u.fullname'
        )
        ->orderByDesc('total_visit')
        ->first();

    /*
    |--------------------------------------------------------------------------
    | Top Customer
    |--------------------------------------------------------------------------
    */

    $topCustomer = DB::table('customers as c')
        ->leftJoin('visits as v', 'v.customer_id', '=', 'c.id')
        ->select(
            'c.customer_code',
            'c.company_name',
            DB::raw('COUNT(v.id) as total_visit')
        )
        ->whereNull('c.deleted_at')
        ->groupBy(
            'c.id',
            'c.customer_code',
            'c.company_name'
        )
        ->orderByDesc('total_visit')
        ->first();

    /*
    |--------------------------------------------------------------------------
    | Insight
    |--------------------------------------------------------------------------
    */

    $insight = [];

    if ($overdueFollowUp > 0) {
        $insight[] = [
            'type' => 'warning',
            'title' => 'Follow Up Overdue',
            'message' => "{$overdueFollowUp} follow up belum dikerjakan."
        ];
    }

    if ($totalComplaint > 0) {
        $insight[] = [
            'type' => 'danger',
            'title' => 'Customer Complaint',
            'message' => "{$totalComplaint} complaint diterima bulan ini."
        ];
    }

    if ($totalPotentialOrder > 0) {
        $insight[] = [
            'type' => 'success',
            'title' => 'Potential Order',
            'message' => "{$totalPotentialOrder} customer memiliki peluang order."
        ];
    }

    if ($topSales) {
        $insight[] = [
            'type' => 'info',
            'title' => 'Top Sales',
            'message' => "{$topSales->fullname} menjadi sales paling aktif bulan ini."
        ];
    }

    return ApiResponse::success([

        'summary' => [

            'lead' => $totalLead,

            'customer' => $totalCustomer,

            'visit' => $totalVisit,

            'visit_today' => $visitToday,

            'follow_up' => $totalFollowUp,

            'overdue_follow_up' => $overdueFollowUp,

            'complaint' => $totalComplaint,

            'potential_order' => $totalPotentialOrder,

        ],

        'kpi' => [

            'conversion_rate' => $conversionRate,

            'complaint_rate' => $complaintRate,

            'potential_rate' => $potentialRate,

        ],

        'top_sales' => $topSales,

        'top_customer' => $topCustomer,

        'insight' => $insight,

    ], 'Dashboard KPI retrieved successfully.');
}




}
