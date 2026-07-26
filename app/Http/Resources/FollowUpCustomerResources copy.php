<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class FollowUpCustomerResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'customer_id'     => $this->customer_id,
            'follow_up_code'  => $this->follow_up_code,
            'follow_up_type'  => $this->follow_up_type,
            'subject'         => $this->subject,
            'notes'           => $this->notes,

            /* ===== STATUS ===== */
            'status'          => $this->status,
            'computed_status' => $this->computed_status,
            'is_overdue'      => (bool) $this->is_overdue,

            /* ===== TARGET (CUSTOMER ONLY) ===== */
            'target_name'            => $this->customer_company_name ?? '-',
            'customer_company_name'  => $this->customer_company_name,
            'customer_status'        => $this->customer_status,

            /* ===== SALES ===== */
            'sales_name' => $this->sales_name,
            // 'visit_result' => $this->visit_result,

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