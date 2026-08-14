<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomersProductPopulationValidationIndex extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // all | mine | incomplete — 3 mode tampilan di frontend
            'view'     => 'nullable|string|in:all,mine,incomplete',

            'search'   => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by'  => 'nullable|string',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ];
    }
}