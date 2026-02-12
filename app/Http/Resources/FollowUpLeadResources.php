<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class FollowUpLeadResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'lead_id'         => $this->lead_id,
            'follow_up_code'  => $this->follow_up_code,
            'follow_up_type'  => $this->follow_up_type,
            'subject'         => $this->subject,
            'notes'           => $this->notes,

            /* ===== STATUS ===== */
            'status'          => $this->status,
            'computed_status' => $this->computed_status, // dari query CASE
            'is_overdue'      => (bool) $this->is_overdue,

            /* ===== TARGET (LEAD ONLY) ===== */
            'target_name'     => $this->lead_company_name ?? '-',
            'lead_company_name' => $this->lead_company_name,
            'lead_status'       => $this->lead_status,

            /* ===== SALES ===== */
            'sales_name' => $this->sales_name,

            /* ===== DATE FORMAT ===== */
            'follow_up_at' => optional($this->follow_up_at)
                                ? Carbon::parse($this->follow_up_at)->format('Y-m-d H:i:s')
                                : null,

            'created_at'   => optional($this->created_at)
                                ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s')
                                : null,
        ];
    }
}
