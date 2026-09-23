<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryProductCatalogValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id') ?? $this->route('category');

        return [
            'name'      => ['required', 'string', 'max:150'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories_product_catalog', 'id'),
                $categoryId ? Rule::notIn([$categoryId]) : 'nullable',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Nama kategori wajib diisi.',
            'name.max'           => 'Nama kategori maksimal 150 karakter.',
            'parent_id.exists'   => 'Kategori induk tidak ditemukan.',
            'parent_id.not_in'   => 'Kategori tidak boleh menjadi induk dari dirinya sendiri.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'name' => $this->has('name') ? trim($this->input('name')) : null,
        ]);
    }
}