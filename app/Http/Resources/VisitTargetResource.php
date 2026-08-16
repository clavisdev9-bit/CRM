<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitTargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $target   = (int) $this->target_count;
        $achieved = (int) $this->achieved_count;

        return [
            'id'              => $this->id,
            'target_code'     => $this->target_code,
            'sales_id'        => $this->sales_id,
            'sales_name'      => $this->sales_name,
            'target_type'     => $this->target_type,   // 'customer' | 'branch'
            'target_name'     => $this->target_name,
            'target_note'     => $this->target_note,   // 'Customer' | 'Branch'
            'target_count'    => $target,
            'achieved_count'  => $achieved,
            'remaining'       => max($target - $achieved, 0),
            'percentage'      => $target > 0 ? min((int) round(($achieved / $target) * 100), 100) : 0,
            'is_achieved'     => $achieved >= $target,
            'period_month'    => $this->period_month,
            'notes'           => $this->notes,
            'created_by'      => $this->created_by,
            'created_by_name' => $this->created_by_name,
            'created_at'      => $this->created_at,
        ];
    }
}