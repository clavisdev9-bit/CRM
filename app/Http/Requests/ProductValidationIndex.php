<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi query params untuk endpoint list Product (GET /products).
 * Gaya sama seperti CostumersValidationIndex/SalesActivityValidationIndex:
 * search/per_page/sort_by/sort_dir.
 */
class ProductValidationIndex extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'search'   => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:5|max:100',
            'sort_by'  => 'nullable|in:name,default_code,list_price,standard_price,qty_available,created_at',
            'sort_dir' => 'nullable|in:asc,desc,Asc,Desc',
        ];
    }
}