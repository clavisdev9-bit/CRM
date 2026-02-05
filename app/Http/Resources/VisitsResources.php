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
            'lead_id' => $this->lead_id,
            'customer_id' => $this->customer_id,
            'sales_id' => $this->sales_id,
            'visit_at' => $this->visit_at,
            'check_in_at' => $this->check_in_at,
            'check_out_at' => $this->check_out_at,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'gps_snapshot' => $this->gps_snapshot,
            'notes' => $this->notes,
            'visit_result' => $this->visit_result,
            'customer_response' => $this->customer_response,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toDateString() ?? '-',
            'updated_at' => $this->updated_at?->toDateString() ?? '-',
        ];
    }
}
