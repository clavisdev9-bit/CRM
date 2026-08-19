<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi query params untuk GET /sales-targets.
 * Gaya sama seperti ProductValidationIndex/SalesActivityValidationIndex.
 */
class SalesTargetValidationIndex extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'sales_id'    => 'nullable|integer|exists:ms_users,id_user',
            'period_year' => 'nullable|integer|min:2000|max:2100',
            'per_page'    => 'nullable|integer|min:5|max:100',
            'search'      => 'nullable|string|max:100',
        ];
    }
}