<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class FollowUpResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'follow_up_type' => $this->follow_up_type,
            'subject'        => $this->subject,
            'notes'          => $this->notes,
            'status'         => $this->status,
            'follow_up_code'         => $this->follow_up_code,

            /* ===== TARGET FIX ===== */
            'target_name' =>
                $this->customer_company_name
                ?? $this->lead_company_name
                ?? '-',

            'target_source' =>
                $this->customer_company_name
                    ? 'CUSTOMER'
                    : ($this->lead_company_name ? 'LEAD' : '-'),

            /* ===== OPTIONAL DETAIL ===== */
            'lead_company_name'     => $this->lead_company_name,
            'lead_status'     => $this->lead_status,
            'customer_company_name' => $this->customer_company_name,

            // 'follow_up_at' => optional($this->follow_up_at)->toDateTimeString(),
            // 'created_at'   => optional($this->created_at)->toDateTimeString(),
            'follow_up_at' => $this->follow_up_at
    ? Carbon::parse($this->follow_up_at)->toDateTimeString()
    : null,

'created_at' => $this->created_at
    ? Carbon::parse($this->created_at)->toDateTimeString()
    : null,
        ];
    }
}
