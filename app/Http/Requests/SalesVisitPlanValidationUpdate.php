<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalesVisitPlanValidationUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // semua field pakai "sometimes" -- update ini partial, cuma field
            // yang benar-benar dikirim yang divalidasi/dipakai controller.
            // customer_id boleh dikirim `null` eksplisit buat lepas link ke
            // customer (balik jadi target manual, title lama tetap dipakai
            // kecuali title baru ikut dikirim juga).
            'customer_id' => ['sometimes', 'nullable', 'integer', 'exists:customers,id'],
            'title'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'plan_date'   => ['sometimes', 'date_format:Y-m-d'],
            'status'      => ['sometimes', 'string', 'in:planned,done,cancelled'],
            'notes'       => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_date.date_format' => 'Format tanggal rencana kunjungan tidak valid.',
            'status.in'             => 'Status harus salah satu dari: planned, done, cancelled.',
            'customer_id.exists'    => 'Customer tidak ditemukan.',
        ];
    }
}