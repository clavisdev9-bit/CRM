<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon; // Pastikan Carbon di-import

class FollowUpResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'lead_id'        => $this->lead_id,
            'customer_id'    => $this->customer_id,
            'follow_up_type' => $this->follow_up_type,
            'subject'        => $this->subject,
            'notes'          => $this->notes,
            
            /* ================= DATE FORMATTING ================= */
            // Gunakan Carbon::parse() untuk mengubah string menjadi objek Carbon sebelum di-format
            'follow_up_at'   => $this->follow_up_at 
                ? Carbon::parse($this->follow_up_at)->toDateTimeString() 
                : '-',
            
            'status'         => $this->status,
            'assigned_to'    => $this->assigned_to,
            'created_by'     => $this->created_by,
            
            // Lakukan hal yang sama untuk created_at & updated_at jika mereka string
            'created_at'     => $this->created_at 
                ? Carbon::parse($this->created_at)->toDateString() 
                : '-',
                
            'updated_at'     => $this->updated_at 
                ? Carbon::parse($this->updated_at)->toDateString() 
                : '-',
        ];
    }
}
