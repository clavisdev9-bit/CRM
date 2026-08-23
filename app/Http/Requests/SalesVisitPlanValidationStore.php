<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalesVisitPlanValidationStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // customer_id NULLABLE -- boleh bikin rencana ke target yang belum
            // ada di database customer (lewat `title` manual di bawah).
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],

            // title WAJIB kalau customer_id kosong (target manual/freeform).
            // Kalau customer_id keisi, title-nya nanti DITIMPA controller
            // pakai company_name customer itu -- jadi walau dikirim kosong
            // dari frontend, tetap lolos validasi di sini.
            'title' => ['nullable', 'required_without:customer_id', 'string', 'max:255'],

            'plan_date' => ['required', 'date_format:Y-m-d'],
            'notes'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required_without' => 'Judul rencana kunjungan wajib diisi kalau tidak memilih customer.',
            'plan_date.required'     => 'Tanggal rencana kunjungan wajib diisi.',
            'plan_date.date_format'  => 'Format tanggal rencana kunjungan tidak valid.',
            'customer_id.exists'     => 'Customer tidak ditemukan.',
        ];
    }
}