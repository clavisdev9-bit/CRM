<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmenuValidationUpdateRequest extends FormRequest
{
   public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $submenuId = $this->route('id'); // id_submenu dari route

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
                    ->ignore($submenuId, 'id_submenu')
                    ->where('id_menu', $this->id_menu)
                    ->whereNull('deleted_at'),
            ],

            'url' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ms_submenu', 'url')
                    ->ignore($submenuId, 'id_submenu')
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
                Rule::notIn([$submenuId]), // ❌ parent = dirinya sendiri
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
            'id_menu.required' => 'Menu wajib dipilih.',
            'id_menu.exists'   => 'Menu tidak ditemukan.',

            'title.required'   => 'Title submenu wajib diisi.',
            'title.unique'     => 'Title submenu sudah digunakan pada menu ini.',

            'url.required'     => 'URL submenu wajib diisi.',
            'url.unique'       => 'URL submenu sudah digunakan.',

            'parent_id.not_in' => 'Parent submenu tidak boleh dirinya sendiri.',
            'parent_id.exists' => 'Parent submenu tidak valid.',

            'is_active.required' => 'Status aktif wajib diisi.',
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
