<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CatalogResources extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'product_id'  => $this->product_id,
            'media_type'  => $this->media_type,
            'source_type' => $this->source_type,
            'url'         => $this->url,
            // ── full_url: kalau upload, jadikan URL publik lewat Storage.
            // Kalau youtube/vimeo/external_link, url yang disimpan sudah
            // berupa link langsung, jadi dipakai apa adanya. ──
            'full_url'    => $this->source_type === 'upload'
                ? Storage::disk('public')->url($this->url)
                : $this->url,
            'title'       => $this->title,
            'sort_order'  => $this->sort_order,
            'created_at'  => $this->created_at?->toDateTimeString(),
            'updated_at'  => $this->updated_at?->toDateTimeString(),
        ];
    }
}