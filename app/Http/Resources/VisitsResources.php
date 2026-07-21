<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitsResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'visit_code'   => $this->visit_code,

            // Target info
            'visit_type'      => $this->visit_type,
            'company_name'    => $this->company_name,
            'target_contact'  => $this->target_contact,
            'target_phone'    => $this->target_phone,
            'target_address'  => $this->target_address,
            'lead_id'         => $this->lead_id,
            'customer_id'     => $this->customer_id,

            // ── BRANCH INFO (baru) ──
            'branch_id'    => $this->branch_id,
            'target_type'  => $this->target_type ?? null, // 'BRANCH' | 'HEAD_OFFICE' | null (lead)
            'branch_code'  => $this->branch_code ?? null,
            'branch_name'  => $this->branch_name ?? null,
            'branch_city'  => $this->branch_city ?? null,

            // Sales info
            'sales_id'    => $this->sales_id,
            'sales_name'  => $this->sales_name ?? null,

            // Visit time
            'visit_at'     => $this->visit_at,
            'check_in_at'  => $this->check_in_at,
            'check_out_at' => $this->check_out_at,

            // Duration
            'time_from_visit_to_check_in'     => $this->time_from_visit_to_check_in,
            'time_from_check_in_to_check_out' => $this->time_from_check_in_to_check_out,
            'total_time_result'               => $this->total_time_result,

            // Status
            'visit_progress'     => $this->visit_progress,
            'visit_status'       => $this->visit_status,
            'visit_status_label' => $this->visit_status_label,

            // Location
            'latitude'     => $this->latitude,
            'longitude'    => $this->longitude,
            'gps_snapshot' => $this->gps_snapshot,

            // Visit detail
            'photo'       => $this->photo,
            'photo_url'   => $this->photo_url,
            'notes'       => $this->notes,
            'visit_result' => $this->visit_result,
            'customer_response'     => $this->customer_response,
            'has_complaint'         => $this->has_complaint ?? null,
            'complaint_detail'      => $this->complaint_detail ?? null,
            'has_potential_order'   => $this->has_potential_order ?? null,
            'potential_order_detail' => $this->potential_order_detail ?? null,

            // Meta
            'created_by' => $this->created_by,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}