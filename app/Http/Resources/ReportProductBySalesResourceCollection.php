<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Bungkus paginator hasil ReportProductBySalesController@index -- pola sama
 * persis kayak SalesTargetResourceCollection/ProductResourceCollection.
 *
 * CATATAN: paginator yang dibungkus di sini dibuat MANUAL lewat
 * `new LengthAwarePaginator(...)` di controller (bukan hasil ->paginate()
 * langsung dari query builder), soalnya agregasi per sales+product-nya
 * kelar di level PHP (regroup dari hasil GROUP BY per customer+product).
 * total()/perPage()/currentPage()/dst di bawah tetap jalan normal karena
 * LengthAwarePaginator implementasinya sama persis.
 */
class ReportProductBySalesResourceCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => ReportProductBySalesResource::collection($this->collection),
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