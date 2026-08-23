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
 *
 * target_type: 'total' | 'customer' | 'brand' | 'category' -- dihitung dari
 * kolom dimensi mana yang keisi (odoo_customer_id / odoo_product_id /
 * categ_id, cuma boleh salah satu -- dijaga di SalesTargetValidationStore +
 * CHECK constraint chk_sales_targets_single_dimension). Dipakai frontend
 * buat nentuin badge/label & field mana yang ditampilin. is_total_target
 * TETAP dipertahankan (dipakai kode lama) -- sekarang artinya "bukan salah
 * satu dari customer/brand/category".
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

        $targetType = 'total';
        if (!is_null($this->odoo_customer_id)) {
            $targetType = 'customer';
        } elseif (!is_null($this->odoo_product_id)) {
            $targetType = 'brand';
        } elseif (!is_null($this->categ_id)) {
            $targetType = 'category';
        }

        return [
            'id'               => $this->id,

            'sales_id'         => $this->sales_id,
            'sales_name'       => $this->whenLoaded('salesUser', fn () => $this->salesUser?->fullname, null),

            'period_year'      => $this->period_year,

            'target_type'      => $targetType,
            'is_total_target'  => $targetType === 'total',

            'odoo_customer_id' => $this->odoo_customer_id,
            'customer_name'    => $this->whenLoaded('odooCustomer', fn () => $this->odooCustomer?->name, null),

            'odoo_product_id'  => $this->odoo_product_id,
            'product_name'     => $this->whenLoaded('odooProduct', fn () => $this->odooProduct?->name, null),

            'categ_id'         => $this->categ_id,
            'categ_name'       => $this->categ_name,

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