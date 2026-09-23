<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductCatalogValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id') ?? $this->route('product');

        return [
            'category_id'  => ['required', 'integer', Rule::exists('categories_product_catalog', 'id')],
            'sku'          => ['required', 'string', 'max:100'],
            'name'         => ['required', 'string', 'max:150'],
            'description'  => ['nullable', 'string'],
            'price'        => ['nullable', 'numeric', 'min:0'],
            'stock'        => ['nullable', 'integer', 'min:0'],
            // ── field upload thumbnail (bukan nama kolom thumbnail_url) ──
            'thumbnail'    => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists'   => 'Kategori tidak ditemukan.',
            'sku.required'         => 'SKU wajib diisi.',
            'sku.max'              => 'SKU maksimal 100 karakter.',
            'name.required'        => 'Nama produk wajib diisi.',
            'name.max'             => 'Nama produk maksimal 150 karakter.',
            'price.numeric'        => 'Harga harus berupa angka.',
            'price.min'            => 'Harga tidak boleh negatif.',
            'stock.integer'        => 'Stok harus berupa angka bulat.',
            'stock.min'            => 'Stok tidak boleh negatif.',
            'thumbnail.image'      => 'File thumbnail harus berupa gambar.',
            'thumbnail.max'        => 'Ukuran thumbnail maksimal 2MB.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'sku'  => $this->has('sku') ? trim($this->input('sku')) : null,
            'name' => $this->has('name') ? trim($this->input('name')) : null,
        ]);
    }
}