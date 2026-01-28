<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class VisitCustomersDataResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_code' => $this->customer_code,
            'company_name' => $this->company_name,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'customer_status' => $this->customer_status,
            'notes' => $this->notes,

            // =========================
            // LEAD RELATION
            // =========================
            'lead_id' => $this->lead_id,
            'lead_company_name' => $this->lead_company_name ?? null,
            'lead_source' => $this->lead_source ?? null,
            'lead_status' => $this->lead_status ?? null,
            'lead_category_id' => $this->lead_category_id,
            'lead_category_name' => $this->category_name ?? null,
            'industry_id' => $this->industry_id,
            'industry_name' => $this->industry_name ?? null,

            // =========================
            // OWNERSHIP
            // =========================
            'assigned_to' => $this->assigned_to,
            'assigned_name' => $this->assigned_name ?? null,
            'created_by' => $this->created_by,
            'owner_name' => $this->owner_name ?? null,

            // =========================
            // ACTIVITY
            // =========================
            'converted_at' => $this->converted_at ? Carbon::parse($this->converted_at)->format('Y-m-d H:i:s') : null,

            // =========================
            // AUDIT
            // =========================
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null,
        ];
    }
}
