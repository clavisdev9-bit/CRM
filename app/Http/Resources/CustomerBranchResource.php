<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CustomerBranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            // =========================
            // BRANCH
            // =========================
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'branch_code' => $this->branch_code,
            'branch_name' => $this->branch_name,
            'is_main_branch' => (bool) $this->is_main_branch,
            'status' => $this->status,

            // =========================
            // INFORMATION
            // =========================
            'address' => $this->address,
            'city' => $this->city,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'notes' => $this->notes,

            // =========================
            // OWNERSHIP
            // =========================
            'assigned_to' => $this->assigned_to,
            'assigned_name' => $this->assigned_name ?? null,

            'created_by' => $this->created_by,
            'created_by_name' => $this->created_by_name ?? null,

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