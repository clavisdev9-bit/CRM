<?php

namespace App\Http\Controllers\Api\Users\Sales\VisitTargets;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Resources\VisitTargetResource;
use App\Traits\BuildsVisitTargetQuery;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * TARGET VISIT -- sisi SALES (read-only). Sales cuma bisa lihat target &
 * progress MILIK DIRINYA SENDIRI, nggak bisa create/edit/hapus (itu wewenang
 * Manager, lihat VisitTargetController).
 */
class SalesVisitTargetController extends Controller
{
    use BuildsVisitTargetQuery;

    /**
     * GET /sales/visit-targets?period_month=2026-08-01
     */
    public function myTargets(Request $request)
    {
        $user = auth()->user();

        if (! $user || empty($user->id_user)) {
            return ApiResponse::error('Unauthorized.', [], 403);
        }

        try {
            $periodMonth = $request->query('period_month')
                ? Carbon::parse($request->query('period_month'))->startOfMonth()->toDateString()
                : now()->startOfMonth()->toDateString();

            $rows = $this->baseVisitTargetQuery()
                ->where('vt.sales_id', $user->id_user)
                ->where('vt.period_month', $periodMonth)
                ->orderBy('vt.created_at', 'desc')
                ->get();

            return ApiResponse::success(
                VisitTargetResource::collection($rows),
                $rows->isEmpty() ? 'Belum ada target visit pada bulan ini' : 'Success'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load visit targets', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}