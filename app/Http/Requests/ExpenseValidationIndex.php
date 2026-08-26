<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseValidationIndex extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'     => ['nullable', 'string', 'in:' . implode(',', Expense::STATUSES)],
            // Filter kategori di halaman list -- sengaja TIDAK dibatasi
            // cuma yang is_active=true (beda dengan validasi di form
            // Ajukan Expense), supaya expense lama yang kategorinya
            // sudah dinonaktifkan tetap bisa difilter/ditemukan.
            'category'   => ['nullable', 'string', 'exists:expense_categories,name'],
            'sales_id'   => ['nullable', 'integer', 'exists:ms_users,id_user'],
            'period_year'  => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'search'     => ['nullable', 'string', 'max:100'],
            'per_page'   => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}