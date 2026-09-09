<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi update mapping 1 company (group_companies.odoo_company_id).
 * Sengaja nullable -- input dikosongkan artinya admin melepas mapping
 * company itu (fallback ke odoo_settings.default_company_id saat push
 * ke Odoo), bukan error.
 */
class OdooCompanyMappingValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'odoo_company_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'odoo_company_id.integer' => 'Odoo Company ID harus berupa angka.',
            'odoo_company_id.min'     => 'Odoo Company ID minimal 1.',
        ];
    }
}