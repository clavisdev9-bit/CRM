<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk 1 baris rekap "Product by Sales" untuk endpoint
 * /report-product-by-sales.
 *
 * BEDA sama Resource lain di project ini: $this->resource DI SINI adalah
 * array PHP biasa (hasil agregasi manual di ReportProductBySalesController),
 * BUKAN Eloquent model -- makanya diakses lewat $this['key'] (ArrayAccess),
 * bukan $this->key. Semua angka (qty/omzet/transaction_count) sudah final
 * hasil SUM/COUNT, ga ada perhitungan tambahan di sini.
 */
class ReportProductBySalesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sales_id'          => $this['sales_id'],
            'sales_name'        => $this['sales_name'],

            'odoo_product_id'   => $this['odoo_product_id'],
            'product_name'      => $this['product_name'],
            'product_code'      => $this['product_code'],

            'categ_id'          => $this['categ_id'],
            'categ_name'        => $this['categ_name'],

            'transaction_count' => (int) $this['transaction_count'],
            'total_qty'         => (float) $this['total_qty'],
            'total_omzet'       => (float) $this['total_omzet'],
        ];
    }
}