<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_role' => $this->id_role,
            'role' => $this->role,
            'description' => $this->description,
            // ── Urutan tier hirarki (lihat MsRole::$fillable /
            // RoleValidationRequest) -- diikutkan di sini supaya form
            // Edit Role di frontend bisa baca & tampilkan nilainya. ──
            'hierarchy_order' => $this->hierarchy_order,
            'created_at' => $this->created_at?->toDateString() ?? '-',
            'updated_at' => $this->updated_at?->toDateString() ?? '-',
        ];
    }
}