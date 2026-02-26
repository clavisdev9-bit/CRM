<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class FollowUpLeadResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Tentukan apakah ini follow up ke Customer atau Lead
        $isCustomer = !empty($this->customer_id);

        return [
            'id'             => $this->id,
            'lead_id'        => $this->lead_id,
            'customer_id'    => $this->customer_id,

            'follow_up_code' => $this->follow_up_code,
            'follow_up_type' => $this->follow_up_type,
            'subject'        => $this->subject,
            'notes'          => $this->notes,

            /* ===== STATUS ===== */
            'status'          => $this->status,
            'computed_status' => $this->computed_status,
            'is_overdue'      => (bool) $this->is_overdue,

            /* ===== TARGET TYPE ===== */
            'target_type' => $isCustomer ? 'CUSTOMER' : 'LEAD',

            /* ===== TARGET NAME ===== */
            'target_name' => $isCustomer
                ? ($this->customer_company_name ?? '-')
                : ($this->lead_company_name ?? '-'),

            /* ===== CUSTOMER DATA ===== */
            'customer_company_name' => $this->customer_company_name,
            'customer_contact_name' => $this->customer_contact_name,
            'customer_status'       => $this->customer_status ?? null,

            /* ===== LEAD DATA ===== */
            'lead_company_name' => $this->lead_company_name,
            'lead_contact_name' => $this->lead_contact_name,
            'lead_status'       => $this->lead_status,

            /* ===== SALES ===== */
            'sales_name' => $this->sales_name,

            /* ===== DATE FORMAT ===== */
            'follow_up_at' => $this->follow_up_at
                ? Carbon::parse($this->follow_up_at)->format('Y-m-d H:i:s')
                : null,

            'created_at' => $this->created_at
                ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}