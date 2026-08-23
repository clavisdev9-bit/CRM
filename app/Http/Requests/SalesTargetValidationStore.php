<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

/**
 * Validasi buat create/update Sales Target.
 *
 * 3 kolom dimensi semuanya nullable -- SEMUA kosong = target TOTAL:
 *   - odoo_customer_id : target per Customer
 *   - odoo_product_id  : target per Brand (= per product Odoo)
 *   - categ_id/categ_name : target per Kategori (categ_name wajib
 *     ngikut kalau categ_id diisi, dipakai buat denormalisasi nama
 *     kategori langsung di sales_targets)
 *
 * TAPI cuma boleh SALAH SATU dari ketiganya yang keisi per baris --
 * dijaga di withValidator() di bawah, dan di-double lagi di level DB lewat
 * CHECK constraint chk_sales_targets_single_dimension (lihat migration
 * 2026_08_23_000002_add_brand_category_to_sales_targets_table).
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
            'odoo_product_id'  => 'nullable|integer|exists:odoo_products,odoo_product_id',
            'categ_id'         => 'nullable|integer',
            'categ_name'       => 'nullable|string|max:255|required_with:categ_id',
            'target_amount'    => 'required|numeric|min:0',
            'notes'            => 'nullable|string|max:1000',
        ];
    }

    public function withValidator(ValidatorContract $validator)
    {
        $validator->after(function (ValidatorContract $validator) {
            $filledDimensions = collect([
                $this->input('odoo_customer_id'),
                $this->input('odoo_product_id'),
                $this->input('categ_id'),
            ])->filter(fn ($value) => $value !== null && $value !== '')->count();

            if ($filledDimensions > 1) {
                $validator->errors()->add(
                    'odoo_customer_id',
                    'Target cuma boleh 1 jenis: per Customer, per Brand, atau per Kategori (tidak boleh digabung). Kosongkan yang lain kalau mau ganti jenis target.'
                );
            }
        });
    }
}