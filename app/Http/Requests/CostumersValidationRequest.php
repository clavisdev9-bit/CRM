<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CostumersValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'company_name'      => 'required|string|max:255',
            'contact_name'      => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:50',
            'industry_id'       => 'nullable|integer',
            'lead_category_id'  => 'nullable|integer',
            'assigned_to'       => 'nullable|integer',
            'visibility_type'   => 'nullable|string|in:PRIVATE,PUBLIC',
            'notes'             => 'nullable|string',
            'address'           => 'nullable|string',
        ];
    }

    /**
     * Custom error messages for validation.
     */
    // public function messages(): array
    // {
    //     return [
    //         'company_name.required'      => 'Nama perusahaan wajib diisi.',
    //         'company_name.string'        => 'Nama perusahaan harus berupa teks.',
    //         'company_name.max'           => 'Nama perusahaan maksimal 255 karakter.',

    //         'contact_name.required'      => 'Nama kontak wajib diisi.',
    //         'contact_name.string'        => 'Nama kontak harus berupa teks.',
    //         'contact_name.max'           => 'Nama kontak maksimal 255 karakter.',

    //         'email.email'                => 'Email tidak valid.',
    //         'email.max'                  => 'Email maksimal 255 karakter.',

    //         'phone.string'               => 'Nomor telepon harus berupa teks.',
    //         'phone.max'                  => 'Nomor telepon maksimal 50 karakter.',

    //         'industry_id.integer'        => 'Industri harus berupa angka.',
    //         'lead_category_id.integer'   => 'Kategori lead harus berupa angka.',
    //         'assigned_to.integer'        => 'Sales yang ditugaskan harus berupa angka.',

    //         'visibility_type.in'         => 'Visibility harus PRIVATE atau PUBLIC.',
    //         'visibility_type.string'     => 'Visibility harus berupa teks.',

    //         'notes.string'               => 'Catatan harus berupa teks.',
    //         'address.string'             => 'Alamat harus berupa teks.',
    //     ];
    // }

    public function messages(): array
{
    return [
        'company_name.required'      => 'Company name is required.',
        'company_name.string'        => 'Company name must be a string.',
        'company_name.max'           => 'Company name may not be greater than 255 characters.',

        'contact_name.required'      => 'Contact name is required.',
        'contact_name.string'        => 'Contact name must be a string.',
        'contact_name.max'           => 'Contact name may not be greater than 255 characters.',

        'email.email'                => 'Email must be a valid email address.',
        'email.max'                  => 'Email may not be greater than 255 characters.',

        'phone.string'               => 'Phone number must be a string.',
        'phone.max'                  => 'Phone number may not be greater than 50 characters.',

        'industry_id.integer'        => 'Industry must be an integer.',
        'lead_category_id.integer'   => 'Lead category must be an integer.',
        'assigned_to.integer'        => 'Assigned sales must be an integer.',

        'visibility_type.in'         => 'Visibility must be either PRIVATE or PUBLIC.',
        'visibility_type.string'     => 'Visibility must be a string.',

        'notes.string'               => 'Notes must be a string.',
        'address.string'             => 'Address must be a string.',
    ];
}

}
