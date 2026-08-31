<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseValidationStore extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_date' => ['required', 'date'],
            'amount'       => ['required', 'numeric', 'min:0'],
            // Kategori sekarang divalidasi terhadap tabel master
            // expense_categories (yang aktif saja), bukan list hardcode
            // lagi -- lihat model ExpenseCategory.
            'category'     => [
                'required',
                'string',
                Rule::exists('expense_categories', 'name')->where('is_active', true),
            ],
            'description'  => ['nullable', 'string'],
            // Kunjungan (opsional) -- customer_id kalau dipilih dari
            // dropdown-search (harus customer yang ada di sistem),
            // location_name buat nama lokasi/customer yang diketik
            // manual (dipakai apa adanya kalau customer_id kosong, atau
            // di-override otomatis dari nama customer di
            // ExpenseController::store() kalau customer_id diisi).
            'customer_id'   => ['nullable', 'integer', 'exists:customers,id'],
            'location_name' => ['nullable', 'string', 'max:255'],
            // Lampiran (foto struk/bill) DIWAJIBKAN -- sebelumnya nullable,
            // diubah jadi required sebagai kontrol supaya sales tidak bisa
            // ajukan expense tanpa bukti fisik (antisipasi klaim fiktif).
            'attachment'   => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.exists'     => 'Kategori tidak valid atau sudah tidak aktif. Silakan pilih dari daftar kategori yang tersedia.',
            'attachment.required' => 'Lampiran foto struk/bill wajib diisi sebagai bukti pengeluaran.',
            'attachment.mimes'    => 'Lampiran harus berupa file JPG, JPEG, PNG, atau PDF.',
            'attachment.max'      => 'Ukuran lampiran maksimal 4 MB.',
        ];
    }
}