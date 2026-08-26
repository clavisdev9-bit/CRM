<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Bungkus paginator hasil ExpenseController@index -- pola sama persis
 * kayak SalesTargetResourceCollection/ReportProductBySalesResourceCollection.
 */
class ExpenseResourceCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => ExpenseResource::collection($this->collection),
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