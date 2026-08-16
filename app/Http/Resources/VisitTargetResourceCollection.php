<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Sama persis polanya dengan SalesActivityResourceCollection: bungkus paginator
 * jadi {"data": [...], "pagination": {...}}.
 */
class VisitTargetResourceCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => VisitTargetResource::collection($this->collection),
            'pagination' => [
                'total'         => $this->total(),
                'per_page'      => $this->perPage(),
                'current_page'  => $this->currentPage(),
                'last_page'     => $this->lastPage(),
                'next_page_url' => $this->nextPageUrl(),
                'prev_page_url' => $this->previousPageUrl(),
            ],
        ];
    }
}