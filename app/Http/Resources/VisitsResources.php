<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitsResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visit_code' => $this->visit_code,

            'company_name' => $this->company_name,
            'visit_type'   => $this->visit_type, // 👈 BARU

            'lead_id'      => $this->lead_id,
            'customer_id'  => $this->customer_id, // 👈 BARU
            'sales_id'     => $this->sales_id,
            'sales_name'   => $this->sales_name ?? null,

            'visit_at'     => $this->visit_at,
            'check_in_at'  => $this->check_in_at,
            'check_out_at' => $this->check_out_at,

            'time_from_visit_to_check_in'      => $this->time_from_visit_to_check_in,
            'time_from_check_in_to_check_out'  => $this->time_from_check_in_to_check_out,
            'total_time_result'                => $this->total_time_result,

            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'gps_snapshot'  => $this->gps_snapshot,
            'notes'         => $this->notes,
            'visit_result'  => $this->visit_result,
            'customer_response' => $this->customer_response,

            'created_by' => $this->created_by,
            'created_at' => optional($this->created_at)->toDateString(),
            'updated_at' => optional($this->updated_at)->toDateString(),
        ];
    }
}
