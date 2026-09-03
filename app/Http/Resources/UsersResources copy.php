<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'id_user' => $this->id_user,
            'fullname' => $this->fullname,
            'role_id'    => $this->role_id,
            'role'       => [
                'id_role' => $this->role?->id_role,
                'role'    => $this->role?->role,
            ],
            'division'       => [
                'id' => $this->division?->id,
                'name_division'    => $this->division?->name_division,
            ],
            'groups'       => [
                'id_group' => $this->groups?->id_group,
                'name_group'    => $this->groups?->name_group,
            ],
            'username' => $this->username,
            'email'    => $this->email,
            'password' => $this->password,
            'image'  => $this->image,
            'is_active'  => $this->is_active,
            'created_at' => optional($this->created_at)->toDateString(),
            'updated_at' => optional($this->updated_at)->toDateString(),
        ];
    }
}
