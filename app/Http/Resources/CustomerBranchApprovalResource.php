<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerBranchApprovalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [

            /**
             * =====================================================
             * BRANCH
             * =====================================================
             */
            'id' => $this->id,
            'customer_id' => $this->customer_id,

            'branch_code' => $this->branch_code,
            'branch_name' => $this->branch_name,

            'is_main_branch' => (bool) $this->is_main_branch,

            'status' => $this->status,

            /**
             * =====================================================
             * CUSTOMER
             * =====================================================
             */
            'company_name' => $this->company_name,
            'customer_code' => $this->customer_code,

            /**
             * =====================================================
             * PRIMARY CONTACT
             * =====================================================
             */
            'contact_name' => $this->contact_name,
            'contact_position' => $this->contact_position,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,

            /**
             * =====================================================
             * LOCATION
             * =====================================================
             */
            'address' => $this->address,
            'city' => $this->city,

            /**
             * =====================================================
             * CURRENT OWNER
             * =====================================================
             */
            'assigned_to' => $this->assigned_to,
            'assigned_name' => $this->assigned_name,

            /**
             * =====================================================
             * SUBMITTED BY
             * =====================================================
             */
            'created_by' => $this->created_by,
            'created_by_name' => $this->created_by_name,

            /**
             * =====================================================
             * APPROVAL
             * =====================================================
             */
            'approval_status' => $this->approval_status,

            'is_pending' => $this->approval_status === 'pending',
            'is_approved' => $this->approval_status === 'approved',
            'is_rejected' => $this->approval_status === 'rejected',

            'submitted_for_approval_at' => $this->submitted_for_approval_at
                ? Carbon::parse($this->submitted_for_approval_at)->format('Y-m-d H:i:s')
                : null,

            'approved_by' => $this->approved_by,
            'approved_name' => $this->approved_name,

            'approved_at' => $this->approved_at
                ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s')
                : null,

            'approval_note' => $this->approval_note,

            'approval_revision' => (int) ($this->approval_revision ?? 0),

            /**
             * =====================================================
             * NOTES
             * =====================================================
             */
            'notes' => $this->notes,

            /**
             * =====================================================
             * AUDIT
             * =====================================================
             */
            'created_at' => $this->created_at
                ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s')
                : null,

            'updated_at' => $this->updated_at
                ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}