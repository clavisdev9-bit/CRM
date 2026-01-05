<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class MenuValidationRequest extends FormRequest
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
            'menu' => ['required', 'string', 'max:255']
        ];
    }

    public function messages(): array
{
    return [
        'menu.required'        => 'menu is required.',
        'menu.string'          => 'menu must be a string.',
        'menu.max'             => 'menu may not be greater than 255 characters.',
    ];
}


    protected function prepareForValidation()
    {
        $this->merge([
            'menu'        => $this->has('menu') ? trim($this->input('menu')) : null,
        ]);
    }
}
