<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerPopulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'   => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by'  => 'nullable|string|in:name,email,total_transaksi,created_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'filter'   => 'nullable|string|in:all,has_purchased',
        ];
    }
}