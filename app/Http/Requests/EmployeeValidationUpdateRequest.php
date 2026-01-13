<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeValidationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'exists:ms_users,id_user'],
             'office_id' => ['sometimes'],
            'attendance_mode' => ['required'],

            'nik' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('employees', 'nik')
                    ->ignore($this->route('id'), 'id_employee'),
            ],

            'tempat_lahir' => ['sometimes', 'string', 'max:100'],
            'tanggal_lahir' => ['sometimes', 'date'],

            'jenis_kelamin' => ['sometimes', 'in:L,P'],

            'alamat' => ['sometimes', 'string', 'max:255'],
            'no_hp' => ['sometimes', 'string', 'max:20'],

            'tanggal_masuk' => ['sometimes', 'date'],

            'status_karyawan' => ['sometimes', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
