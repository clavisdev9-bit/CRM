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
        'leads'                    => 'required|array|min:1',
        'leads.*.company_name'     => 'required|string|max:255',
        'leads.*.contact_name'     => 'required|string|max:255',
        'leads.*.email'            => 'nullable|email|max:255',
        'leads.*.phone'            => 'nullable|string|max:20',
        'leads.*.lead_source'      => 'nullable|string|max:100',
        'leads.*.lead_status'      => 'nullable|string|max:50',
        'leads.*.industry_id'      => 'nullable|integer|exists:lead_industries,id',
        'leads.*.lead_category_id' => 'nullable|integer|exists:lead_categories,id',
        'leads.*.assigned_to'      => 'nullable|integer|exists:ms_users,id_user',
        'leads.*.visibility_type'  => 'nullable|string|in:PRIVATE,PUBLIC,TEAM',
        'leads.*.notes'            => 'nullable|string',
        'leads.*.last_contacted_at'=> 'nullable|date',
    ];
    }
}
