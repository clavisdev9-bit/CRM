<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitTargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $target   = (int) $this->target_count;
        $achieved = (int) $this->achieved_count;

        return [
            'id'              => $this->id,
            'target_code'     => $this->target_code,
            'sales_id'        => $this->sales_id,
            'sales_name'      => $this->sales_name,
            'target_type'     => $this->target_type,   // 'customer' | 'branch'
            'target_name'     => $this->target_name,
            'target_note'     => $this->target_note,   // 'Customer' | 'Branch'

            // ── kolom asli dari visit_targets, TIDAK diubah artinya --
            // customer_id ini bisa NULL kalau target_type = 'branch' (lihat
            // constraint chk_visit_targets_owner). Dipertahankan apa adanya
            // buat backward compat kalau ada consumer lain (misal form edit
            // Manager) yang gantung ke semantik asli ini.
            'customer_id'     => $this->customer_id,
            'branch_id'       => $this->branch_id,

            // ══════════════════════════════════════════════════════════
            // TOMBOL "VISIT" (Sales side, MyTargetVisit.vue) -- lihat
            // penjelasan lengkap di BuildsVisitTargetQuery::baseVisitTargetQuery().
            // visit_customer_id SELALU keisi ID customer head office (baik
            // target tipe customer maupun branch), dipakai manggil
            // startVisitCustomers(visit_customer_id, branch_id) dari
            // visitSalesStore.js.
            // ══════════════════════════════════════════════════════════
            'visit_customer_id' => $this->visit_customer_id,
            'company_name'      => $this->company_name,
            'branch_name'       => $this->branch_name,
            'city'              => $this->city,
            'contact_name'      => $this->contact_name,
            'customer_status'   => $this->customer_status,
            'latitude'          => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude'         => $this->longitude !== null ? (float) $this->longitude : null,

            'target_count'    => $target,
            'achieved_count'  => $achieved,
            'remaining'       => max($target - $achieved, 0),
            'percentage'      => $target > 0 ? min((int) round(($achieved / $target) * 100), 100) : 0,
            'is_achieved'     => $achieved >= $target,
            'period_month'    => $this->period_month,
            'notes'           => $this->notes,
            'created_by'      => $this->created_by,
            'created_by_name' => $this->created_by_name,
            'created_at'      => $this->created_at,
        ];
    }
}