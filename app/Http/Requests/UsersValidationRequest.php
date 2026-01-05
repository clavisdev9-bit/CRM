<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsersValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullname' => [
                'required',
                'string',
                'max:100',
            ],

            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ms_users', 'username')
                    ->whereNull('deleted_at'),
            ],

            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('ms_users', 'email')
                    ->whereNull('deleted_at'),
            ],

            'password' => [
                'required',
                'min:6',
            ],

            'role_id' => [
                'required',
                'integer',
                'exists:ms_role,id_role',
            ],

            'divisi_id' => [
                'required',
                'integer',
                'exists:ms_division,id',
            ],

            'group_id' => [
                'required',
                'integer',
                'exists:group_companies,id_group',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fullname.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique'   => 'Username sudah digunakan.',
            'email.required'    => 'Email wajib diisi.',
            'email.unique'      => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'role_id.required'  => 'Role wajib dipilih.',
            'role_id.exists'    => 'Role tidak ditemukan.',
            'divisi_id.required'  => 'Divisi wajib dipilih.',
            'divisi_id.exists'    => 'Divisi tidak ditemukan.',
            'group_id.required'  => 'Group wajib dipilih.',
            'group_id.exists'    => 'Group tidak ditemukan.',
            'image.image'       => 'File harus berupa gambar.',
            'image.mimes'       => 'Format gambar harus jpg, jpeg, atau png.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fullname' => trim($this->fullname),
            'username' => trim($this->username),
            'email'    => trim($this->email),
        ]);
    }
}
