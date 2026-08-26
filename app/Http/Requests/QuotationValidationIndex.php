<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuotationValidationIndex extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_id'     => ['nullable', 'integer', 'exists:ms_users,id_user'],
            'customer_id'  => ['nullable', 'integer', 'exists:customers,id'],
            'period_year'  => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'search'       => ['nullable', 'string', 'max:100'],
            'per_page'     => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}