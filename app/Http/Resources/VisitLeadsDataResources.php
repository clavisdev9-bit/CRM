<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class VisitLeadsDataResources extends JsonResource
{
   
     public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // =====================
            // DATA PROSPEK
            // =====================
            'company_name' => $this->company_name,
            'contact_name' => $this->contact_name,
            'email'        => $this->email,
            'phone'        => $this->phone,

            // =====================
            // RELATION IDS
            // =====================
            'lead_category_id' => $this->lead_category_id,
            'industry_id'      => $this->industry_id,

            // =====================
            // OWNERSHIP
            // =====================
            'id_user'     => $this->id_user,
            'assigned_to' => $this->assigned_to,
            'created_by'  => $this->created_by,

             // =====================
            // OWNERSHIP (JOIN ALIAS)
            // =====================
            'owner_name'    => $this->owner_name ?? null,
            'assigned_name' => $this->assigned_name ?? null,
         

            // =====================
            // STATUS & VISIBILITY
            // =====================
            'visibility_type' => $this->visibility_type,
            'lead_source'     => $this->lead_source,
            'lead_status'     => $this->lead_status,

            // =====================
            // ACTIVITY
            // =====================
            'notes' => $this->notes,
            'address' => $this->address,
            'active_visit_id' => $this->active_visit_id,

             // =====================
            // JOIN DATA NAME
            // =====================

             'category_name' => $this->category_name,
            'industry_name' => $this->industry_name,

          'last_contacted_at' => $this->last_contacted_at
            ? Carbon::parse($this->last_contacted_at)->format('Y-m-d H:i:s')
            : null,

            'converted_at' => $this->converted_at
                ? Carbon::parse($this->converted_at)->format('Y-m-d H:i:s')
                : null,


            // =====================
            // AUDIT
            // =====================
           'created_at' => $this->created_at
            ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s')
            : null,

            'updated_at' => $this->updated_at
                ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s')
                : null,

            'deleted_at' => $this->deleted_at
                ? Carbon::parse($this->deleted_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}
