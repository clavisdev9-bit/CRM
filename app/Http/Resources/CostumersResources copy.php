<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CostumersResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            // =========================
            // CUSTOMER
            // =========================
            'id' => $this->id,
            'customer_code' => $this->customer_code,
            'company_name' => $this->company_name,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,

            // =========================
            // STATUS
            // =========================
            'customer_status' => $this->customer_status,

            // =========================
            // APPROVAL
            // =========================
            'approval_status' => $this->approval_status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at
                ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s')
                : null,
            'approval_note' => $this->approval_note,
            'approval_revision' => $this->approval_revision,

            // =========================
            // NOTES
            // =========================
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
            // BRANCH
            // =========================
            'branch_count' => (int) ($this->branch_count ?? 0),

            // =========================
// BRANCH
// =========================
'branch_count' => (int) ($this->branch_count ?? 0),

'branches' => collect(json_decode($this->branches ?? '[]', true))
    ->map(function ($branch) {
        return [
            'id'              => $branch['id'] ?? null,
            'branch_code'     => $branch['branch_code'] ?? null,
            'branch_name'     => $branch['branch_name'] ?? null,
            'contact_name'    => $branch['contact_name'] ?? null,
            'email'           => $branch['email'] ?? null,
            'phone'           => $branch['phone'] ?? null,
            'address'         => $branch['address'] ?? null,
            'city'            => $branch['city'] ?? null,
            'assigned_to'     => $branch['assigned_to'] ?? null,
            'created_by'      => $branch['created_by'] ?? null,
            'status'          => $branch['status'] ?? null,
            'approval_status' => $branch['approval_status'] ?? null,
            'approved_by'     => $branch['approved_by'] ?? null,
            'approved_at'     => $branch['approved_at'] ?? null,
        ];
    })
    ->values(),

            // =========================
            // ACTIVITY
            // =========================
            'converted_at' => $this->converted_at
                ? Carbon::parse($this->converted_at)->format('Y-m-d H:i:s')
                : null,

            // =========================
            // AUDIT
            // =========================
            'created_at' => $this->created_at
                ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s')
                : null,

            'updated_at' => $this->updated_at
                ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}