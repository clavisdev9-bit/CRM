<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Query dasar buat visit_targets (dipakai bareng oleh controller Manager
 * (VisitTargetController, full CRUD) dan controller Sales (SalesVisitTargetController,
 * read-only) -- biar SQL "achieved_count" & join-nya nggak dobel-tulis di 2 tempat).
 */
trait BuildsVisitTargetQuery
{
    /**
     * @return \Illuminate\Database\Query\Builder
     */
    private function baseVisitTargetQuery()
    {
        return DB::table('visit_targets as vt')
            ->select([
                'vt.id',
                'vt.target_code',
                'vt.sales_id',
                'sales.fullname as sales_name',
                DB::raw("CASE WHEN vt.branch_id IS NOT NULL THEN 'branch' ELSE 'customer' END as target_type"),
                DB::raw('COALESCE(cb.branch_name, c.company_name) as target_name'),
                DB::raw("CASE WHEN vt.branch_id IS NOT NULL THEN 'Branch' ELSE 'Customer' END as target_note"),
                'vt.customer_id',
                'vt.branch_id',
                'vt.target_count',
                'vt.period_month',
                'vt.notes',
                'vt.created_by',
                'manager.fullname as created_by_name',
                'vt.created_at',
                // achieved_count: correlated scalar subquery, per baris target -- lihat
                // penjelasan aturan hitung progress di migration create_visit_targets_table.
                DB::raw("(
                    SELECT COUNT(*) FROM visits v
                    WHERE v.deleted_at IS NULL
                      AND v.check_in_at IS NOT NULL
                      AND v.sales_id = vt.sales_id
                      AND (
                        (vt.customer_id IS NOT NULL AND v.customer_id = vt.customer_id)
                        OR (vt.branch_id IS NOT NULL AND v.branch_id = vt.branch_id)
                      )
                      AND v.visit_at::date BETWEEN vt.period_month AND (vt.period_month + INTERVAL '1 month' - INTERVAL '1 day')
                      AND v.created_at >= vt.created_at
                ) as achieved_count"),
            ])
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'vt.sales_id')
            ->leftJoin('ms_users as manager', 'manager.id_user', '=', 'vt.created_by')
            ->leftJoin('customer_branches as cb', 'cb.id', '=', 'vt.branch_id')
            ->leftJoin('customers as c', 'c.id', '=', 'vt.customer_id')
            ->whereNull('vt.deleted_at');
    }

    /**
     * Generate target_code baru, format VTG-YYYYMMDD-0001 (persis pola
     * follow_up_code/visit_code yang udah ada).
     */
    private function generateTargetCode(): string
    {
        $today = now()->format('Ymd');
        $countToday = DB::table('visit_targets')
            ->whereRaw('created_at::date = CURRENT_DATE')
            ->count();
        $seq = str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);

        return "VTG-{$today}-{$seq}";
    }
}