<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'odoo_product_id'  => $this->odoo_product_id,
            'product_name'     => $this->whenLoaded('odooProduct', fn () => $this->odooProduct?->name),
            'description'      => $this->description,
            'quantity'         => (float) $this->quantity,
            'unit'             => $this->unit,
            'unit_price'       => (float) $this->unit_price,
            'total'            => (float) $this->total,
            'sort_order'       => $this->sort_order,
        ];
    }
}