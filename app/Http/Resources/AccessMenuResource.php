<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessMenuResource extends JsonResource
{
     public function toArray(Request $request): array
    {
       return [
            'id_menu' => $this->id_menu,
            'menu' => $this->menu,
            'has_access' => $this->has_access,
            // 'created_at' => $this->created_at?->toDateString() ?? '-',
            // 'updated_at' => $this->updated_at?->toDateString() ?? '-',
        ];
    }


}
