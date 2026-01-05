<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSubmenuAccessResource extends JsonResource
{
    

   public function toArray(Request $request): array
{
    return [
        'id_access_submenu' => $this->id_access_submenu,
        'id_user'           => $this->id_user,
        'id_submenu'        => $this->id_submenu,

        // UI table (flattened)
        'menu_name'    => $this->menu_name ?? '-',
        'submenu_name' => $this->submenu_name ?? '-',

        // permissions (checkbox)
        'can_view'   => (bool) $this->can_view,
        'can_create' => (bool) $this->can_create,
        'can_update' => (bool) $this->can_update,
        'can_delete' => (bool) $this->can_delete,

        // optional
        // 'created_at' => $this->created_at,
        // 'updated_at' => $this->updated_at,
    ];
}


}
