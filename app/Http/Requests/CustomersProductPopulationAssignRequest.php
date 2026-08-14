<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomersProductPopulationAssignRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // baris-baris product population yang mau di-assign
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:product_populations,id',

            // sales tujuan (single target untuk 1x klik assign)
            'user_id' => 'required|integer|exists:ms_users,id_user',
        ];
    }
}