<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomersProductPopulationValidationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id'                 => 'nullable|integer|exists:customers,id',

            'pump_serial_no'              => 'required|string|max:255',
            'product_category'           => 'required|string|max:100',
            'product_display'            => 'nullable|string|max:255',
            'product_model'              => 'nullable|string|max:255',
            'tag_no'                      => 'required|string|max:100',
            'qty'                         => 'required|integer|min:1',
            'seal_plan'                   => 'nullable|string|max:100',
            'mechanical_seal_drawing_no' => 'nullable|string|max:100',

            // PIC/sales yang menangani baris ini, bisa lebih dari 1
            'user_id'   => 'nullable|array',
            'user_id.*' => 'integer|exists:ms_users,id_user',
        ];
    }
}