<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class QuotationValidationUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // {quotation} diambil dari segment route (route model binding
        // manual di controller, bukan implicit binding -- lihat pola
        // yang sama di ExpenseController), jadi ambil dari route
        // parameter biasa.
        $quotationId = $this->route('id');

        return [
            'customer_id'            => ['required', 'integer', 'exists:customers,id'],
            'customer_company_name'  => ['required', 'string', 'max:255'],
            'customer_address'       => ['required', 'string'],
            'customer_pic_name'      => ['required', 'string', 'max:255'],

            // Opsional -- lihat catatan yang sama di QuotationValidationStore.
            'quotation_no'   => [
                'nullable', 'string', 'max:100',
                Rule::unique('quotations', 'quotation_no')->ignore($quotationId),
            ],
            'customer_ref'   => ['required', 'string', 'max:255'],
            'payment_terms'  => ['required', 'string', 'max:255'],
            'quotation_date' => ['required', 'date'],
            'pages'          => ['nullable', 'string', 'max:50'],
            'validity'       => ['required', 'string', 'max:255'],
            'delivery_time'  => ['required', 'string', 'max:255'],
            'term'           => ['nullable', 'string'],
            'ppn'            => ['required', 'numeric', 'min:0'],
            'signature'      => ['nullable', 'string', 'max:255'],

            'items'                    => ['required', 'array', 'min:1'],
            'items.*.odoo_product_id'  => ['nullable', 'integer', 'exists:odoo_products,id'],
            'items.*.description'     => ['required', 'string'],
            'items.*.quantity'        => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'            => ['required', 'string', 'max:50'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'quotation_no.unique'  => 'Nomor quotation ini sudah dipakai, silakan pakai nomor lain.',
            'items.required'       => 'Minimal harus ada 1 baris item.',
            'items.min'            => 'Minimal harus ada 1 baris item.',
        ];
    }
}