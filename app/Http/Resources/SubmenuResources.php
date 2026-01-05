<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmenuResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_submenu' => $this->id_submenu,
            'id_menu'    => $this->id_menu,
            'menu'       => [
                'id_menu' => $this->menu?->id_menu,
                'menu'    => $this->menu?->menu,
            ],
            'url'        => $this->url,
            'icon'       => $this->icon,
            'title'      => $this->title,
            'noted'      => $this->noted,
            'is_active'  => $this->is_active,
            'parent_id'  => $this->parent_id,
            'children'   => SubmenuResources::collection(
                                $this->whenLoaded('children')
                            ),
            'created_at' => optional($this->created_at)->toDateString(),
            'updated_at' => optional($this->updated_at)->toDateString(),
        ];
    }
}
