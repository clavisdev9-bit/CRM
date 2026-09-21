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
            'email'        => ['nullable', 'email', 'max:100'],
            'phone'        => ['nullable', 'string', 'max:20'],
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
            'email.email'              => 'Format email tidak valid.',
            'phone.max'                => 'Nomor telepon maksimal 20 karakter.',
            'status.in'                => 'Status harus Active atau Inactive.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'company_name' => $this->has('company_name') ? trim($this->input('company_name')) : null,
            'contact_name' => $this->filled('contact_name') ? trim($this->input('contact_name')) : null,
            'email'        => $this->filled('email') ? trim($this->input('email')) : null,
            'phone'        => $this->filled('phone') ? trim($this->input('phone')) : null,
        ]);
    }
}