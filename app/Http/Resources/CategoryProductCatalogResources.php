<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryProductCatalogResources extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'parent_id'      => $this->parent_id,
            'parent_name'    => $this->whenLoaded('parent', fn () => $this->parent?->name),
            'products_count' => $this->when(isset($this->products_count), fn () => $this->products_count),
            'children_count' => $this->when(isset($this->children_count), fn () => $this->children_count),
            'created_at'     => $this->created_at?->toDateTimeString(),
            'updated_at'     => $this->updated_at?->toDateTimeString(),
        ];
    }
}