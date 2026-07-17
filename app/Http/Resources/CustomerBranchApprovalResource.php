<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CustomerBranchApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            // =========================
            // BRANCH
            // =========================
            'id' => $this->id ?? null,
            'customer_id' => $this->customer_id ?? null,
            'branch_code' => $this->branch_code ?? null,
            'branch_name' => $this->branch_name ?? null,
            'is_main_branch' => (bool) ($this->is_main_branch ?? false),
            'status' => $this->status ?? null,

            // =========================
            // COMPANY INDUK (join dari tabel customers)
            // =========================
            'company_name' => $this->company_name ?? null,
            'customer_code' => $this->customer_code ?? null,

            // =========================
            // APPROVAL
            // =========================
            'approval_status' => $this->approval_status ?? null,
            'submitted_for_approval_at' => isset($this->submitted_for_approval_at)
                ? Carbon::parse($this->submitted_for_approval_at)->format('Y-m-d H:i:s')
                : null,
            'approved_by' => $this->approved_by ?? null,
            'approved_name' => $this->approved_name ?? null,
            'approved_at' => isset($this->approved_at)
                ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s')
                : null,
            'approval_note' => $this->approval_note ?? null,
            'approval_revision' => $this->approval_revision ?? 0,

            // =========================
            // INFORMATION
            // =========================
            'address' => $this->address ?? null,
            'city' => $this->city ?? null,
            'contact_name' => $this->contact_name ?? null,
            'email' => $this->email ?? null,
            'phone' => $this->phone ?? null,
            'notes' => $this->notes ?? null,

            // =========================
            // OWNERSHIP
            // =========================
            'assigned_to' => $this->assigned_to ?? null,
            'assigned_name' => $this->assigned_name ?? null,

            'created_by' => $this->created_by ?? null,
            'created_by_name' => $this->created_by_name ?? null,

            // =========================
            // AUDIT
            // =========================
            'created_at' => isset($this->created_at)
                ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s')
                : null,

            'updated_at' => isset($this->updated_at)
                ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}