<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'sales_id'    => $this->sales_id,
            'sales_name'  => $this->whenLoaded('sales', fn () => $this->sales?->fullname),

            'customer_id'             => $this->customer_id,
            'customer_company_name'   => $this->customer_company_name,
            'customer_address'        => $this->customer_address,
            'customer_pic_name'       => $this->customer_pic_name,

            'quotation_no'   => $this->quotation_no,
            'customer_ref'   => $this->customer_ref,
            'payment_terms'  => $this->payment_terms,
            'quotation_date' => optional($this->quotation_date)->toDateString(),
            'pages'          => $this->pages,
            'validity'       => $this->validity,
            'delivery_time'  => $this->delivery_time,
            'term'           => $this->term,

            'sub_total'  => (float) $this->sub_total,
            'ppn'        => (float) $this->ppn,
            'net_amount' => (float) $this->net_amount,

            'signature' => $this->signature,

            'items' => QuotationItemResource::collection($this->whenLoaded('items')),

            // ── Info push ke Odoo (sale.order), dipicu manual ──
            'odoo_sale_order_id' => $this->odoo_sale_order_id,
            'odoo_push_status'   => $this->odoo_push_status,
            'odoo_push_error'    => $this->odoo_push_error,
            'odoo_pushed_at'     => optional($this->odoo_pushed_at)->toDateTimeString(),

            'created_by' => $this->created_by,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}