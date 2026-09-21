<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Contact;

/**
 * ── Validasi untuk "Link dari Data Existing" (tarik contact dari
 * customers/leads/customer_contacts/branch_contacts). Eksistensi
 * source_id di tabel yang sesuai TIDAK dicek di sini -- tidak bisa
 * pakai rule 'exists:' biasa karena tabelnya polymorphic (beda-beda
 * tergantung source_type), jadi dicek manual di
 * ContactController::storeLink() lewat Contact::resolveSource(). ──
 */
class ContactLinkValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_type' => ['required', 'string', 'in:' . implode(',', Contact::SOURCE_TYPES)],
            'source_id'   => ['required', 'integer', 'min:1'],
            'notes'       => ['nullable', 'string'],
            'status'      => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_type.required' => 'Sumber contact wajib dipilih.',
            'source_type.in'       => 'Sumber contact tidak valid. Hanya diperbolehkan: ' . implode(', ', Contact::SOURCE_TYPES),
            'source_id.required'   => 'Data yang mau di-link wajib dipilih.',
            'source_id.integer'    => 'Data yang mau di-link tidak valid.',
            'status.in'            => 'Status harus Active atau Inactive.',
        ];
    }
}