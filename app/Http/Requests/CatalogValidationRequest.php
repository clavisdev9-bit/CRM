<?php

namespace App\Http\Requests;

use App\Models\Catalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpload = $this->input('source_type') === 'upload';

        return [
            'product_id'  => ['required', 'integer', Rule::exists('products_catalog', 'id')],
            'media_type'  => ['required', 'string', Rule::in(Catalog::MEDIA_TYPES)],
            'source_type' => ['required', 'string', Rule::in(Catalog::SOURCE_TYPES)],
            'title'       => ['nullable', 'string', 'max:150'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            // ── kalau source_type = upload, file wajib (untuk create;
            // untuk update file boleh dikosongkan kalau tidak mau ganti
            // file -- controller yang menentukan itu, bukan rule ini). ──
            'file' => [
                $isUpload && $this->isMethod('post') && !$this->has('_method') ? 'required' : 'nullable',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return;
                    }

                    $mediaType = $this->input('media_type');
                    $ext = strtolower($value->getClientOriginalExtension());

                    if ($mediaType === 'pdf' && $ext !== 'pdf') {
                        $fail('File untuk media PDF harus berformat .pdf.');
                    }

                    if ($mediaType === 'video' && !in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm'])) {
                        $fail('File untuk media video harus berformat mp4, mov, avi, mkv, atau webm.');
                    }
                },
            ],
            // ── kalau source_type BUKAN upload, url wajib diisi manual.
            // max dinaikkan dari 500 -> 2048: link YouTube/Vimeo memang
            // pendek, tapi link CDN pihak ketiga (mis. Widen/Brightcove/
            // dsb) sering berupa signed URL dengan query string panjang
            // yang bisa jauh melebihi 500 karakter. ──
            'url' => [$isUpload ? 'nullable' : 'required', 'nullable', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'  => 'Produk wajib dipilih.',
            'product_id.exists'    => 'Produk tidak ditemukan.',
            'media_type.required'  => 'Jenis media wajib dipilih.',
            'media_type.in'        => 'Jenis media tidak valid.',
            'source_type.required' => 'Sumber media wajib dipilih.',
            'source_type.in'       => 'Sumber media tidak valid.',
            'file.required'        => 'File wajib diupload untuk sumber media Upload.',
            'file.max'             => 'Ukuran file maksimal 10MB.',
            'url.required'         => 'URL wajib diisi untuk sumber media ini.',
        ];
    }
}