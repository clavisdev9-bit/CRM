<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Body request buat fitur "Duplikat ke Bulan Berikutnya" -- cuma butuh bulan
 * SUMBER (period_month yang lagi ditampilkan manager di halaman Target Visit).
 * Bulan TUJUAN dihitung otomatis di controller (source + 1 bulan) dan SENGAJA
 * tidak bisa dipilih bebas lewat request ini, biar sesuai nama fiturnya dan
 * nggak disalahgunakan buat "duplikat ke bulan sembarang".
 */
class VisitTargetValidationDuplicate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // frontend selalu kirim tanggal 1 di bulan yang sedang ditampilkan,
            // misal "2026-08-01" -- pola sama persis kayak period_month di
            // VisitTargetValidationStore.
            'period_month' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_month.required'    => 'Bulan sumber wajib diisi.',
            'period_month.date_format' => 'Format bulan sumber tidak valid.',
        ];
    }
}