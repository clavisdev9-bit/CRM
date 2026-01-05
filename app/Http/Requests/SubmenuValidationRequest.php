<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmenuValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_menu' => [
                'required',
                'integer',
                'exists:ms_menu,id_menu',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ms_submenu', 'title')
                    ->where('id_menu', $this->id_menu)
                    ->whereNull('deleted_at'),
            ],

            'url' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ms_submenu', 'url')
                    ->whereNull('deleted_at'),
            ],

            'icon' => [
                'nullable',
                'string',
                'max:255',
            ],

            'noted' => [
                'nullable',
                'string',
                'max:500',
            ],

            'parent_id' => [
                'nullable',
                'integer',
                'exists:ms_submenu,id_submenu',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_menu.required'   => 'Menu wajib dipilih.',
            'id_menu.exists'     => 'Menu tidak ditemukan.',

            'title.required'     => 'Title submenu wajib diisi.',
            'title.unique'       => 'Title submenu sudah digunakan pada menu ini.',

            'url.required'       => 'URL submenu wajib diisi.',
            'url.unique'         => 'URL submenu sudah digunakan.',

            'parent_id.exists'   => 'Parent submenu tidak valid.',

            'is_active.required' => 'Status aktif wajib diisi.',
            'is_active.boolean'  => 'Status aktif harus bernilai true / false.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->title ? trim($this->title) : null,
            'url'   => $this->url ? trim($this->url) : null,
            'icon'  => $this->icon ? trim($this->icon) : null,
            'noted' => $this->noted ? trim($this->noted) : null,
        ]);
    }
}
