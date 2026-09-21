<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

   public function rules(): array
    {
        return [
            'role'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            // ── Urutan tier hirarki (opsional) -- semakin kecil angkanya,
            // semakin tinggi posisinya di struktur hirarki. Boleh
            // dikosongkan (nullable): kalau tidak diisi, role tersebut
            // otomatis ditaruh paling bawah oleh
            // Administrator::userHierarchy(). ──
            'hierarchy_order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
{
    return [
        'role.required'        => 'role is required.',
        'role.string'          => 'role must be a string.',
        'role.max'             => 'role may not be greater than 255 characters.',
        'description.required' => 'description is required.',
        'description.string'   => 'description must be a string.',
        'hierarchy_order.integer' => 'hierarchy_order must be a number.',
    ];
}


    protected function prepareForValidation()
    {
        $this->merge([
            'role'        => $this->has('role') ? trim($this->input('role')) : null,
            'description' => $this->has('description') ? trim($this->input('description')) : null,
            // ── Normalisasi hierarchy_order: string kosong ('') dari form
            // frontend dianggap "tidak diisi" -> null (bukan gagal validasi
            // 'integer'), sedangkan nilai yang benar-benar terisi dipaksa
            // jadi integer murni. ──
            'hierarchy_order' => $this->filled('hierarchy_order') ? (int) $this->input('hierarchy_order') : null,
        ]);
    }
}