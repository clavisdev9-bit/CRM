<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class VisitCustomersDataResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        // ── CONTACTS ──
        // Bisa berupa array (sudah di-decode di controller) atau
        // Collection biasa, jadi di-normalize dulu supaya konsisten.
        $contacts = collect($this->contacts ?? [])->map(function ($contact) {
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
            // GEOLOKASI (Phase 1/3) -- dipakai frontend buat nge-cek apakah
            // target ini (customer head office ATAU branch) udah punya
            // titik lokasi sendiri sebelum ngaktifin tombol "Visit Now"
            // (lihat hasCoordinates() di SalesVisitData.vue). Query-nya
            // sudah nyertain c.latitude/c.longitude/c.radius_meter (head
            // office) & b.latitude/b.longitude/b.radius_meter (branch) di
            // Visits::VisitCustomers() -- di sini tinggal di-passthrough.
            // =========================
            'latitude'     => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude'    => $this->longitude !== null ? (float) $this->longitude : null,
            'radius_meter' => $this->radius_meter !== null ? (int) $this->radius_meter : null,

            // =========================
            // TARGET TYPE (customer / branch)
            // =========================
            'target_type' => $this->target_type ?? 'customer',
            'branch_id'   => $this->branch_id ?? null,
            'branch_code' => $this->branch_code ?? null,
            'branch_name' => $this->branch_name ?? null,
            'city'        => $this->city ?? null,

            // ── semua kontak (customer atau branch, tergantung target_type) ──
            'contacts' => $contacts,

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
            // ID JOIN (visit aktif untuk target ini)
            // =========================
            'active_visit_id' => $this->active_visit_id,
            'visit_status' => $this->visit_status,
            'active_check_in_at' => $this->active_check_in_at,

            // =========================
            // AUDIT
            // =========================
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null,
        ];
    }
}