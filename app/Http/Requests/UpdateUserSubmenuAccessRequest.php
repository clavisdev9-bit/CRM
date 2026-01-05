<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSubmenuAccessRequest extends FormRequest
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
            'can_view'   => ['required', 'boolean'],
            'can_create' => ['required', 'boolean'],
            'can_update' => ['required', 'boolean'],
            'can_delete' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'can_view.required'    => 'View permission is required.',
            'can_view.boolean'     => 'View permission must be true or false.',

            'can_create.required'  => 'Create permission is required.',
            'can_create.boolean'   => 'Create permission must be true or false.',

            'can_update.required'  => 'Update permission is required.',
            'can_update.boolean'   => 'Update permission must be true or false.',

            'can_delete.required'  => 'Delete permission is required.',
            'can_delete.boolean'   => 'Delete permission must be true or false.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'can_view'   => $this->has('can_view') ? (bool) $this->input('can_view') : false,
            'can_create' => $this->has('can_create') ? (bool) $this->input('can_create') : false,
            'can_update' => $this->has('can_update') ? (bool) $this->input('can_update') : false,
            'can_delete' => $this->has('can_delete') ? (bool) $this->input('can_delete') : false,
        ]);
    }
}
