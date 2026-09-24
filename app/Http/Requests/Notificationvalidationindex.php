<?php

namespace App\Http\Requests;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class NotificationValidationIndex extends FormRequest
{
    protected array $allowedSortFields = ['created_at', 'title', 'status', 'scheduled_at'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // ── 'mine' ditambahkan ke whitelist -- sebelumnya TIDAK ada di
        // sini, padahal index() di NotificationController membaca
        // $validated['mine']. Akibatnya setiap request yang mengirim
        // ?mine=true (mis. filter "hanya notifikasi saya" di tab Kelola
        // Notifikasi) langsung ditolak 422 "Parameter tidak dikenali:
        // mine" sebelum sempat masuk ke rules() sama sekali. ──
        $allowedParams = ['search', 'sort_by', 'sort_dir', 'per_page', 'page', 'category', 'is_read', 'mine'];

        $unknown = array_diff(array_keys($this->query()), $allowedParams);

        if (!empty($unknown)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Parameter query tidak dikenali: ' . implode(', ', $unknown),
                'errors'  => ['query' => ['Parameter tidak dikenali: ' . implode(', ', $unknown)]],
            ], 422));
        }
    }

    public function rules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:100'],
            'sort_by'  => ['nullable', 'string', 'in:' . implode(',', $this->allowedSortFields)],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'category' => ['nullable', 'string', 'in:' . implode(',', Notification::CATEGORIES)],
            // ── dipakai di endpoint "notifikasi saya" (recipient-side)
            // buat filter belum-dibaca saja, mis. tab "Belum Dibaca" di
            // Notification Center.
            //
            // CATATAN (fix): sengaja TIDAK pakai rule bawaan `boolean`.
            // Rule itu memvalidasi dengan in_array STRICT ke
            // [true, false, 0, 1, '0', '1'] saja -- literal string
            // "true"/"false" yang selalu dikirim frontend lewat query
            // string (URLSearchParams) TIDAK termasuk di situ, jadi
            // ?is_read=false selalu kena 422 walau nilainya valid.
            // Dipakai `in:` di sini supaya string "true"/"false" (dan
            // "0"/"1") tetap lolos. Controller-nya sendiri sudah benar --
            // sudah pakai filter_var(..., FILTER_VALIDATE_BOOLEAN) yang
            // memang mengerti string "true"/"false", jadi tidak perlu
            // diubah. ──
            'is_read'  => ['nullable', 'in:true,false,0,1'],
            // ── dipakai di endpoint admin/manager (index()) buat filter
            // "hanya notifikasi/reminder yang saya buat sendiri" (tab
            // Kelola Notifikasi). Sebelumnya field ini TIDAK ADA SAMA
            // SEKALI di rules -- gabungan dengan bug whitelist di atas,
            // itu penyebab 422 di tab Kelola Notifikasi. Rule sama
            // seperti is_read, alasannya juga sama (string "true"/"false"
            // dari frontend). ──
            'mine'     => ['nullable', 'in:true,false,0,1'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Parameter query tidak valid.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}