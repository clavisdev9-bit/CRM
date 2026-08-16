<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk 1 baris aktivitas (visit / followup / direct) untuk endpoint
 * GET /manager/sales-activity/activities.
 *
 * Polanya sama seperti CostumersResources punya kamu: cuma reshape kolom
 * hasil query builder jadi array response, tanpa logic tambahan. Kolom
 * internal `sort_time` (dipakai buat orderBy() di controller) sengaja
 * tidak diikutkan di sini karena bukan konsumsi frontend.
 */
class SalesActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'activity_type' => $this->activity_type,
            'id'            => $this->id,
            'sales_id'      => $this->sales_id,
            'sales_name'    => $this->sales_name,
            'target_name'   => $this->target_name,
            'target_note'   => $this->target_note,
            'activity_date' => $this->activity_date,
            'activity_time' => $this->activity_time,
            'note'          => $this->note,
        ];
    }
}