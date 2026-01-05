<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeValidationRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:ms_users,id_user'],

            'nik' => ['required', 'string', 'max:50'],

            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date'],

            'jenis_kelamin' => ['required', 'in:L,P'],

            'alamat' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'string', 'max:20'],

            'tanggal_masuk' => ['required', 'date'],

            'status_karyawan' => ['required', 'in:Tetap,Kontrak'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User is required.',
            'user_id.exists'   => 'User not found.',

            'nik.required' => 'NIK is required.',
            'nik.string'   => 'NIK must be a string.',
            'nik.max'      => 'NIK may not be greater than 50 characters.',

            'tempat_lahir.required' => 'Place of birth is required.',
            'tanggal_lahir.required' => 'Date of birth is required.',

            'jenis_kelamin.required' => 'Gender is required.',
            'jenis_kelamin.in'       => 'Gender must be L or P.',

            'alamat.required' => 'Address is required.',
            'no_hp.required'  => 'Phone number is required.',

            'tanggal_masuk.required' => 'Join date is required.',

            'status_karyawan.required' => 'Employee status is required.',
            'status_karyawan.in'       => 'Employee status must be aktif or nonaktif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nik'           => $this->has('nik') ? trim($this->input('nik')) : null,
            'tempat_lahir'  => $this->has('tempat_lahir') ? trim($this->input('tempat_lahir')) : null,
            'alamat'        => $this->has('alamat') ? trim($this->input('alamat')) : null,
            'no_hp'         => $this->has('no_hp') ? trim($this->input('no_hp')) : null,
        ]);
    }
}

