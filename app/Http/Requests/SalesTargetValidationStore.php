<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi buat create/update Sales Target.
 * odoo_customer_id nullable -- NULL = target total sales, diisi = target
 * khusus 1 customer Odoo tertentu (opsional).
 */
class SalesTargetValidationStore extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'sales_id'         => 'required|integer|exists:ms_users,id_user',
            'period_year'      => 'required|integer|min:2000|max:2100',
            'odoo_customer_id' => 'nullable|integer|exists:odoo_customers,odoo_partner_id',
            'target_amount'    => 'required|numeric|min:0',
            'notes'            => 'nullable|string|max:1000',
        ];
    }
}