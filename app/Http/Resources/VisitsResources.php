<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitsResources extends JsonResource
{
    /**
     * PostgreSQL kadang mengembalikan kolom boolean sebagai string "t"/"f"
     * (bukan true/false asli) kalau datanya diambil lewat DB::table() biasa
     * (query builder, bukan Eloquent yang sudah di-cast). Kalau dibiarkan,
     * string "f" tetap "truthy" di JS/Vue, jadi badge "Lokasi Tidak Sesuai"
     * bisa salah muncul untuk visit yang sebenarnya lokasinya sudah sesuai.
     * Helper ini menormalkan ke boolean PHP asli sebelum dikirim ke frontend.
     */
    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === null) {
            return false;
        }
        return in_array(strtolower((string) $value), ['1', 't', 'true', 'yes'], true);
    }

    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'visit_code'   => $this->visit_code,
            'no_reference'   => $this->no_reference,

            // Target info
            'visit_type'      => $this->visit_type,
            'company_name'    => $this->company_name,
            'target_contact'  => $this->target_contact,
            'target_phone'    => $this->target_phone,
            'target_address'  => $this->target_address,
            'lead_id'         => $this->lead_id,
            'customer_id'     => $this->customer_id,

            // ── BRANCH INFO (baru) ──
            'branch_id'    => $this->branch_id,
            'target_type'  => $this->target_type ?? null, // 'BRANCH' | 'HEAD_OFFICE' | null (lead)
            'branch_code'  => $this->branch_code ?? null,
            'branch_name'  => $this->branch_name ?? null,
            'branch_city'  => $this->branch_city ?? null,

            // Sales info
            'sales_id'    => $this->sales_id,
            'sales_name'  => $this->sales_name ?? null,

            // Visit time
            'visit_at'     => $this->visit_at,
            'check_in_at'  => $this->check_in_at,
            'check_out_at' => $this->check_out_at,

            // Duration
            'time_from_visit_to_check_in'     => $this->time_from_visit_to_check_in,
            'time_from_check_in_to_check_out' => $this->time_from_check_in_to_check_out,
            'total_time_result'               => $this->total_time_result,

            // Status
            'visit_progress'     => $this->visit_progress,
            'visit_status'       => $this->visit_status,
            'visit_status_label' => $this->visit_status_label,

            // Location
            'latitude'     => $this->latitude,
            'longitude'    => $this->longitude,
            'gps_snapshot' => $this->gps_snapshot,

            // ── NOTE RADIUS CHECK-IN (baru) ──
            // Dipakai frontend (SalesVisitData.vue, tampilan Card & Table)
            // buat nampilin badge "Lokasi Tidak Sesuai" kalau sales
            // check-in di luar radius customer/cabang yang terdaftar.
            // Sebelumnya field ini SUDAH di-select di query getVisitLead()
            // (Visits.php), tapi belum diteruskan di sini -- makanya cuma
            // muncul di Detail Modal (yang ambil data mentah, bukan lewat
            // Resource ini) dan tidak muncul di Card/Table.
            'is_outside_radius' => $this->toBool($this->is_outside_radius ?? false),
            'distance_meter'    => $this->distance_meter ?? null,

            // Visit detail
            'photo'       => $this->photo,
            'photo_url'   => $this->photo_url,
            'notes'       => $this->notes,
            'visit_result' => $this->visit_result,
            'customer_response'     => $this->customer_response,
            'has_complaint'         => $this->has_complaint ?? null,
            'complaint_detail'      => $this->complaint_detail ?? null,
            'has_potential_order'   => $this->has_potential_order ?? null,
            'potential_order_detail' => $this->potential_order_detail ?? null,

            // Meta
            'created_by' => $this->created_by,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}