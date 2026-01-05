<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

   public function rules(): array
    {
        return [
            'role'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }

    public function messages(): array
{
    return [
        'role.required'        => 'role is required.',
        'role.string'          => 'role must be a string.',
        'role.max'             => 'role may not be greater than 255 characters.',
        'description.required' => 'description is required.',
        'description.string'   => 'description must be a string.',
    ];
}


    protected function prepareForValidation()
    {
        $this->merge([
            'role'        => $this->has('role') ? trim($this->input('role')) : null,
            'description' => $this->has('description') ? trim($this->input('description')) : null,
        ]);
    }
}
