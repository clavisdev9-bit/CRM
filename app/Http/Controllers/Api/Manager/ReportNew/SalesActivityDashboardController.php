<?php

namespace App\Http\Controllers\Api\Manager\ReportNew;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\SalesActivityValidationIndex;
use App\Http\Resources\SalesActivityResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ============================================================================
 * SALES ACTIVITY DASHBOARD (MANAGER ONLY)
 * ----------------------------------------------------------------------------
 * Gaya coding & pola query mengikuti Costumers controller punya kamu: DB::table()
 * query builder, leftJoin manual, ApiResponse::success/error/paginate, try/catch
 * per method.
 *
 * SUDAH DIKONFIRMASI dari migration follow_ups & seeder ms_role yang kamu kirim:
 *
 * - MANAGER ROLE: seeder ms_role isinya persis 'administrator'/'sales'/'manager'
 *   (lowercase) — cocok dengan isManager() di bawah, tidak perlu diubah.
 *
 * - follow_ups.follow_up_type di-constraint CHECK ke: CALL, EMAIL, WHATSAPP,
 *   MEETING, VISIT, OTHER. Aturan "Direct vs Follow Up" tetap: 'VISIT' →
 *   Follow Up, selain itu (termasuk MEETING & OTHER) → Direct. Sesuaikan CASE
 *   WHEN di buildActivitiesUnion() kalau MEETING sebetulnya harus masuk bucket
 *   lain.
 *
 * - follow_ups pakai constraint chk_followups_owner: HARUS salah satu dari
 *   lead_id / customer_id (tidak boleh dua-duanya, tidak boleh kosong dua-
 *   duanya). Makanya semua query follow_ups di bawah sudah leftJoin ke 'leads'
 *   juga (sebelumnya sempat kelewat), bukan cuma ke 'customers'.
 *
 * - follow_ups punya assigned_to (nullable) + created_by (wajib) — sama
 *   seperti customers/customer_branches. Jadi dipakai pola "sales efektif"
 *   yang sama dengan Costumers controller kamu: COALESCE(assigned_to,
 *   created_by), BUKAN created_by polos seperti draft pertama kemarin.
 *
 * - follow_ups punya follow_up_at (jadwal/rencana follow-up SELANJUTNYA) TERPISAH
 *   dari created_at (kapan record ini dicatat/aktivitasnya beneran terjadi) dan
 *   completed_at (kapan follow-up itu selesai dikerjakan, nullable). SEMPAT
 *   SALAH ASUMSI: sebelumnya "tanggal aktivitas" dipakai COALESCE(completed_at,
 *   follow_up_at), akibatnya record yang baru dibuat hari ini tapi jadwal
 *   follow-up-nya di masa depan (status masih PENDING) jadi nggak muncul di
 *   dashboard hari ini. SUDAH DIKONFIRMASI user: "tanggal aktivitas" yang benar
 *   adalah created_at (kapan dicatat/dilakukan) — dipakai di buildActivitiesUnion(),
 *   countActiveSales(), buildDailyRoster(), buildLeaderboard(), dan followUpBase()
 *   di summary(). follow_up_at TETAP dipakai apa adanya di buildVisitDetail()
 *   (field "Kunjungan Selanjutnya") dan di buildFollowUpReminders() (baru) — daftar
 *   follow-up yang JATUH TEMPO pada rentang yang dipilih & masih PENDING, buat
 *   card "Follow Up Reminder" di dashboard manager.
 *
 * - follow_ups.visit_id (nullable) menandai follow-up yang AUTO-GENERATED dari
 *   sebuah visit (dibuat otomatis sebagai reminder "kunjungan/follow up
 *   selanjutnya" saat visit itu diselesaikan) — bukan aktivitas berdiri sendiri,
 *   cuma metadata milik visit tsb (sudah tampil di detail visit-nya). SEMPAT
 *   KE-DOUBLE-HITUNG: sebelum fix ini, tiap visit yang otomatis bikin follow_ups
 *   record ikut kehitung LAGI sebagai "Follow Up"/"Direct" terpisah di feed,
 *   bikin total aktivitas kelihatan lebih banyak dari jumlah aksi asli sales.
 *   Fix: buildActivitiesUnion(), followUpBase() di summary(), countActiveSales(),
 *   buildDailyRoster(), buildLeaderboard() semua nambahin whereNull('visit_id')
 *   — cuma follow_ups yang BERDIRI SENDIRI (direct call/email/wa, atau follow up
 *   yang dibuat manual bukan hasil auto-generate dari visit) yang dihitung
 *   sebagai aktivitas terpisah. buildFollowUpReminders() SENGAJA TIDAK di-filter
 *   visit_id — follow-up yang jatuh tempo tetap relevan buat reminder manager
 *   walau asalnya dari auto-generate visit.
 *
 * - follow_ups juga punya kolom `result` (NO_RESPONSE/STILL_CONSIDERING/
 *   INTERESTED/NOT_INTERESTED/DEAL) — beda vocabulary dari visits.customer_
 *   response (improved/maintained/no_progress). Frontend perlu mapping label
 *   & warna terpisah untuk ini.
 *
 * MASIH ASUMSI (belum ada konfirmasi langsung dari kamu):
 *
 * 1) branch_id di follow_ups — menurutmu sebelumnya ditambahkan lewat migration
 *    terpisah (sama seperti visit_id/no_reference). Query di bawah masih
 *    leftJoin ke customer_branches via fu.branch_id; kalau nama kolomnya beda
 *    atau ternyata belum ada, sesuaikan join di buildActivitiesUnion() dan
 *    buildFollowUpDetail().
 *
 * 2) SALES AKTIF — untuk tanggal "hari ini": sales dengan visit yang sudah
 *    check-in tapi belum check-out (live). Untuk tanggal lampau/rentang: sales
 *    yang punya minimal 1 aktivitas (visit ATAU follow_up/direct) pada periode
 *    tsb. Sesuaikan kalau definisi "aktif" kamu beda.
 * ============================================================================
 */
