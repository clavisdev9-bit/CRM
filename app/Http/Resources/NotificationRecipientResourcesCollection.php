<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationRecipientResourcesCollection extends ResourceCollection
{
    public $collects = NotificationRecipientResources::class;

    public function toArray($request)
    {
        return $this->collection;
    }
}
