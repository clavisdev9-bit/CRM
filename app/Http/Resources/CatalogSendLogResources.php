<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CatalogSendLogResources extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'catalog_id'       => $this->catalog_id,
            'product_id'       => $this->product_id,
            'sender_id'        => $this->sender_id,
            'sender_name'      => $this->sender_name,
            'sender_email'     => $this->sender_email,
            'recipient_name'   => $this->recipient_name,
            'recipient_email'  => $this->recipient_email,
            'recipient_phone'  => $this->recipient_phone,
            'product_name'     => $this->product_name,
            'catalog_title'    => $this->catalog_title,
            'channel'          => $this->channel,
            'status'           => $this->status,
            'error_message'    => $this->error_message,
            'created_at'       => $this->created_at?->toDateTimeString(),
            'updated_at'       => $this->updated_at?->toDateTimeString(),
        ];
    }
}