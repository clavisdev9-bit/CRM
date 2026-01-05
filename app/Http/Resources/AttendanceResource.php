<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // relasi user
            'user' => [
                'id_user'  => $this->user?->id_user,
                'fullname' => $this->user?->fullname,
                'username' => $this->user?->username,
            ],

            // relasi employee
            'employee' => [
                'id_employee' => $this->employee?->id_employee,
                'nik'         => $this->employee?->nik,
            ],

            // attendance core
            'attendance_type' => $this->attendance_type,
            'attendance_date' => $this->attendance_date,
            'attendance_time' => $this->attendance_time,
            'attendance_datetime' => $this->attendance_datetime,

            // location
            'location_name' => $this->location_name,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'accuracy'      => $this->accuracy,
            'accuracy_status' => $this->accuracy_status,

            // policy
            'policy_status' => $this->policy_status,
            'policy_reason' => $this->policy_reason,
            'distance_from_office' => $this->distance_from_office,
            'allowed_radius'       => $this->allowed_radius,

            // device
            'device_type' => $this->device_type,
            'ip_address'  => $this->ip_address,

            // status
            'attendance_status' => $this->attendance_status,

            // timestamps
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
