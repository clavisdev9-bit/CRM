<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationResourcesCollection extends ResourceCollection
{
    public $collects = NotificationResources::class;

    public function toArray($request)
    {
        return $this->collection;
    }
}