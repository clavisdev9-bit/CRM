<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsersValidationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'fullname' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'username' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('ms_users', 'username')
                    ->ignore($userId, 'id_user')
                    ->whereNull('deleted_at'),
            ],

            'email' => [
                'sometimes',
                'email',
                'max:100',
                Rule::unique('ms_users', 'email')
                    ->ignore($userId, 'id_user')
                    ->whereNull('deleted_at'),
            ],

            'password' => [
                'nullable',
                'string',
                'min:6',
            ],


            'role_id' => [
                'sometimes',
                'integer',
                'exists:ms_role,id_role',
            ],

            
            'divisi_id' => [
                'sometimes',
                'integer',
               'exists:ms_division,id',
            ],

            'group_id' => [
                'sometimes',
                'integer',
                'exists:group_companies,id_group',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'image' => [
                'sometimes',
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
            'username.unique' => 'Username sudah digunakan.',
            'email.unique'    => 'Email sudah digunakan.',
            'role_id.exists'  => 'Role tidak ditemukan.',
            'divisi_id.exists'    => 'Divisi tidak ditemukan.',
            'group_id.exists'    => 'Group tidak ditemukan.',
            'image.image'     => 'File harus berupa gambar.',
            'image.mimes'     => 'Format gambar harus jpg, jpeg, atau png.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('fullname')) {
            $data['fullname'] = trim($this->fullname);
        }

        if ($this->has('username')) {
            $data['username'] = trim($this->username);
        }

        if ($this->has('email')) {
            $data['email'] = trim($this->email);
        }

        $this->merge($data);
    }
}
