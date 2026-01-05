<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppSettingResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'app_name' => $this->app_name,
            'app_short_name' => $this->app_short_name,
            'app_tagline' => $this->app_tagline,
            'app_logo' => $this->app_logo,
            'app_logo_small' => $this->app_logo_small,
            'favicon' => $this->favicon,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'sidebar_color' => $this->sidebar_color,
            'navbar_color' => $this->navbar_color,
            'footer_text' => $this->footer_text,
            'footer_license_url' => $this->footer_license_url,
            'footer_documentation_url' => $this->footer_documentation_url,
            'footer_support_url' => $this->footer_support_url,
            'version' => $this->version,
            'environment' => $this->environment,
            'created_at' => $this->created_at?->toDateString() ?? '-',
            'updated_at' => $this->updated_at?->toDateString() ?? '-',
        ];
    }
}
