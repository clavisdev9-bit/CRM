<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource khusus konsumen EXTERNAL. Sengaja lebih ringkas
 * dibanding CustomersProductPopulationResource (versi internal) —
 * nggak ada pic_list/user_id (siapa sales yang pegang), karena itu
 * detail internal sales/assignment yang nggak perlu dibuka ke luar.
 */
class ProductPopulationExternalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasCustomer = ! empty($this->resource->customer_id ?? null);

        return [

            'id' => $this->resource->id ?? null,

            'customer' => $hasCustomer ? [
                'id'   => $this->resource->customer_id ?? null,
                'code' => $this->resource->customer_code ?? null,
                'name' => $this->resource->customer_name ?? null,
            ] : null,

            'pump_serial_no'             => $this->resource->pump_serial_no ?? null,
            'product_category'           => $this->resource->product_category ?? null,
            'product_display'            => $this->resource->product_display ?? null,
            'product_model'              => $this->resource->product_model ?? null,
            'tag_no'                     => $this->resource->tag_no ?? null,
            'qty'                        => (int) ($this->resource->qty ?? 0),
            'seal_plan'                  => $this->resource->seal_plan ?? null,
            'mechanical_seal_drawing_no' => $this->resource->mechanical_seal_drawing_no ?? null,

            'created_at' => !empty($this->resource->created_at ?? null)
                ? Carbon::parse($this->resource->created_at)->format('Y-m-d H:i:s')
                : null,

            'updated_at' => !empty($this->resource->updated_at ?? null)
                ? Carbon::parse($this->resource->updated_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}