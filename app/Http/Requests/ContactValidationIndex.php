<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Contact;

class ContactValidationIndex extends FormRequest
{
    protected array $allowedSortFields = ['company_name', 'status', 'created_at'];

    public function authorize(): bool
    {
        return true;
    }

    public function setAllowedSortFields(array $fields): void
    {
        $this->allowedSortFields = $fields;
    }

    public function rules(): array
    {
        return [
            'search'          => 'nullable|string',
            'per_page'        => 'nullable|integer|min:1|max:100',
            'sort_by'         => ['nullable', 'in:' . implode(',', $this->allowedSortFields)],
            'sort_dir'        => 'nullable|in:asc,desc',
            'page'            => 'nullable|integer|min:1',
            'only_deleted'    => 'nullable|boolean',
            // ── Filter tambahan khusus listing Contact ──
            'contact_type_id' => 'nullable|integer|exists:contact_types,id',
            'source_type'     => ['nullable', 'in:' . implode(',', Contact::SOURCE_TYPES)],
            'status'          => 'nullable|string|in:Active,Inactive',
        ];
    }

    protected function prepareForValidation(): void
    {
        $allowed = array_keys($this->rules());
        $unknown = array_diff(array_keys($this->all()), $allowed);

        if (count($unknown) > 0) {
            throw new HttpResponseException(response()->json([
                'message' => 'Field tidak dikenali',
                'errors' => collect($unknown)->mapWithKeys(fn ($f) => [$f => ['Field ini tidak valid']])
            ], 422));
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422));
    }

    public function messages(): array
    {
        return [
            'sort_by.in' => 'Kolom "sort_by" tidak valid. Hanya diperbolehkan: ' . implode(', ', $this->allowedSortFields),
            'sort_dir.in' => 'Arah pengurutan (sort_dir) harus "asc" atau "desc".',
            'per_page.integer' => 'Per page harus berupa angka.',
            'per_page.min' => 'Per page minimal 1.',
            'per_page.max' => 'Per page maksimal 100.',
            'only_deleted.boolean' => 'untuk only deleted Harus 1 atau 0',
            'contact_type_id.exists' => 'Jenis contact tidak ditemukan.',
            'source_type.in' => 'source_type tidak valid. Hanya diperbolehkan: ' . implode(', ', Contact::SOURCE_TYPES),
            'status.in' => 'Status harus Active atau Inactive.',
        ];
    }
}