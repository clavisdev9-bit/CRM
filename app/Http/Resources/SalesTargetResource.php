<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk 1 baris Sales Target buat endpoint /sales-targets.
 *
 * target_amount disimpan di DB (kolom sales_targets.target_amount).
 * achieved_amount & achievement_percent BUKAN kolom DB -- keduanya dihitung
 * dinamis di SalesTargetController (dari odoo_customer_purchase_items lewat
 * assignment CustomerSalesAssignmentOdoo) lalu "ditempel" ke instance model
 * sebelum masuk Resource ini (contoh: $target->achieved_amount = ...).
 * Kalau achieved_amount belum ditempel (misal Resource ini dipakai di tempat
 * lain yang belum sempat ngitung), default-nya 0 -- BUKAN null/error.
 */
class SalesTargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $targetAmount       = (float) $this->target_amount;
        $achievedAmount     = (float) ($this->achieved_amount ?? 0);
        $achievementPercent = $targetAmount > 0
            ? round(($achievedAmount / $targetAmount) * 100, 2)
            : 0;

        return [
            'id'               => $this->id,

            'sales_id'         => $this->sales_id,
            'sales_name'       => $this->whenLoaded('salesUser', fn () => $this->salesUser?->fullname, null),

            'period_year'      => $this->period_year,

            'odoo_customer_id' => $this->odoo_customer_id,
            'customer_name'    => $this->whenLoaded('odooCustomer', fn () => $this->odooCustomer?->name, null),
            'is_total_target'  => is_null($this->odoo_customer_id),

            'target_amount'       => $targetAmount,
            'achieved_amount'     => $achievedAmount,
            'achievement_percent' => $achievementPercent,

            'notes'           => $this->notes,

            'created_by'      => $this->created_by,
            'created_by_name' => $this->whenLoaded('creator', fn () => $this->creator?->fullname, null),

            'created_at' => !empty($this->created_at ?? null)
                ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s')
                : null,
            'updated_at' => !empty($this->updated_at ?? null)
                ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}