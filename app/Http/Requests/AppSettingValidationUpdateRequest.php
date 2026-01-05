<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppSettingValidationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'app_name' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'app_short_name' => [
                'sometimes',
                'string',
                'max:20',
            ],

            'app_tagline' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'app_logo' => [
                'sometimes',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],

            'app_logo_small' => [
                'sometimes',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],

            'favicon' => [
                'sometimes',
                'mimes:ico,jpg,jpeg,png',
                'max:2048',
            ],

            'primary_color' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'secondary_color' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'sidebar_color' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'navbar_color' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'footer_text' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'footer_license_url' => [
                'sometimes',
                'url',
                'max:255',
            ],

            'footer_documentation_url' => [
                'sometimes',
                'url',
                'max:255',
            ],

            'footer_support_url' => [
                'sometimes',
                'url',
                'max:255',
            ],

            'version' => [
                'sometimes',
                'string',
                'max:20',
            ],

            'environment' => [
                'sometimes',
                'in:development,staging,production',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'app_name.string' => 'App name harus berupa string.',

            'app_short_name.string' => 'App short name harus berupa string.',
            'app_tagline.string' => 'App tagline harus berupa string.',

            'app_logo.image' => 'App logo harus berupa gambar.',
            'app_logo_small.image' => 'App logo small harus berupa gambar.',
            'favicon.mimes' => 'Favicon harus berformat ico, jpg, jpeg, atau png.',

            'footer_license_url.url' => 'Footer license URL tidak valid.',
            'footer_documentation_url.url' => 'Footer documentation URL tidak valid.',
            'footer_support_url.url' => 'Footer support URL tidak valid.',

            'environment.in' => 'Environment hanya boleh development, staging, atau production.',
        ];
    }
}
