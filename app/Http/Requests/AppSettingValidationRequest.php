<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppSettingValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'app_name' => [
                'required',
                'string',
                'max:20',
            ],

            'app_short_name' => [
                'required',
                'string',
                'max:20',
            ],

            'app_tagline' => [
                'required',
                'string',
                'max:50',
            ],

            'app_logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],

            'app_logo_small' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],

            'favicon' => [
                'nullable',
                'mimes:ico,jpg,jpeg,png',
                'max:2048',
            ],

            'primary_color' => [
                'required',
                'string',
                'max:50',
            ],

            'secondary_color' => [
                'required',
                'string',
                'max:50',
            ],

            'sidebar_color' => [
                'required',
                'string',
                'max:50',
            ],

            'navbar_color' => [
                'required',
                'string',
                'max:50',
            ],

            'footer_text' => [
                'required',
                'string',
                'max:255',
            ],

            'footer_license_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'footer_documentation_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'footer_support_url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'version' => [
                'required',
                'string',
                'max:20',
            ],

            'environment' => [
                'required',
                'in:development,staging,production',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'app_name.required' => 'App name is required.',
                'app_short_name.required' => 'App short name is required.',
                'app_tagline.required' => 'App tagline is required.',

                'app_logo.image' => 'App logo must be an image.',
                'app_logo.mimes' => 'App logo must be a JPG, JPEG, or PNG file.',

                'app_logo_small.image' => 'App logo small must be an image.',
                'app_logo_small.mimes' => 'App logo small must be a JPG, JPEG, or PNG file.',

                'favicon.mimes' => 'Favicon must be an ICO, JPG, JPEG, or PNG file.',

                'primary_color.required' => 'Primary color is required.',
                'secondary_color.required' => 'Secondary color is required.',
                'sidebar_color.required' => 'Sidebar color is required.',
                'navbar_color.required' => 'Navbar color is required.',

                'footer_text.required' => 'Footer text is required.',
                'footer_license_url.url' => 'Footer license URL must be a valid URL.',
                'footer_documentation_url.url' => 'Footer documentation URL must be a valid URL.',
                'footer_support_url.url' => 'Footer support URL must be a valid URL.',

                'version.required' => 'Version is required.',
                'environment.in' => 'Environment must be one of: development, staging, or production.',

        ];
    }
}
