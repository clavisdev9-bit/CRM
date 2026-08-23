<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CostumersValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name'      => 'required|string|max:255',
            'industry_id'       => 'required|integer',
            'lead_category_id'  => 'required|integer',
            'assigned_to'       => 'nullable|integer',
            'lead_source'       => 'required|string|max:255',
            'visibility_type'   => 'nullable|string|in:PRIVATE,PUBLIC',
            'notes'             => 'nullable|string',
            'address'           => 'required|string',
            'customer_status'   => 'required|string',

            // ── GEOLOKASI (auto-fill dari forward-geocoding Address, lihat
            //    Location::search() -- disiapkan sekalian buat matching radius
            //    Visit Check-In di phase 2, tapi belum dipakai di phase 1 ini) ──
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'radius_meter'      => 'nullable|integer|min:10|max:5000',

            // ── CONTACTS (bisa banyak) ──
            'contacts'                  => 'required|array|min:1',
            'contacts.*.id'             => 'nullable|integer|exists:customer_contacts,id',
            'contacts.*.name'           => 'required|string|max:100',
            'contacts.*.position'       => 'nullable|string|max:100',
            'contacts.*.email'          => 'nullable|email|max:100',
            // 'contacts.*.phone'          => 'nullable|string|max:20',
            // 'contacts.*.phone'          => ['nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
            'contacts.*.phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[0-9\-\s()]{8,20}$/'],
            'contacts.*.is_primary'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required'      => 'Company name is required.',
            'company_name.string'        => 'Company name must be a string.',
            'company_name.max'           => 'Company name may not be greater than 255 characters.',

            'industry_id.integer'        => 'Industry must be an integer.',
            'lead_category_id.integer'   => 'Lead category must be an integer.',
            'assigned_to.integer'        => 'Assigned sales must be an integer.',

            'visibility_type.in'         => 'Visibility must be either PRIVATE or PUBLIC.',
            'visibility_type.string'     => 'Visibility must be a string.',

            'notes.string'               => 'Notes must be a string.',
            'address.string'             => 'Address must be a string.',
            'customer_status.string'     => 'Customer status must be a string.',

            'latitude.numeric'           => 'Latitude harus berupa angka.',
            'latitude.between'           => 'Latitude harus di antara -90 dan 90.',
            'longitude.numeric'          => 'Longitude harus berupa angka.',
            'longitude.between'          => 'Longitude harus di antara -180 dan 180.',
            'radius_meter.integer'       => 'Radius harus berupa angka bulat.',
            'radius_meter.min'           => 'Radius minimal 10 meter.',
            'radius_meter.max'           => 'Radius maksimal 5000 meter.',

            'contacts.required'          => 'Minimal 1 kontak harus diisi.',
            'contacts.array'             => 'Format kontak tidak valid.',
            'contacts.min'               => 'Minimal 1 kontak harus diisi.',

            'contacts.*.name.required'   => 'Nama kontak wajib diisi.',
            'contacts.*.email.email'     => 'Email kontak tidak valid.',
            'contacts.*.id.exists'       => 'Kontak tidak ditemukan.',
            'contacts.*.phone.regex'     => 'Format nomor telepon tidak valid. Gunakan 08xx, +628xx, atau 628xx.',
        ];
    }
}