class SalesActivityDashboardController extends Controller
{
    /**
     * GET /manager/sales-activity/summary
     * Stat tiles + roster (live/rekap harian) ATAU leaderboard (mode rentang).
     */
    public function summary(SalesActivityValidationIndex $request)
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Halaman ini khusus Manager.', [], 403);
        }

        try {
            $validated = $request->validated();
            [$startDate, $endDate, $isRange] = $this->resolveDateRange($validated);
            $dayCount = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

            $totalSales = DB::table('ms_users as u')
                ->join('ms_role as r', 'r.id_role', '=', 'u.role_id')
                ->whereRaw('LOWER(r.role) = ?', ['sales'])
                ->whereNull('u.deleted_at')
                ->count();

            $visitCount = DB::table('visits as v')
                ->whereNull('v.deleted_at')
                ->whereRaw('v.visit_at::date BETWEEN ? AND ?', [$startDate, $endDate])
                ->count();

            $followUpBase = fn () => DB::table('follow_ups as fu')
                ->whereNull('fu.deleted_at')
                // exclude follow_ups yang auto-generated dari sebuah visit (fu.visit_id
                // terisi) — itu cuma metadata "next visit" milik visit tsb (sudah
                // tampil di detail visit-nya), bukan aktivitas berdiri sendiri. Kalau
                // diikutkan, 1 visit bisa kehitung 2x (sebagai Visit + Follow Up/Direct).
                ->whereNull('fu.visit_id')
                ->whereRaw('fu.created_at::date BETWEEN ? AND ?', [$startDate, $endDate]);

            $followUpCount = $followUpBase()->where('fu.follow_up_type', 'VISIT')->count();
            $directCount   = $followUpBase()->where('fu.follow_up_type', '!=', 'VISIT')->count();

            $activeSales = $this->countActiveSales($startDate, $endDate, $isRange);

            $roster = $isRange
                ? $this->buildLeaderboard($startDate, $endDate)
                : $this->buildDailyRoster($startDate);

            // follow-up yang JATUH TEMPO (follow_up_at) pada rentang ini & masih
            // PENDING — beda konsep dari $followUpCount di atas (yang dihitung dari
            // created_at). Ini buat card "Follow Up Reminder" di manager.
            $followUpReminders = $this->buildFollowUpReminders($startDate, $endDate);

            return ApiResponse::success([
                'mode'       => $isRange ? 'range' : 'day',
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'days'       => $dayCount,
                'stats'      => [
                    'active_sales' => $activeSales,
                    'total_sales'  => $totalSales,
                    'visits'       => $visitCount,
                    'followups'    => $followUpCount,
                    'direct'       => $directCount,
                ],
                'roster'             => $roster,
                'follow_up_reminders' => $followUpReminders,
            ], 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load sales activity summary', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /manager/sales-activity/activities
     * Daftar aktivitas (visit + follow up + direct digabung), dengan
     * search/filter tipe/sort/pagination — sama seperti daftar customer kamu.
     */
    public function activities(SalesActivityValidationIndex $request)
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Halaman ini khusus Manager.', [], 403);
        }

        try {
            $validated = $request->validated();
            [$startDate, $endDate] = $this->resolveDateRange($validated);

            $type    = $validated['type'] ?? 'all';
            $search  = $validated['search'] ?? null;
            $perPage = $validated['per_page'] ?? 10;
            $sortBy  = $validated['sort_by'] ?? 'time';
            $sortDir = strtolower($validated['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

            $union = $this->buildActivitiesUnion($startDate, $endDate, $search);

            $results = DB::query()
                ->fromSub($union, 'activity')
                ->when($type !== 'all', fn ($q) => $q->where('activity_type', $type))
                ->orderBy($sortBy === 'name' ? 'sales_name' : 'sort_time', $sortDir)
                ->paginate($perPage);

            return ApiResponse::paginate(
                SalesActivityResourceCollection::make($results),
                $results->isEmpty() ? 'Tidak ada aktivitas pada rentang ini' : 'Success'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load activities', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /manager/sales-activity/activities/{type}/{id}
     * Detail 1 baris aktivitas — dipanggil saat klik ikon "Detail" di tabel.
     * $type: visit | followup | direct (followup & direct sama-sama baca dari
     * follow_ups, cuma beda tampilan di frontend).
     */
    public function activityDetail(Request $request, string $type, $id)
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Halaman ini khusus Manager.', [], 403);
        }

        try {
            if ($type === 'visit') {
                $detail = $this->buildVisitDetail($id);
            } elseif (in_array($type, ['followup', 'direct'], true)) {
                $detail = $this->buildFollowUpDetail($id);
            } else {
                return ApiResponse::error('Tipe aktivitas tidak dikenal.', [], 422);
            }

            if (! $detail) {
                return ApiResponse::error('Activity not found.', [], 404);
            }

            return ApiResponse::success($detail, 'Success');

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load activity detail', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /* =====================================================================
     *  HELPERS
     * ===================================================================== */

    /**
     * Cek apakah user yang login adalah Manager.
     * NOTE: ganti 'manager' di bawah kalau value ms_role.role asli kamu beda.
     */
    private function isManager(): bool
    {
        $user = auth()->user();

        if (! $user || empty($user->role_id)) {
            return false;
        }

        return DB::table('ms_role')
            ->where('id_role', $user->role_id)
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(role) = ?', ['manager'])
            ->exists();
    }

    /**
     * Terjemahkan input (preset / date / start_date+end_date) jadi
     * [$startDate, $endDate, $isRangeMode].
     */
    private function resolveDateRange(array $validated): array
    {
        $today = now()->toDateString();

        if (! empty($validated['preset'])) {
            switch ($validated['preset']) {
                case 'today':
                    return [$today, $today, false];

                case 'yesterday':
                    $d = now()->subDay()->toDateString();
                    return [$d, $d, false];

                case 'last7':
                    return [now()->subDays(6)->toDateString(), $today, true];

                case 'last_week':
                    $lastMonday = now()->startOfWeek()->subWeek();
                    $lastSunday = $lastMonday->copy()->endOfWeek();
                    return [$lastMonday->toDateString(), $lastSunday->toDateString(), true];

                case 'last30':
                    return [now()->subDays(29)->toDateString(), $today, true];

                case 'last_month':
                    $firstOfPrevMonth = now()->subMonthNoOverflow()->startOfMonth();
                    $lastOfPrevMonth  = now()->subMonthNoOverflow()->endOfMonth();
                    return [$firstOfPrevMonth->toDateString(), $lastOfPrevMonth->toDateString(), true];
            }
        }

        if (! empty($validated['date'])) {
            return [$validated['date'], $validated['date'], false];
        }

        if (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            $isRange = $validated['start_date'] !== $validated['end_date'];
            return [$validated['start_date'], $validated['end_date'], $isRange];
        }

        // default: hari ini
        return [$today, $today, false];
    }

    private function countActiveSales(string $startDate, string $endDate, bool $isRange): int
    {
        // mode live: hanya masuk akal kalau tanggalnya persis "hari ini"
        if (! $isRange && $startDate === now()->toDateString()) {
            return DB::table('visits as v')
                ->whereNull('v.deleted_at')
                ->whereRaw('v.visit_at::date = ?', [$startDate])
                ->whereNotNull('v.check_in_at')
                ->whereNull('v.check_out_at')
                ->distinct()
                ->count('v.sales_id');
        }

        // mode rekap/rentang: sales yang minimal punya 1 aktivitas apapun
        $visitSalesIds = DB::table('visits')
            ->select('sales_id')
            ->whereNull('deleted_at')
            ->whereRaw('visit_at::date BETWEEN ? AND ?', [$startDate, $endDate])
            ->pluck('sales_id');

        $followUpSalesIds = DB::table('follow_ups')
            ->select(DB::raw('COALESCE(assigned_to, created_by) as sales_id'))
            ->whereNull('deleted_at')
            ->whereNull('visit_id')
            ->whereRaw('created_at::date BETWEEN ? AND ?', [$startDate, $endDate])
            ->pluck('sales_id');

        return $visitSalesIds->merge($followUpSalesIds)->filter()->unique()->count();
    }

    /**
     * Roster harian — mode LIVE (hari ini) atau REKAP (tanggal lampau tunggal).
     */
    private function buildDailyRoster(string $date): array
    {
        $liveVisits = DB::table('visits as v')
            ->select(
                'v.sales_id',
                DB::raw("COALESCE(cb.branch_name, c.company_name, l.company_name) as current_target")
            )
            ->leftJoin('customer_branches as cb', 'cb.id', '=', 'v.branch_id')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
            ->whereNull('v.deleted_at')
            ->whereRaw('v.visit_at::date = ?', [$date])
            ->whereNotNull('v.check_in_at')
            ->whereNull('v.check_out_at')
            ->get()
            ->keyBy('sales_id');

        $visitAgg = DB::table('visits as v')
            ->select(
                'v.sales_id',
                DB::raw('COUNT(*) as visit_count'),
                DB::raw('MAX(COALESCE(v.check_out_at, v.check_in_at, v.visit_at)) as last_at')
            )
            ->whereNull('v.deleted_at')
            ->whereRaw('v.visit_at::date = ?', [$date])
            ->groupBy('v.sales_id')
            ->get()
            ->keyBy('sales_id');

        $followUpAgg = DB::table('follow_ups as fu')
            ->select(
                DB::raw('COALESCE(fu.assigned_to, fu.created_by) as sales_id'),
                DB::raw("COUNT(*) FILTER (WHERE fu.follow_up_type = 'VISIT') as followup_count"),
                DB::raw("COUNT(*) FILTER (WHERE fu.follow_up_type != 'VISIT') as direct_count"),
                DB::raw('MAX(fu.created_at) as last_at')
            )
            ->whereNull('fu.deleted_at')
            ->whereNull('fu.visit_id')
            ->whereRaw('fu.created_at::date = ?', [$date])
            ->groupBy(DB::raw('COALESCE(fu.assigned_to, fu.created_by)'))
            ->get()
            ->keyBy('sales_id');

        $salesIds = $visitAgg->keys()->merge($followUpAgg->keys())->unique();

        $salesNames = DB::table('ms_users')
            ->whereIn('id_user', $salesIds)
            ->pluck('fullname', 'id_user');

        return $salesIds->map(function ($salesId) use ($visitAgg, $followUpAgg, $salesNames, $liveVisits) {
            $v = $visitAgg->get($salesId);
            $f = $followUpAgg->get($salesId);
            $live = $liveVisits->get($salesId);

            $lastAt = collect([$v->last_at ?? null, $f->last_at ?? null])
                ->filter()
                ->sort()
                ->last();

            return [
                'sales_id'       => $salesId,
                'sales_name'     => $salesNames[$salesId] ?? '-',
                'is_live'        => $live !== null,
                'current_target' => $live->current_target ?? null,
                'counts' => [
                    'visit'    => (int) ($v->visit_count ?? 0),
                    'followup' => (int) ($f->followup_count ?? 0),
                    'direct'   => (int) ($f->direct_count ?? 0),
                ],
                'last_activity_at' => $lastAt,
            ];
        })
            ->sortByDesc(fn ($row) => $row['is_live'] ? 1 : 0)
            ->values()
            ->toArray();
    }

    /**
     * Leaderboard — mode RENTANG (7 hari terakhir / minggu lalu / dst), agregat
     * per sales, diurutkan dari yang paling aktif.
     */
    private function buildLeaderboard(string $startDate, string $endDate): array
    {
        $visitAgg = DB::table('visits as v')
            ->select('v.sales_id', DB::raw('COUNT(*) as visit_count'))
            ->whereNull('v.deleted_at')
            ->whereRaw('v.visit_at::date BETWEEN ? AND ?', [$startDate, $endDate])
            ->groupBy('v.sales_id')
            ->get()
            ->keyBy('sales_id');

        $followUpAgg = DB::table('follow_ups as fu')
            ->select(
                DB::raw('COALESCE(fu.assigned_to, fu.created_by) as sales_id'),
                DB::raw("COUNT(*) FILTER (WHERE fu.follow_up_type = 'VISIT') as followup_count"),
                DB::raw("COUNT(*) FILTER (WHERE fu.follow_up_type != 'VISIT') as direct_count")
            )
            ->whereNull('fu.deleted_at')
            ->whereNull('fu.visit_id')
            ->whereRaw('fu.created_at::date BETWEEN ? AND ?', [$startDate, $endDate])
            ->groupBy(DB::raw('COALESCE(fu.assigned_to, fu.created_by)'))
            ->get()
            ->keyBy('sales_id');

        $salesIds = $visitAgg->keys()->merge($followUpAgg->keys())->unique();

        $salesNames = DB::table('ms_users')
            ->whereIn('id_user', $salesIds)
            ->pluck('fullname', 'id_user');

        return $salesIds->map(function ($salesId) use ($visitAgg, $followUpAgg, $salesNames) {
            $v = $visitAgg->get($salesId);
            $f = $followUpAgg->get($salesId);

            $counts = [
                'visit'    => (int) ($v->visit_count ?? 0),
                'followup' => (int) ($f->followup_count ?? 0),
                'direct'   => (int) ($f->direct_count ?? 0),
            ];

            return [
                'sales_id'   => $salesId,
                'sales_name' => $salesNames[$salesId] ?? '-',
                'counts'     => $counts,
                'total'      => array_sum($counts),
            ];
        })
            ->sortByDesc('total')
            ->values()
            ->toArray();
    }

    /**
     * Union visits + follow_ups jadi satu bentuk kolom yang seragam, siap
     * dipakai fromSub() di activities().
     */
    private function buildActivitiesUnion(string $startDate, string $endDate, ?string $search)
    {
        $visitQuery = DB::table('visits as v')
            ->select([
                DB::raw("'visit' as activity_type"),
                'v.id',
                'v.sales_id',
                'sales.fullname as sales_name',
                DB::raw("COALESCE(cb.branch_name, c.company_name, l.company_name) as target_name"),
                DB::raw("CASE WHEN cb.id IS NOT NULL THEN 'Branch' WHEN l.id IS NOT NULL THEN 'Lead' ELSE NULL END as target_note"),
                DB::raw('v.visit_at::date as activity_date'),
                DB::raw("TO_CHAR(COALESCE(v.check_in_at, v.visit_at), 'HH24:MI') as activity_time"),
                DB::raw('COALESCE(v.check_in_at, v.visit_at) as sort_time'),
                DB::raw("CASE
                    WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NULL THEN 'Sedang check-in, belum check-out'
                    WHEN v.check_out_at IS NOT NULL THEN CONCAT('Selesai — hasil: ', COALESCE(v.customer_response, '-'))
                    ELSE 'Belum check-in'
                END as note"),
            ])
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'v.sales_id')
            ->leftJoin('customer_branches as cb', 'cb.id', '=', 'v.branch_id')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
            ->whereNull('v.deleted_at')
            ->whereRaw('v.visit_at::date BETWEEN ? AND ?', [$startDate, $endDate]);

        $followUpQuery = DB::table('follow_ups as fu')
            ->select([
                DB::raw("CASE WHEN fu.follow_up_type = 'VISIT' THEN 'followup' ELSE 'direct' END as activity_type"),
                'fu.id',
                DB::raw('COALESCE(fu.assigned_to, fu.created_by) as sales_id'),
                DB::raw('COALESCE(assigned_sales.fullname, creator_sales.fullname) as sales_name'),
                // follow_ups wajib salah satu dari lead_id/customer_id (chk_followups_owner),
                // jadi COALESCE-nya harus ikut leads juga, bukan cuma customers/branch.
                DB::raw("COALESCE(cb.branch_name, c.company_name, l.company_name) as target_name"),
                DB::raw("CASE WHEN cb.id IS NOT NULL THEN 'Branch' WHEN l.id IS NOT NULL THEN 'Lead' ELSE NULL END as target_note"),
                // "tanggal aktivitas" follow_ups = created_at (kapan dicatat/dilakukan),
                // BUKAN follow_up_at (itu jadwal follow-up SELANJUTNYA — dipakai
                // terpisah di buildFollowUpReminders()).
                DB::raw('fu.created_at::date as activity_date'),
                DB::raw("TO_CHAR(fu.created_at, 'HH24:MI') as activity_time"),
                DB::raw('fu.created_at as sort_time'),
                DB::raw('COALESCE(fu.subject, fu.notes) as note'),
            ])
            // dua kali join ms_users (assigned_to & created_by) lalu COALESCE hasilnya —
            // supaya tidak perlu join pakai expression COALESCE() langsung di kondisi ON.
            ->leftJoin('ms_users as assigned_sales', 'assigned_sales.id_user', '=', 'fu.assigned_to')
            ->leftJoin('ms_users as creator_sales', 'creator_sales.id_user', '=', 'fu.created_by')
            ->leftJoin('customer_branches as cb', 'cb.id', '=', 'fu.branch_id')
            ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
            ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
            ->whereNull('fu.deleted_at')
            // exclude follow_ups yang auto-generated dari sebuah visit (fu.visit_id
            // terisi) — itu cuma metadata "next visit" milik visit tsb (sudah tampil
            // di detail visit-nya via next_visit_at dkk), bukan aktivitas berdiri
            // sendiri. Kalau tidak di-exclude, 1 visit selesai bisa kehitung dobel:
            // sekali sebagai "Visit", sekali lagi sebagai "Follow Up"/"Direct".
            ->whereNull('fu.visit_id')
            ->whereRaw('fu.created_at::date BETWEEN ? AND ?', [$startDate, $endDate]);

        if ($search) {
            $visitQuery->where(function ($q) use ($search) {
                $q->where('sales.fullname', 'ILIKE', "%{$search}%")
                    ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('cb.branch_name', 'ILIKE', "%{$search}%")
                    ->orWhere('l.company_name', 'ILIKE', "%{$search}%");
            });
            $followUpQuery->where(function ($q) use ($search) {
                $q->where('assigned_sales.fullname', 'ILIKE', "%{$search}%")
                    ->orWhere('creator_sales.fullname', 'ILIKE', "%{$search}%")
                    ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('cb.branch_name', 'ILIKE', "%{$search}%")
                    ->orWhere('l.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('fu.subject', 'ILIKE', "%{$search}%")
                    ->orWhere('fu.notes', 'ILIKE', "%{$search}%");
            });
        }

        return $visitQuery->unionAll($followUpQuery);
    }

    /**
     * Follow-up yang JATUH TEMPO (follow_up_at) pada rentang yang dipilih & masih
     * PENDING — beda konsep dari buildActivitiesUnion() (yang berbasis created_at).
     * Ini buat card "Follow Up Reminder" di dashboard manager, supaya manager tahu
     * sales mana yang hari ini/pada rentang ini harus follow up ke siapa.
     * is_overdue = true kalau follow_up_at sudah lewat dari tanggal hari ini nyata
     * (bukan cuma di luar rentang yang lagi dipilih di dashboard).
     */
    private function buildFollowUpReminders(string $startDate, string $endDate): array
    {
        return DB::table('follow_ups as fu')
            ->select([
                'fu.id',
                'fu.follow_up_code',
                DB::raw('COALESCE(fu.assigned_to, fu.created_by) as sales_id'),
                DB::raw('COALESCE(assigned_sales.fullname, creator_sales.fullname) as sales_name'),
                DB::raw('COALESCE(cb.branch_name, c.company_name, l.company_name) as target_name'),
                DB::raw("CASE WHEN cb.id IS NOT NULL THEN 'Branch' WHEN l.id IS NOT NULL THEN 'Lead' ELSE NULL END as target_note"),
                'fu.follow_up_type',
                'fu.subject',
                DB::raw("TO_CHAR(fu.follow_up_at, 'YYYY-MM-DD') as follow_up_date"),
                DB::raw("TO_CHAR(fu.follow_up_at, 'HH24:MI') as follow_up_time"),
                DB::raw('(fu.follow_up_at::date < CURRENT_DATE) as is_overdue'),
            ])
            ->leftJoin('ms_users as assigned_sales', 'assigned_sales.id_user', '=', 'fu.assigned_to')
            ->leftJoin('ms_users as creator_sales', 'creator_sales.id_user', '=', 'fu.created_by')
            ->leftJoin('customer_branches as cb', 'cb.id', '=', 'fu.branch_id')
            ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
            ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
            ->whereNull('fu.deleted_at')
            ->where('fu.status', 'PENDING')
            ->whereRaw('fu.follow_up_at::date BETWEEN ? AND ?', [$startDate, $endDate])
            ->orderBy('fu.follow_up_at', 'asc')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    /**
     * Detail 1 visit — struktur field-nya sengaja disamakan dengan
     * Visits::getVisitTargetMap() yang sudah ada, supaya modal detail di
     * frontend bisa reuse helper yang sama (sanitizeRichText, dsb).
     */
    private function buildVisitDetail($id)
    {
        return DB::table('visits as v')
            ->select([
                'v.id',
                'v.sales_id',
                'sales.fullname as sales_name',
                DB::raw("COALESCE(cb.branch_name, c.company_name, l.company_name) as target_name"),
                DB::raw('COALESCE(cb.address, c.address, l.address) as address'),
                DB::raw('COALESCE(cb.phone, c.phone, l.phone) as phone'),
                DB::raw('COALESCE(cb.email, c.email, l.email) as email'),
                DB::raw("TO_CHAR(v.visit_at, 'HH24:MI') as visit_at"),
                DB::raw("TO_CHAR(v.check_in_at, 'HH24:MI') as check_in_at"),
                DB::raw("TO_CHAR(v.check_out_at, 'HH24:MI') as check_out_at"),
                DB::raw("CASE WHEN v.check_in_at IS NOT NULL THEN TO_CHAR(v.check_in_at - v.visit_at, 'HH24:MI:SS') END as dur_to_check_in"),
                DB::raw("CASE WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NOT NULL THEN TO_CHAR(v.check_out_at - v.check_in_at, 'HH24:MI:SS') END as dur_check_in_to_out"),
                DB::raw("CASE WHEN v.check_in_at IS NOT NULL AND v.check_out_at IS NOT NULL THEN TO_CHAR(v.check_out_at - v.visit_at, 'HH24:MI:SS') END as dur_total"),
                'v.photo',
                DB::raw("CASE WHEN v.photo IS NOT NULL AND v.photo != '' THEN CONCAT('" . asset('storage') . "/', v.photo) ELSE NULL END as photo_url"),
                'v.check_out_file',
                DB::raw("CASE WHEN v.check_out_file IS NOT NULL AND v.check_out_file != '' THEN CONCAT('" . asset('storage') . "/', v.check_out_file) ELSE NULL END as check_out_file_url"),
                'v.customer_response as response',
                'v.notes',
                'v.has_complaint',
                'v.complaint_detail',
                'v.has_potential_order',
                'v.potential_order_detail',
                'fu.follow_up_at as next_visit_at',
                'fu.notes as next_visit_notes',
                'fu.follow_up_type as next_visit_type',
                'fu.status as next_visit_status',
            ])
            ->leftJoin('ms_users as sales', 'sales.id_user', '=', 'v.sales_id')
            ->leftJoin('customer_branches as cb', 'cb.id', '=', 'v.branch_id')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->leftJoin('leads as l', 'l.id', '=', 'v.lead_id')
            ->leftJoin('follow_ups as fu', function ($join) {
                $join->on('fu.visit_id', '=', 'v.id')->whereNull('fu.deleted_at');
            })
            ->where('v.id', $id)
            ->whereNull('v.deleted_at')
            ->first();
    }

    /**
     * Detail 1 follow up / direct — dipakai untuk activity_type 'followup'
     * maupun 'direct' (sumber tabelnya sama, follow_ups).
     */
    private function buildFollowUpDetail($id)
    {
        return DB::table('follow_ups as fu')
            ->select([
                'fu.id',
                'fu.follow_up_code',
                DB::raw('COALESCE(fu.assigned_to, fu.created_by) as sales_id'),
                DB::raw('COALESCE(assigned_sales.fullname, creator_sales.fullname) as sales_name'),
                // sama seperti buildActivitiesUnion: wajib ikutkan leads karena
                // chk_followups_owner (lead_id XOR customer_id).
                DB::raw('COALESCE(cb.branch_name, c.company_name, l.company_name) as target_name'),
                DB::raw('COALESCE(cb.address, c.address, l.address) as address'),
                DB::raw('COALESCE(cb.phone, c.phone, l.phone) as phone'),
                DB::raw('COALESCE(cb.email, c.email, l.email) as email'),
                DB::raw("TO_CHAR(fu.follow_up_at, 'HH24:MI') as scheduled_time"),
                DB::raw("TO_CHAR(fu.completed_at, 'HH24:MI') as completed_time"),
                DB::raw("TO_CHAR(COALESCE(fu.completed_at, fu.follow_up_at), 'HH24:MI') as time"),
                'fu.follow_up_type',
                'fu.subject',
                'fu.notes',
                'fu.status',
                'fu.result as response',
            ])
            ->leftJoin('ms_users as assigned_sales', 'assigned_sales.id_user', '=', 'fu.assigned_to')
            ->leftJoin('ms_users as creator_sales', 'creator_sales.id_user', '=', 'fu.created_by')
            ->leftJoin('customer_branches as cb', 'cb.id', '=', 'fu.branch_id')
            ->leftJoin('customers as c', 'c.id', '=', 'fu.customer_id')
            ->leftJoin('leads as l', 'l.id', '=', 'fu.lead_id')
            ->where('fu.id', $id)
            ->whereNull('fu.deleted_at')
            ->first();
    }
}