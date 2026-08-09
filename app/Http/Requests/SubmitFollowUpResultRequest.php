<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFollowUpResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'nullable|in:PENDING,DONE,CANCELED',
            'result' => 'required|in:success,need_followup,reschedule,no_meet,dealing,closed,cancelled',
            'notes' => 'nullable|string',
            'next_follow_up_at' => 'nullable|date',
            'no_reference' => 'nullable|string|max:100',
            'follow_up_type' => 'nullable|string|max:100',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (
                in_array($this->result, ['need_followup', 'reschedule']) &&
                !$this->next_follow_up_at
            ) {
                $validator->errors()->add('next_follow_up_at', 'Next follow up wajib diisi.');
            }
        });
    }
}