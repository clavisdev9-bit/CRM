<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FollowUpValidationRequest extends FormRequest
{
  public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_id'       => 'nullable|exists:leads,id',
            'customer_id'   => 'nullable|exists:customers,id',

            'follow_up_date'=> 'required|date',
            'follow_up_type'=> 'required|in:CALL,MEETING,WHATSAPP,EMAIL',
            'notes'         => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (empty($this->lead_id) && empty($this->customer_id)) {
                $validator->errors()->add(
                    'target',
                    'Lead atau Customer wajib diisi salah satu'
                );
            }

            if (!empty($this->lead_id) && !empty($this->customer_id)) {
                $validator->errors()->add(
                    'target',
                    'Tidak boleh mengisi Lead dan Customer bersamaan'
                );
            }
        });
    }
}
