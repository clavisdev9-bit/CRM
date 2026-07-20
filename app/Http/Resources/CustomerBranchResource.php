<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerBranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Aman untuk Eloquent model dan stdClass dari DB::table().
        $contacts = collect($this->contacts ?? [])
            ->map(fn ($contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'position' => $contact->position ?? null,
                'email' => $contact->email ?? null,
                'phone' => $contact->phone ?? null,
                'is_primary' => (bool) ($contact->is_primary ?? false),
                'status' => $contact->status ?? 'Active',
                'notes' => $contact->notes ?? null,
            ])
            ->values();

        $primaryContact = $contacts->firstWhere('is_primary', true)
            ?? $contacts->first();

        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'branch_code' => $this->branch_code,
            'branch_name' => $this->branch_name,
            'is_main_branch' => (bool) $this->is_main_branch,
            'status' => $this->status,

            'approval_status' => $this->approval_status ?? null,
            'submitted_for_approval_at' => $this->submitted_for_approval_at ?? null,
            'approved_by' => $this->approved_by ?? null,
            'approved_at' => $this->approved_at ?? null,
            'approval_note' => $this->approval_note ?? null,
            'approval_revision' => $this->approval_revision ?? 0,

            'address' => $this->address,
            'city' => $this->city,
            'notes' => $this->notes,

            // Frontend baru
            'contacts' => $contacts,

            // Fallback untuk UI/data lama
            'contact_name' => $primaryContact['name'] ?? $this->contact_name ?? null,
            'email' => $primaryContact['email'] ?? $this->email ?? null,
            'phone' => $primaryContact['phone'] ?? $this->phone ?? null,

            'assigned_to' => $this->assigned_to ?? null,
            'assigned_name' => $this->assigned_name ?? null,

            'created_by' => $this->created_by ?? null,
            'creator_name' => $this->creator_name ?? $this->created_by_name ?? null,

            'created_at' => $this->created_at
                ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s')
                : null,
            'updated_at' => $this->updated_at
                ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}