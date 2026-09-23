<?php

namespace App\Http\Requests;

use App\Models\CatalogSendLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogSendValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $channel = $this->input('channel');

        return [
            'product_id'      => ['required', 'integer', Rule::exists('products_catalog', 'id')],
            // ── catalog_id wajib diisi khusus channel email (supaya ada
            // lampiran/link catalog yang jelas dikirim), opsional untuk
            // whatsapp karena bisa saja hanya link product secara umum. ──
            'catalog_id'      => [$channel === 'email' ? 'required' : 'nullable', 'integer', Rule::exists('catalogs', 'id')],
            'channel'         => ['required', 'string', Rule::in(CatalogSendLog::CHANNELS)],
            'recipient_name'  => ['nullable', 'string', 'max:150'],
            'recipient_email' => [$channel === 'email' ? 'required' : 'nullable', 'nullable', 'email', 'max:150'],
            'recipient_phone' => [$channel === 'whatsapp' ? 'required' : 'nullable', 'nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'      => 'Produk wajib dipilih.',
            'product_id.exists'        => 'Produk tidak ditemukan.',
            'catalog_id.required'      => 'Catalog (PDF/Video) wajib dipilih untuk pengiriman via email.',
            'catalog_id.exists'        => 'Catalog tidak ditemukan.',
            'channel.required'         => 'Channel pengiriman wajib dipilih.',
            'channel.in'               => 'Channel pengiriman tidak valid.',
            'recipient_email.required' => 'Email penerima wajib diisi untuk pengiriman via email.',
            'recipient_email.email'    => 'Format email penerima tidak valid.',
            'recipient_phone.required' => 'Nomor WhatsApp penerima wajib diisi.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'recipient_name'  => $this->filled('recipient_name') ? trim($this->input('recipient_name')) : null,
            'recipient_email' => $this->filled('recipient_email') ? trim($this->input('recipient_email')) : null,
            'recipient_phone' => $this->filled('recipient_phone') ? trim($this->input('recipient_phone')) : null,
        ]);
    }
}