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

                // ══════════════════════════════════════════════════════════
                // TOMBOL "VISIT" (Sales side, MyTargetVisit.vue) -- konsepnya
                // sama kayak tombol Visit di Customer Master, manggil
                // startVisitCustomers(customerId, branchId) dari
                // visitSalesStore.js. Fungsi itu SELALU butuh customerId (ID
                // customer head office), tapi vt.customer_id PASTI NULL kalau
                // target-nya branch (lihat constraint chk_visit_targets_owner
                // di migration create_visit_targets_table -- exactly 1 dari
                // customer_id/branch_id, gak bisa dua-duanya). Makanya di sini
                // di-resolve manual: kalau target-nya branch, ambil parent
                // customer_id-nya lewat cb.customer_id.
                //
                // SENGAJA dibikin kolom BARU (visit_customer_id) -- BUKAN
                // nimpa/nge-replace kolom vt.customer_id yang udah ada di atas,
                // biar gak ganggu semantik asli customer_id/branch_id yang
                // mungkin masih dipakai controller Manager (VisitTargetController)
                // buat prefill form edit target.
                // ══════════════════════════════════════════════════════════
                DB::raw('COALESCE(vt.customer_id, cb.customer_id) as visit_customer_id'),

                // ── info tambahan buat modal konfirmasi "Visit Now" (sama
                // persis kayak modal di Customer Master) & buat ngecek
                // hasCoordinates() sebelum ngaktifin tombol Visit ──
                'c.company_name as company_name',
                'cb.branch_name as branch_name',
                'cb.city as city',
                DB::raw('CASE WHEN vt.branch_id IS NOT NULL THEN cb.contact_name ELSE c.contact_name END as contact_name'),
                'c.customer_status as customer_status',
                DB::raw('CASE WHEN vt.branch_id IS NOT NULL THEN cb.latitude ELSE c.latitude END as latitude'),
                DB::raw('CASE WHEN vt.branch_id IS NOT NULL THEN cb.longitude ELSE c.longitude END as longitude'),

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
            // ── join `c` di-resolve lewat COALESCE(vt.customer_id, cb.customer_id),
            // BUKAN cuma `c.id = vt.customer_id` -- soalnya kalau join lama
            // dipakai, `c` bakal SELALU NULL buat baris target tipe branch
            // (karena vt.customer_id emang NULL di situ per constraint XOR),
            // padahal kita butuh company_name/contact_name/customer_status
            // dari customer INDUK-nya branch itu buat modal Visit Now. Fix ini
            // aman & backward compatible: buat target tipe customer, hasilnya
            // sama persis kayak join lama (cb NULL -> COALESCE jatuh ke
            // vt.customer_id); target_name/achieved_count/target_type juga
            // gak kepengaruh sama sekali.
            ->leftJoin('customers as c', function ($join) {
                $join->on('c.id', '=', DB::raw('COALESCE(vt.customer_id, cb.customer_id)'));
            })
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