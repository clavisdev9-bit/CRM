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
            'company_name'       => 'required|string|max:255',
            'contact_name'       => 'required|string|max:255',
            'email'              => 'nullable|email|max:255',
            'phone'              => 'nullable|string|max:20',
            'lead_source'        => 'nullable|string|max:100',
            'lead_status'        => 'nullable|string|max:50',
            'industry_id'        => 'nullable|integer|exists:lead_industries,id',
            'lead_category_id'   => 'nullable|integer|exists:lead_categories,id',
            'assigned_to'        => 'nullable|integer|exists:ms_users,id_user',
            'visibility_type'    => 'nullable|string|in:PRIVATE,PUBLIC,TEAM',
            'notes'              => 'nullable|string',
            'last_contacted_at'  => 'nullable|date',
        ];
    }
}
