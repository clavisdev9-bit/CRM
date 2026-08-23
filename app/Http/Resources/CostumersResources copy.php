<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CostumersResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        $displayType = $this->resource->display_type ?? 'customer';

        $rawBranches = $this->resource->branches ?? null;

        $branches = collect(
            is_string($rawBranches)
                ? json_decode($rawBranches, true)
                : ($rawBranches ?? [])
        )->values();

        // ── CONTACTS ──
        // Bisa berupa Collection (dari showCostumers/store/update yang
        // saya tambahkan) atau tidak ada sama sekali (dari query list
        // lama yang belum di-join). Fallback ke collect kosong.
        $rawContacts = $this->resource->contacts ?? collect();

        $contacts = collect($rawContacts)->map(function ($contact) {
            $contact = (array) $contact;

            return [
                'id'         => $contact['id'] ?? null,
                'name'       => $contact['name'] ?? null,
                'position'   => $contact['position'] ?? null,
                'email'      => $contact['email'] ?? null,
                'phone'      => $contact['phone'] ?? null,
                'is_primary' => (bool) ($contact['is_primary'] ?? false),
            ];
        })->values();

        /**
         * ==========================================================
         * BRANCH CARD
         * ==========================================================
         */
        if ($displayType === 'branch') {

            $branch = $branches->first();

            return [

                'display_type' => 'branch',

                'customer' => [
                    'id' => $this->resource->id ?? null,
                    'customer_code' => $this->resource->customer_code ?? null,
                    'company_name' => $this->resource->company_name ?? null,
                ],

                'branch' => [
                    'id' => $branch['id'] ?? null,
                    'branch_code' => $branch['branch_code'] ?? null,
                    'branch_name' => $branch['branch_name'] ?? null,
                    'contact_name' => $branch['contact_name'] ?? null,
                    'email' => $branch['email'] ?? null,
                    'phone' => $branch['phone'] ?? null,
                    'address' => $branch['address'] ?? null,
                    'city' => $branch['city'] ?? null,

                    'status' => $branch['status'] ?? null,
                    'approval_status' => $branch['approval_status'] ?? null,
                     'approval_note' => $branch['approval_note'] ?? null,
                    'approval_revision' => $branch['approval_revision'] ?? null,

                    'assigned_to' => $branch['assigned_to'] ?? null,
                    'assigned_name' => $branch['assigned_name'] ?? null,

                    'created_by' => $branch['created_by'] ?? null,
                    'owner_name' => $branch['owner_name'] ?? null,

                    'approved_by' => $branch['approved_by'] ?? null,

                    'approved_at' => !empty($branch['approved_at'])
                        ? Carbon::parse($branch['approved_at'])->format('Y-m-d H:i:s')
                        : null,

                    // ── TRIGGER FOLLOW UP (per branch, sudah dihitung di controller) ──
                    'followup_due' => (bool) ($branch['followup_due'] ?? false),
                    'followup_due_date' => $branch['followup_due_date'] ?? null,
                    'followup_overdue' => (bool) ($branch['followup_overdue'] ?? false),
                ],

                // ── TRIGGER FOLLOW UP (level card, dipakai frontend buat border merah/badge) ──
                // Sudah digabung di controller (customer-level + branch-level yang relevan).
                'followup_due' => (bool) ($this->resource->followup_due ?? false),
                'followup_due_date' => $this->resource->followup_due_date ?? null,
                'followup_overdue' => (bool) ($this->resource->followup_overdue ?? false),

                'created_at' => !empty($this->resource->created_at ?? null)
                    ? Carbon::parse($this->resource->created_at)->format('Y-m-d H:i:s')
                    : null,

                'updated_at' => !empty($this->resource->updated_at ?? null)
                    ? Carbon::parse($this->resource->updated_at)->format('Y-m-d H:i:s')
                    : null,
            ];
        }

        /**
         * ==========================================================
         * CUSTOMER CARD
         * ==========================================================
         */
        return [

            'display_type' => 'customer',

            'id' => $this->resource->id ?? null,
            'customer_code' => $this->resource->customer_code ?? null,
            'company_name' => $this->resource->company_name ?? null,

            // tetap dipertahankan untuk backward compat (sinkron dari kontak primary)
            'contact_name' => $this->resource->contact_name ?? null,
            'email' => $this->resource->email ?? null,
            'phone' => $this->resource->phone ?? null,

            // ── kontak lengkap (baru) ──
            'contacts' => $contacts,

            'address' => $this->resource->address ?? null,

            'customer_status' => $this->resource->customer_status ?? null,

            'approval_status' => $this->resource->approval_status ?? null,
            'approved_by' => $this->resource->approved_by ?? null,

            'approved_at' => !empty($this->resource->approved_at ?? null)
                ? Carbon::parse($this->resource->approved_at)->format('Y-m-d H:i:s')
                : null,

            'approval_note' => $this->resource->approval_note ?? null,
            'approval_revision' => $this->resource->approval_revision ?? null,

            'notes' => $this->resource->notes ?? null,

            'lead_id' => $this->resource->lead_id ?? null,
            'lead_company_name' => $this->resource->lead_company_name ?? null,
            'lead_source' => $this->resource->lead_source ?? null,
            'lead_status' => $this->resource->lead_status ?? null,

            'lead_category_id' => $this->resource->lead_category_id ?? null,
            'lead_category_name' => $this->resource->category_name ?? null,

            'industry_id' => $this->resource->industry_id ?? null,
            'industry_name' => $this->resource->industry_name ?? null,

            'assigned_to' => $this->resource->assigned_to ?? null,
            'assigned_name' => $this->resource->assigned_name ?? null,

            'created_by' => $this->resource->created_by ?? null,
            'owner_name' => $this->resource->owner_name ?? null,

            'branch_count' => (int) ($this->resource->branch_count ?? 0),

            'branches' => $branches,

            // ── TRIGGER FOLLOW UP (level card, dipakai frontend buat border merah/badge) ──
            // Sudah digabung di controller (customer-level + SEMUA branch, karena
            // owner customer melihat semua branch). Deteksi berbasis tanggal saja.
            'followup_due' => (bool) ($this->resource->followup_due ?? false),
            'followup_due_date' => $this->resource->followup_due_date ?? null,
            'followup_overdue' => (bool) ($this->resource->followup_overdue ?? false),

            'converted_at' => !empty($this->resource->converted_at ?? null)
                ? Carbon::parse($this->resource->converted_at)->format('Y-m-d H:i:s')
                : null,

            'created_at' => !empty($this->resource->created_at ?? null)
                ? Carbon::parse($this->resource->created_at)->format('Y-m-d H:i:s')
                : null,

            'updated_at' => !empty($this->resource->updated_at ?? null)
                ? Carbon::parse($this->resource->updated_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}