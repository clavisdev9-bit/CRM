<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OdooCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'odoo_partner_id' => $this->odoo_partner_id,
            'name'            => $this->name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'address'         => $this->address,
            'is_company'      => $this->is_company,
            'has_purchased'   => $this->has_purchased,
            'total_transaksi' => $this->total_transaksi,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
