<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadsValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // penting supaya request diterima
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
         return [
        'company_name'     => 'required|string|max:255',
        'contact_name'     => 'required|string|max:255',
        'email'            => 'required|email|max:255',
        'phone' => [
            'required',
            'regex:/^[0-9+\-.\s()]+([eE][xX][tT]\.?\s*[0-9]{1,6}|[eE][kK][sS][tT]\.?\s*[0-9]{1,6}|[xX]\s*[0-9]{1,6})?$/',
            function ($attribute, $value, $fail) {
                if (preg_match('/(ext\.?|ekst\.?|x)\s*([0-9]{1,6})\s*$/i', $value, $matches, PREG_OFFSET_CAPTURE)) {
                    $main = substr($value, 0, $matches[0][1]);
                    $ext  = $matches[2][0];
                } else {
                    $main = $value;
                    $ext  = null;
                }

                $digitsOnly = preg_replace('/[^0-9]/', '', $main);

                if (strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
                    $fail('Phone number must be 7-15 digits (may include -, spaces, parentheses, +).');
                    return;
                }

                if ($ext !== null && (strlen($ext) < 1 || strlen($ext) > 6)) {
                    $fail('Invalid extension number, maximum 6 digits.');
                }
            },
        ],
        'lead_source'      => 'required|string|max:100',

        'industry_id'      => 'required|integer|exists:lead_industries,id',
        'lead_category_id' => 'required|integer|exists:lead_categories,id',
        'assigned_to'      => 'nullable|integer',

        'visibility_type'  => 'nullable|in:PRIVATE,PUBLIC,TEAM',
        'notes'            => 'nullable|string',
        'address'          => 'nullable|string',

    ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
        // Company Name
        'company_name.required' => 'Company name is required.',
        'company_name.string'   => 'Company name must be a string.',
        'company_name.max'      => 'Company name may not be greater than 255 characters.',
        // Contact Name
        'contact_name.required' => 'Contact name is required.',
        'contact_name.string'   => 'Contact name must be a string.',
        'contact_name.max'      => 'Contact name may not be greater than 255 characters.',
        // Email
        'email.required' => 'Email is required.',
        'email.email'    => 'Please enter a valid email address.',
        'email.max'      => 'Email may not be greater than 255 characters.',
        // Phone
        'phone.required' => 'Phone number is required.',
        'phone.regex'    => 'Invalid phone number format.',
        // Lead Source
        'lead_source.required' => 'Lead source is required.',
        'lead_source.string'   => 'Lead source must be a string.',
        'lead_source.max'      => 'Lead source may not be greater than 100 characters.',
        // Industry
        'industry_id.required' => 'Industry is required.',
        'industry_id.integer'  => 'Invalid industry selected.',
        'industry_id.exists'   => 'The selected industry does not exist.',
        // Lead Category
        'lead_category_id.required' => 'Lead category is required.',
        'lead_category_id.integer'  => 'Invalid lead category selected.',
        'lead_category_id.exists'   => 'The selected lead category does not exist.',
        // Assigned To
        'assigned_to.integer' => 'Invalid assigned user.',
        // Visibility Type
        'visibility_type.in' => 'Visibility type must be one of: PRIVATE, PUBLIC, TEAM.',
        // Notes
        'notes.string' => 'Notes must be a string.',
        // Address
        'address.string' => 'Address must be a string.',
        ];
    }
}
