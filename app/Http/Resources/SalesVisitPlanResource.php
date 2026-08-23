<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesVisitPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            // 'type' => 'plan' -- dipakai frontend buat bedain kartu ini
            // (bisa diedit/dihapus) dari item 'follow_up' (read-only,
            // dari tabel follow_ups, digabung di response index()).
            'type' => 'plan',

            'customer_id'    => $this->customer_id,
            'customer_code'  => $this->customer_code,
            'title'          => $this->title,
            'plan_date'      => $this->plan_date,
            'status'         => $this->status, // planned | done | cancelled
            'notes'          => $this->notes,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}