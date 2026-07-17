<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerBranchApprovalResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * Mendukung 2 mode:
     * - Hasil ->paginate()  → tersedia key 'pagination'
     * - Hasil ->get() biasa → hanya key 'data'
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (method_exists($this->resource, 'total')) {
            return [
                'data' => CustomerBranchResource::collection($this->collection),
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

        return [
            'data' => CustomerBranchResource::collection($this->collection),
        ];
    }
}