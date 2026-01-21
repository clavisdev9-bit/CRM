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
            'follow_up_date' => 'required|date_format:Y-m-d H:i:s',
            'follow_up_type' => 'required|in:CALL,MEETING,WHATSAPP,EMAIL',
            'notes'          => 'nullable|string',
        ];
    }
}
