<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductCatalogResources extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'category_id'       => $this->category_id,
            'category_name'     => $this->whenLoaded('category', fn () => $this->category?->name),
            'sku'               => $this->sku,
            'name'              => $this->name,
            'description'       => $this->description,
            'price'             => $this->price !== null ? (float) $this->price : null,
            'stock'             => (int) $this->stock,
            'thumbnail_url'     => $this->thumbnail_url,
            'thumbnail_full_url' => $this->thumbnail_url
                ? Storage::disk('public')->url('product-catalogs/thumbnails/' . $this->thumbnail_url)
                : null,
            'catalogs_count'    => $this->when(isset($this->catalogs_count), fn () => $this->catalogs_count),
            'catalogs'          => CatalogResources::collection($this->whenLoaded('catalogs')),
            'created_at'        => $this->created_at?->toDateTimeString(),
            'updated_at'        => $this->updated_at?->toDateTimeString(),
        ];
    }
}