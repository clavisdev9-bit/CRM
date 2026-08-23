<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi query params untuk GET /report-product-by-sales.
 * Gaya sama seperti SalesTargetValidationIndex/ProductValidationIndex.
 */
class ReportProductBySalesValidationIndex extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'period_year' => 'nullable|integer|min:2000|max:2100',
            'sales_id'    => 'nullable|integer|exists:ms_users,id_user',
            'categ_id'    => 'nullable|integer',
            'search'      => 'nullable|string|max:100',
            'per_page'    => 'nullable|integer|min:5|max:100',
        ];
    }
}