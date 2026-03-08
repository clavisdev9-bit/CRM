<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VisitValidationForExternalIndex extends FormRequest
{
    /**
     * Authorize request
     */
    public function authorize(): bool
    {
        // karena ini hanya read data → biasanya true
        // kalau mau pakai permission bisa dicek di sini
        return true;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
        'per_page' => 'required|integer|min:1|max:10000',
        'search'   => 'nullable|string|max:100',
        'sort_by'  => 'nullable|in:company_name,created_at,visit_date,check_out',
        'sort_dir' => 'nullable|in:asc,desc',

        // ✅ Fix: PLANNED bukan VISIT
        'visit_status' => 'nullable|in:PLANNED,ONGOING,DONE',

        // ✅ Tambah visit_type
        'visit_type' => 'nullable|in:LEAD,CUSTOMER',
        // ✅ Tambah filter tanggal
        'date_from' => 'nullable|date',
        'date_to'   => 'nullable|date|after_or_equal:date_from',
    ];
    }

    /**
     * Default values (optional tapi recommended)
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->per_page ?? 10,
            'sort_by'  => $this->sort_by ?? 'created_at',
            'sort_dir' => $this->sort_dir ?? 'desc',
        ]);
    }

    /**
     * Custom error messages (biar clean ke frontend)
     */
   public function messages(): array
{
    return [
        'per_page.integer'  => 'Per page harus berupa angka.',
        'per_page.max'      => 'Maksimal per page adalah 10000 data.',
        'sort_by.in'        => 'Kolom sorting tidak valid.',
        'sort_dir.in'       => 'Arah sorting hanya boleh asc atau desc.',
        'visit_status.in'   => 'Status visit hanya PLANNED, ONGOING, atau DONE.',
        'visit_type.in'     => 'Tipe visit hanya LEAD atau CUSTOMER.',
    ];
}
}
