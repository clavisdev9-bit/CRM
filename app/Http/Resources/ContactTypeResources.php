<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactTypeResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'is_active'   => (bool) $this->is_active,
            'is_system'   => (bool) $this->is_system,
            // ── Jumlah contact yang pakai jenis ini, kalau di-load lewat
            // withCount('contacts') di controller -- dipakai frontend
            // buat nge-warn user sebelum hapus/nonaktifkan jenis yang
            // masih dipakai. Null kalau tidak di-load. ──
            'contacts_count' => $this->when(isset($this->contacts_count), fn () => $this->contacts_count),
            'created_at'  => $this->created_at?->toDateString() ?? '-',
            'updated_at'  => $this->updated_at?->toDateString() ?? '-',
        ];
    }
}