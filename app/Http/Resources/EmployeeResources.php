<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResources extends JsonResource
{
   public function toArray(Request $request): array
{
    return [
        'id_employee' => $this->id_employee,
        'user_id'     => $this->user_id,
        'office_id'   => $this->office_id,  // ← TAMBAHKAN di root

        // ===== OFFICE (relasi dari employee, bukan dari user) =====
        'office' => $this->whenLoaded('office', function () {
            return [
                'id'          => $this->office->id,
                'office_name' => $this->office->office_name,
                'latitude'    => $this->office->latitude,
                'longitude'   => $this->office->longitude,
                'radius'      => $this->office->radius,
            ];
        }),

        'user' => $this->whenLoaded('user', function () {
            return [
                'id_user'   => $this->user->id_user,
                'fullname'  => $this->user->fullname,
                'username'  => $this->user->username,
                'email'     => $this->user->email,
                'image'     => $this->user->image,
                'is_active' => $this->user->is_active,

                // ===== DIVISION =====
                'division' => $this->user->relationLoaded('division')
                    ? [
                        'id'            => $this->user->division?->id,
                        'name_division' => $this->user->division?->name_division,
                    ]
                    : null,

                // ===== GROUP =====
                'group' => $this->user->relationLoaded('groups')
                    ? [
                        'id_group'   => $this->user->groups?->id_group,
                        'name_group' => $this->user->groups?->name_group,
                    ]
                    : null,
            ];
        }),

        // ===== DATA EMPLOYEE =====
        'nik'             => $this->nik,
        'tempat_lahir'    => $this->tempat_lahir,
        'tanggal_lahir'   => $this->tanggal_lahir,
        'jenis_kelamin'   => $this->jenis_kelamin,
        'alamat'          => $this->alamat,
        'no_hp'           => $this->no_hp,
        'tanggal_masuk'   => $this->tanggal_masuk,
        'status_karyawan' => $this->status_karyawan,
        'attendance_mode' => $this->attendance_mode,

        'created_at' => $this->created_at?->toDateTimeString(),
        'updated_at' => $this->updated_at?->toDateTimeString(),
        'deleted_at'  => $this->deleted_at?->toDateTimeString(),
    ];
}
}
