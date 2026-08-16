<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VisitTargetValidationIndex extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_month' => ['nullable', 'date_format:Y-m-d'],
            'sales_id'     => ['nullable', 'integer'],
            'search'       => ['nullable', 'string', 'max:100'],
            'status'       => ['nullable', 'in:all,achieved,not_achieved'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'sort_by'      => ['nullable', 'in:created_at,target_count,achieved_count,sales_name'],
            'sort_dir'     => ['nullable', 'in:asc,desc'],
        ];
    }
}
