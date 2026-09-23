<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CatalogSendLogResourcesCollection extends ResourceCollection
{
    public $collects = CatalogSendLogResources::class;

    public function toArray($request)
    {
        return $this->collection;
    }
}