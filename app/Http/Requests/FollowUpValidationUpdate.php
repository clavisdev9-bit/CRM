<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FollowUpValidationUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // TARGET (wajib salah satu)
            // 'lead_id'     => 'nullable|integer|exists:leads,id',
            // 'customer_id' => 'nullable|integer|exists:customers,id',
            // 'status' => 'nullable|in:PENDING,DONE,CANCELED',

            // DATA UTAMA
            // 'subject'        => 'required|string|max:255',
            'follow_up_at' => 'nullable|date_format:Y-m-d H:i',
            // 'follow_up_type' => 'nullable|in:CALL,MEETING,WHATSAPP,EMAIL',
            'notes'          => 'nullable|string',
            'subject'          => 'nullable|string',
        ];
    }

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {

    //         $lead     = $this->input('lead_id');
    //         $customer = $this->input('customer_id');

    //         // ❌ dua-duanya kosong
    //         if (!$lead && !$customer) {
    //             $validator->errors()->add(
    //                 'lead_id',
    //                 'Pilih Lead atau Customer.'
    //             );
    //         }

    //         // ❌ dua-duanya diisi
    //         if ($lead && $customer) {
    //             $validator->errors()->add(
    //                 'lead_id',
    //                 'Tidak boleh memilih Lead dan Customer bersamaan.'
    //             );
    //         }
    //     });
    // }
}
