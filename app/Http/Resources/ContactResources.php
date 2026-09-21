<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Contact;

/**
 * ── Resource ini dipakai untuk 2 bentuk data yang beda:
 *
 * 1. Eloquent Contact model (dari showContact/storeContact/updateContact
 *    di ContactController) -- untuk baris LINKED, company_name/
 *    contact_name/dll di model masih NULL, jadi di sini di-resolve dulu
 *    lewat Contact::resolveSource() supaya response tetap lengkap.
 *
 * 2. Baris hasil JOIN mentah (stdClass, dari ContactController::index())
 *    -- sengaja TIDAK lewat Eloquent supaya listing bisa 1 query + di
 *    paginate dengan benar tanpa N+1 (lihat catatan panjang di
 *    Contact::resolveSource() dan ContactController::index()). Baris ini
 *    SUDAH punya company_name/contact_name/dll yang benar (hasil
 *    COALESCE di query), jadi di sini tinggal dibaca apa adanya --
 *    resolveSource() TIDAK dipanggil lagi (tidak ada method itu di
 *    stdClass).
 * ──
 */
class ContactResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isEloquent = $this->resource instanceof Contact;

        $resolved = null;
        if ($isEloquent && $this->source_type) {
            $resolved = $this->resolveSource();
        }

        return [
            'id'              => $this->id,
            'contact_type_id' => $this->contact_type_id,

            'contact_type_name' => $isEloquent
                ? $this->whenLoaded('contactType', fn () => $this->contactType?->name)
                : ($this->contact_type_name ?? null),

            'contact_type_is_system' => $isEloquent
                ? $this->whenLoaded('contactType', fn () => (bool) $this->contactType?->is_system)
                : (isset($this->contact_type_is_system) ? (bool) $this->contact_type_is_system : null),

            'contact_code' => $this->contact_code,

            'is_linked'   => (bool) $this->source_type,
            'source_type' => $this->source_type,
            'source_id'   => $this->source_id,

            // ── Field identitas: kalau linked & Eloquent, override pakai
            // data live hasil resolveSource(); kalau raw join row, field
            // ini sudah benar apa adanya dari query. ──
            'company_name' => $resolved['display_name'] ?? $this->company_name,
            'contact_name' => $resolved['contact_name'] ?? $this->contact_name,
            'email'        => $resolved['email'] ?? $this->email,
            'phone'        => $resolved['phone'] ?? $this->phone,
            'address'      => $resolved['address'] ?? $this->address,

            // source_code & parent_label cuma relevan untuk linked contact
            'source_code'  => $resolved['source_code'] ?? ($this->source_code ?? null),
            'parent_label' => $resolved['parent_label'] ?? ($this->parent_label ?? null),

            'notes'  => $this->notes,
            'status' => $this->status,

            'created_by' => $this->created_by,
            'created_at' => $isEloquent
                ? ($this->created_at?->toDateString() ?? '-')
                : ($this->created_at ?? '-'),
            'updated_at' => $isEloquent
                ? ($this->updated_at?->toDateString() ?? '-')
                : ($this->updated_at ?? '-'),
        ];
    }
}