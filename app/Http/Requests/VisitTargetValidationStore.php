<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VisitTargetValidationStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_id'     => ['required', 'integer', 'exists:ms_users,id_user'],
            'customer_id'  => ['nullable', 'required_without:branch_id', 'integer', 'exists:customers,id'],
            'branch_id'    => ['nullable', 'required_without:customer_id', 'integer', 'exists:customer_branches,id'],
            'target_count' => ['required', 'integer', 'min:1', 'max:1000'],
            // frontend selalu kirim tanggal 1 di bulan yang dipilih, misal "2026-08-01"
            'period_month' => ['required', 'date_format:Y-m-d'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('customer_id') && $this->filled('branch_id')) {
                $validator->errors()->add(
                    'customer_id',
                    'Pilih salah satu target: Customer ATAU Branch, tidak boleh dua-duanya sekaligus.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'sales_id.required'            => 'Sales wajib dipilih.',
            'sales_id.exists'              => 'Sales tidak ditemukan.',
            'customer_id.required_without' => 'Pilih Customer atau Branch sebagai target.',
            'branch_id.required_without'   => 'Pilih Customer atau Branch sebagai target.',
            'target_count.required'        => 'Jumlah target wajib diisi.',
            'target_count.min'             => 'Jumlah target minimal 1.',
            'period_month.required'        => 'Bulan target wajib dipilih.',
        ];
    }
}