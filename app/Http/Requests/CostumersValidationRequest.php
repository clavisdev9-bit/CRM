<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CostumersValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name'      => 'required|string|max:255',
            'industry_id'       => 'required|integer',
            'lead_category_id'  => 'required|integer',
            'assigned_to'       => 'nullable|integer',
            'lead_source'       => 'required|string|max:255',
            'visibility_type'   => 'nullable|string|in:PRIVATE,PUBLIC',
            'notes'             => 'nullable|string',
            'address'           => 'required|string',
            'customer_status'   => 'required|string',

            // ── CONTACTS (bisa banyak) ──
            'contacts'                  => 'required|array|min:1',
            'contacts.*.id'             => 'nullable|integer|exists:customer_contacts,id',
            'contacts.*.name'           => 'required|string|max:100',
            'contacts.*.position'       => 'nullable|string|max:100',
            'contacts.*.email'          => 'nullable|email|max:100',
            // 'contacts.*.phone'          => 'nullable|string|max:20',
            'contacts.*.phone'          => ['nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
            'contacts.*.is_primary'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required'      => 'Company name is required.',
            'company_name.string'        => 'Company name must be a string.',
            'company_name.max'           => 'Company name may not be greater than 255 characters.',

            'industry_id.integer'        => 'Industry must be an integer.',
            'lead_category_id.integer'   => 'Lead category must be an integer.',
            'assigned_to.integer'        => 'Assigned sales must be an integer.',

            'visibility_type.in'         => 'Visibility must be either PRIVATE or PUBLIC.',
            'visibility_type.string'     => 'Visibility must be a string.',

            'notes.string'               => 'Notes must be a string.',
            'address.string'             => 'Address must be a string.',
            'customer_status.string'     => 'Customer status must be a string.',

            'contacts.required'          => 'Minimal 1 kontak harus diisi.',
            'contacts.array'             => 'Format kontak tidak valid.',
            'contacts.min'               => 'Minimal 1 kontak harus diisi.',

            'contacts.*.name.required'   => 'Nama kontak wajib diisi.',
            'contacts.*.email.email'     => 'Email kontak tidak valid.',
            'contacts.*.id.exists'       => 'Kontak tidak ditemukan.',
            'contacts.*.phone.regex'     => 'Format nomor telepon tidak valid. Gunakan 08xx, +628xx, atau 628xx.',
        ];
    }
}