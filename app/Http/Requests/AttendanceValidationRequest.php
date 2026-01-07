<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_type' => 'required|in:IN,OUT',

            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',

            'location_name'   => 'required|string|max:255',

            'device_type'     => 'required|in:WEB,ANDROID,IOS',

            'accuracy'        => 'nullable|numeric|min:0',

            'photo_path'           => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_type.required' => 'Tipe absensi wajib diisi',
            'attendance_type.in'       => 'Tipe absensi tidak valid',

            'latitude.required'        => 'Latitude wajib diisi',
            'latitude.numeric'         => 'Latitude harus berupa angka',

            'longitude.required'       => 'Longitude wajib diisi',
            'longitude.numeric'        => 'Longitude harus berupa angka',

            'location_name.required'   => 'Nama lokasi wajib diisi',

            'device_type.required'     => 'Device wajib diisi',
            'device_type.in'           => 'Device tidak valid',

            'photo_path.required'            => 'Foto wajib diisi',
            'photo_path.image'              => 'Foto harus berupa gambar',
            'photo_path.max'                => 'Ukuran foto maksimal 2MB',
        ];
    }
}
