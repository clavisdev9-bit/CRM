<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Bungkus paginator hasil activities() jadi bentuk pagination custom yang
 * kamu biasa pakai — persis pola CostumersResourcesCollection:
 * {"data": [...], "pagination": {total, per_page, current_page, last_page,
 * next_page_url, prev_page_url}}.
 *
 * Dipakai lewat: ApiResponse::paginate(SalesActivityResourceCollection::make($results), ...)
 */
class SalesActivityResourceCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => SalesActivityResource::collection($this->collection),
            'pagination' => [
                'total'          => $this->total(),
                'per_page'       => $this->perPage(),
                'current_page'   => $this->currentPage(),
                'last_page'      => $this->lastPage(),
                'next_page_url'  => $this->nextPageUrl(),
                'prev_page_url'  => $this->previousPageUrl(),
            ],
        ];
    }
}