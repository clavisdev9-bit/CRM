<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CabangResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_cabang' => $this->id_cabang,
            'cabang' => $this->cabang,
            'alamat' => $this->alamat,
            'no_telp' => $this->no_telp,
            // ── 1 Cabang = 1 Company ──
            'group_id' => $this->group_id,
            'group' => [
                'id_group'   => $this->group?->id_group,
                'name_group' => $this->group?->name_group,
            ],
            'created_at' => $this->created_at?->toDateString() ?? '-',
            'updated_at' => $this->updated_at?->toDateString() ?? '-',
        ];

    }
}