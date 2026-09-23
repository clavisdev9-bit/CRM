<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryProductCatalogResourcesCollection extends ResourceCollection
{
    public $collects = CategoryProductCatalogResources::class;

    public function toArray($request)
    {
        return $this->collection;
    }
}