<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk 1 baris product (dari tabel lokal odoo_products, hasil sync) untuk
 * endpoint GET /products. Read-only -- source of truth-nya tetap Odoo.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'odoo_product_id' => $this->odoo_product_id,
            'name'            => $this->name,
            'default_code'    => $this->default_code,
            'barcode'         => $this->barcode,
            'category'        => $this->categ_name,
            'uom'             => $this->uom_name,
            'list_price'      => (float) $this->list_price,
            'standard_price'  => (float) $this->standard_price,
            'qty_available'   => (float) $this->qty_available,
            'active'          => (bool) $this->active,

            'updated_at' => !empty($this->updated_at ?? null)
                ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}