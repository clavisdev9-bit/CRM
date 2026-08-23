<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalesVisitPlanValidationIndex extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // format "2026-08" (dari <input type="month"> di frontend),
            // nullable -- kalau kosong, controller pakai bulan berjalan.
            'month' => ['nullable', 'date_format:Y-m'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.date_format' => 'Format bulan tidak valid.',
        ];
    }
}