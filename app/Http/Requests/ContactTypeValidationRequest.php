<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactTypeValidationRequest extends FormRequest
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
            'name'        => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
            // ── 'is_system' SENGAJA tidak ada di sini -- jenis reserved
            // (Customer/Lead/dst) hanya boleh dipasang lewat proses link
            // di ContactController, bukan lewat form Add/Edit Contact
            // Type biasa. Kalaupun user mengirim 'is_system' di body
            // request, field itu diabaikan (tidak ikut divalidasi/
            // ter-mass-assign lewat request ini). ──
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'name is required.',
            'name.string'          => 'name must be a string.',
            'name.max'             => 'name may not be greater than 50 characters.',
            'description.string'   => 'description must be a string.',
            'is_active.boolean'    => 'is_active must be true or false.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name'        => $this->has('name') ? trim($this->input('name')) : null,
            'description' => $this->has('description') ? trim($this->input('description')) : null,
            'is_active'   => $this->has('is_active')
                ? filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN)
                : true,
        ]);
    }
}