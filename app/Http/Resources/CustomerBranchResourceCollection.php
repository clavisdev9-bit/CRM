<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerBranchResourceCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        // Jika ini hasil paginate(), tampilkan dengan struktur pagination
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

        // Jika ini hasil get() biasa (dipakai endpoint /customers/{id}/branches)
        return [
            'data' => CustomerBranchResource::collection($this->collection),
        ];
    }
}