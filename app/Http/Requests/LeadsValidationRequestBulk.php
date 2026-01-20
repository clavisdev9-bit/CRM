<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadsValidationRequestBulk extends FormRequest
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
        'leads' => 'required|array|min:1',

        'leads.*.company_name' => 'required|string|max:255',
        'leads.*.contact_name' => 'required|string|max:255',
        'leads.*.email'        => 'nullable|email|max:255',
        'leads.*.phone'        => 'required|string|max:20',
        'leads.*.lead_source'  => 'required|string|max:100',

        'leads.*.industry_id'      => 'required|integer|exists:lead_industries,id',
        'leads.*.lead_category_id' => 'required|integer|exists:lead_categories,id',
        'leads.*.assigned_to'      => 'nullable|integer',

        'leads.*.notes' => 'nullable|string|max:1000',
        'leads.*.address' => 'nullable|string|max:1000',
    ];
}



public function messages(): array
{
    return [
        'leads.*.company_name.required' => 'Company name is required.',
        'leads.*.contact_name.required' => 'Contact name is required.',
        'leads.*.lead_source.required'  => 'Lead source is required.',

        'leads.*.email.email' => 'Email format is invalid.',

        'leads.*.industry_id.required' => 'Industry is required.',
        'leads.*.industry_id.exists'   => 'Industry not found.',

        'leads.*.lead_category_id.required' => 'Category is required.',
        'leads.*.lead_category_id.exists'   => 'Category not found.',
        'leads.*.assigned_to.required' => 'Assigned to is required.',
     

        'leads.*.notes.string' => 'Notes must be text.',
        'leads.*.notes.max'    => 'Notes may not be greater than 1000 characters.',

         'leads.*.address.string' => 'Address must be text.',
        'leads.*.address.max'    => 'Address may not be greater than 1000 characters.',

    ];
}


}
