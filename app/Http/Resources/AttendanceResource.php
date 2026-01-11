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

            // Relasi User
            'user' => [
                'id_user'  => $this->user?->id_user,
                'fullname' => $this->user?->fullname,
                'email' => $this->user?->email,
            ],

            // Relasi Employee
            'employee' => [
                'id_employee' => $this->employee?->id_employee,
                'nik'         => $this->employee?->nik,
            ],

            // Attendance Core
            'attendance_mode'     => $this->attendance_mode, // Tambahan dari DB
            'attendance_type'     => $this->attendance_type,
            'attendance_date'     => $this->attendance_date,
            'attendance_time'     => $this->attendance_time,
            'attendance_datetime' => $this->attendance_datetime,
            'attendance_status'   => $this->attendance_status,

            // Media & Notes
            'photo_path' => $this->photo_path, // Tambahan dari DB
            'noted'      => $this->noted,      // Tambahan dari DB

            // Location (Current)
            'location_name'   => $this->location_name,
            'latitude'        => $this->latitude,
            'longitude'       => $this->longitude,
            'accuracy'        => $this->accuracy,
            'accuracy_status' => $this->accuracy_status,

            // Office Reference (Data pembanding saat absen)
            'office_latitude'  => $this->office_latitude,  // Tambahan dari DB
            'office_longitude' => $this->office_longitude, // Tambahan dari DB
            
            // Policy & Distance
            'policy_status'        => $this->policy_status,
            'policy_reason'        => $this->policy_reason,
            'distance_from_office' => $this->distance_from_office,
            'allowed_radius'       => $this->allowed_radius,

            // Device Info
            'device_type' => $this->device_type,
            'ip_address'  => $this->ip_address,

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}