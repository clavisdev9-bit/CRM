<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form koneksi Odoo GLOBAL (menu Odoo Settings). Dipakai untuk
 * dua endpoint: updateConnection() (simpan) dan testConnection() (cuma
 * test, belum tentu disimpan) -- rules-nya sama karena keduanya butuh
 * 4 field koneksi yang lengkap & valid.
 */
class OdooConnectionValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url'                => ['required', 'string', 'max:255'],
            'db'                 => ['required', 'string', 'max:255'],
            'username'           => ['required', 'string', 'max:255'],
            'api_key'            => ['required', 'string', 'max:255'],
            'default_company_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required'      => 'Base URL wajib diisi.',
            'db.required'       => 'Database wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'api_key.required'  => 'API Key wajib diisi.',
            'default_company_id.integer' => 'Default Company ID harus berupa angka.',
            'default_company_id.min'     => 'Default Company ID minimal 1.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'url'      => $this->has('url')      ? trim((string) $this->input('url'))      : null,
            'db'       => $this->has('db')       ? trim((string) $this->input('db'))       : null,
            'username' => $this->has('username') ? trim((string) $this->input('username')) : null,
            'api_key'  => $this->has('api_key')  ? trim((string) $this->input('api_key'))  : null,
        ]);
    }
}