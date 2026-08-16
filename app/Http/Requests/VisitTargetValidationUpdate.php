<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update HANYA boleh ubah target_count & notes -- sales/customer/branch/period_month
 * sengaja tidak bisa diubah setelah target dibuat (kalau salah target, hapus lalu
 * bikin baru), biar makna "achieved_count sejak created_at" tetap konsisten.
 */
class VisitTargetValidationUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_count.required' => 'Jumlah target wajib diisi.',
            'target_count.min'      => 'Jumlah target minimal 1.',
        ];
    }
}