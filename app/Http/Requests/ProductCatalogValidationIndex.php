<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductCatalogValidationIndex extends FormRequest
{
    protected array $allowedSortFields = ['name', 'sku', 'price', 'stock', 'created_at'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $allowedParams = ['search', 'sort_by', 'sort_dir', 'per_page', 'page', 'category_id'];

        $unknown = array_diff(array_keys($this->query()), $allowedParams);

        if (!empty($unknown)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Parameter query tidak dikenali: ' . implode(', ', $unknown),
                'errors'  => ['query' => ['Parameter tidak dikenali: ' . implode(', ', $unknown)]],
            ], 422));
        }
    }

    public function rules(): array
    {
        return [
            'search'      => ['nullable', 'string', 'max:100'],
            'sort_by'     => ['nullable', 'string', 'in:' . implode(',', $this->allowedSortFields)],
            'sort_dir'    => ['nullable', 'string', 'in:asc,desc'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:200'],
            'page'        => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'exists:categories_product_catalog,id'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Parameter query tidak valid.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}