<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ── Validasi untuk Add/Edit contact STANDALONE saja (Principle,
 * Competitor, dst -- diisi manual lewat form). Untuk "Link dari Data
 * Existing" pakai ContactLinkValidationRequest, bukan file ini. ──
 */
class ContactValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_type_id' => [
                'required',
                'integer',
                // ── Jenis yang boleh dipilih manual HANYA yang
                // is_system = false. Jenis reserved (Customer/Lead/dst)
                // hanya boleh terpasang otomatis lewat proses link. ──
                Rule::exists('contact_types', 'id')->where(fn ($q) => $q->where('is_system', false)),
            ],
            'company_name' => ['required', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            // ── email & phone sekarang array (multi-value, disimpan sebagai
            // jsonb -- lihat Contact::$casts). '.*' memvalidasi tiap elemen
            // satu-satu. prepareForValidation() di bawah sudah menormalisasi
            // input jadi array bersih (trim, buang kosong/duplikat) sebelum
            // sampai di sini. ──
            'email'        => ['nullable', 'array'],
            'email.*'      => ['nullable', 'email', 'max:100'],
            'phone'        => ['nullable', 'array'],
            'phone.*'      => ['nullable', 'string', 'max:20'],
            'address'      => ['nullable', 'string'],
            'notes'        => ['nullable', 'string'],
            'status'       => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_type_id.required' => 'Jenis contact wajib dipilih.',
            'contact_type_id.integer'  => 'Jenis contact tidak valid.',
            'contact_type_id.exists'   => 'Jenis contact tidak ditemukan atau tidak bisa dipakai untuk contact standalone.',
            'company_name.required'    => 'Nama perusahaan/pihak wajib diisi.',
            'company_name.max'         => 'Nama perusahaan/pihak maksimal 150 karakter.',
            'contact_name.max'         => 'Nama PIC maksimal 100 karakter.',
            'email.array'              => 'Format email tidak valid.',
            'email.*.email'           => 'Salah satu email tidak valid.',
            'email.*.max'             => 'Email maksimal 100 karakter.',
            'phone.array'              => 'Format nomor telepon tidak valid.',
            'phone.*.max'             => 'Nomor telepon maksimal 20 karakter.',
            'status.in'                => 'Status harus Active atau Inactive.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'company_name' => $this->has('company_name') ? trim($this->input('company_name')) : null,
            'contact_name' => $this->filled('contact_name') ? trim($this->input('contact_name')) : null,
            'email'        => $this->normalizeMultiValue($this->input('email')),
            'phone'        => $this->normalizeMultiValue($this->input('phone')),
        ]);
    }

    /**
     * ── Normalisasi input email/phone jadi array bersih:
     * - Terima array (kasus normal, dari form multi-value) ATAU string
     *   tunggal (jaga-jaga kalau ada pemanggil lama yang masih kirim
     *   scalar) -- keduanya diseragamkan jadi array.
     * - Trim tiap elemen, buang yang kosong & duplikat, lalu re-index.
     * - Kalau hasilnya kosong, kembalikan null (bukan array kosong)
     *   supaya kolom jsonb-nya NULL, konsisten dengan behavior lama
     *   waktu field ini dikosongkan.
     * ──
     */
    private function normalizeMultiValue($value): ?array
    {
        if ($value === null) {
            return null;
        }

        $items = is_array($value) ? $value : [$value];

        $items = collect($items)
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->unique()
            ->values()
            ->all();

        return empty($items) ? null : $items;
    }
}