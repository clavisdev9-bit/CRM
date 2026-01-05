<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ✅ WAJIB
    }

    public function rules(): array
    {
        return [
            'attendance_type' => 'required|in:IN,OUT',

            'latitude'        => 'required|numeric|between:-90,90',
            'longitude'       => 'required|numeric|between:-180,180',

            'location_name'   => 'required|string|max:255',

            'device_type'     => 'required|in:DESKTOP,MOBILE',

            'photo'           => 'nullable|image|max:2048', // opsional
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_type.required' => 'Tipe absensi wajib diisi',
            'attendance_type.in'       => 'Tipe absensi tidak valid',

            'latitude.required'        => 'Latitude wajib diisi',
            'longitude.required'       => 'Longitude wajib diisi',

            'device_type.required'     => 'Device wajib diisi',
            'device_type.in'           => 'Device tidak valid',
        ];
    }
}
