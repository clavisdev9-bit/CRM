<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'sales_id'      => $this->sales_id,
            'sales_name'    => $this->whenLoaded('sales', fn () => $this->sales?->fullname),

            'customer_id'   => $this->customer_id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->company_name),
            'location_name' => $this->location_name,

            'expense_date'  => optional($this->expense_date)->toDateString(),
            'amount'        => (float) $this->amount,
            'category'      => $this->category,
            'description'   => $this->description,

            'attachment'     => $this->attachment,
            'attachment_url' => $this->attachment ? asset('storage/' . $this->attachment) : null,

            'status' => $this->status,

            'approved_by'       => $this->approved_by,
            'approver_name'     => $this->whenLoaded('approver', fn () => $this->approver?->fullname),
            'approved_at'       => optional($this->approved_at)->toDateTimeString(),
            'rejection_reason'  => $this->rejection_reason,

            // ── Info push ke Odoo (hr.expense), diisi otomatis saat approved ──
            'odoo_expense_id'  => $this->odoo_expense_id,
            'odoo_push_status' => $this->odoo_push_status,
            'odoo_push_error'  => $this->odoo_push_error,
            'odoo_pushed_at'   => optional($this->odoo_pushed_at)->toDateTimeString(),

            'created_by' => $this->created_by,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}