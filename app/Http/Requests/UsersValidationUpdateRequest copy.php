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
                'required',
                'string',
                'max:100',
            ],

            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ms_users', 'username')
                    ->ignore($userId, 'id_user')
                    ->whereNull('deleted_at'),
            ],

            'email' => [
                'required',
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
                'username.unique'   => 'Username is already taken.',
                'email.unique'      => 'Email address is already in use.',
                'role_id.exists'    => 'Selected role does not exist.',
                'divisi_id.exists'  => 'Selected division does not exist.',
                'group_id.exists'   => 'Selected group does not exist.',
                'image.image'       => 'The file must be an image.',
                'image.mimes'       => 'The image must be a file of type: jpg, jpeg, or png.',
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
