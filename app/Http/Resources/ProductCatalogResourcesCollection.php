<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCatalogResourcesCollection extends ResourceCollection
{
    public $collects = ProductCatalogResources::class;

    public function toArray($request)
    {
        return $this->collection;
    }
}
