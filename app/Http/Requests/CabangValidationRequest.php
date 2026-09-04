<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class CabangValidationRequest extends FormRequest
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
        'cabang' => [
            'nullable',
            'string',
            'max:255',
            // 'regex:/^[a-zA-Z0-9\s]+$/',
        ],
        'alamat' => [
            'nullable',
            'string',
            'max:255',
        ],
        'no_telp' => [
            'nullable',
            'string',
            'max:20',
        ],

        // ── 1 Cabang = 1 Company -- wajib dipilih ──
        'group_id' => [
            'required',
            'integer',
            'exists:group_companies,id_group',
        ],
    ];
}

public function messages(): array
{
    return [
        'cabang.required' => 'Cabang is required.',
        'cabang.string'   => 'Cabang must be a string.',
        'cabang.max'      => 'Cabang may not be greater than 255 characters.',
        'cabang.regex'    => 'Cabang may only contain letters and spaces.', // ← tambah
        'alamat.string'   => 'Alamat must be a string.',
        'alamat.max'      => 'Alamat may not be greater than 255 characters.',
        'no_telp.string'  => 'No Telp must be a string.',
        'no_telp.max'     => 'No Telp may not be greater than 20 characters.',
        'group_id.required' => 'Company must be selected.',
        'group_id.exists'   => 'The selected company is invalid.',
    ];
}


    protected function prepareForValidation()
    {
        $this->merge([
            'cabang'        => $this->has('cabang') ? trim($this->input('cabang')) : null,
            'alamat'        => $this->has('alamat') ? trim($this->input('alamat')) : null,
            'no_telp'       => $this->has('no_telp') ? trim($this->input('no_telp')) : null,
            'group_id'      => $this->group_id ? (int) $this->group_id : null,
        ]);
    }
}