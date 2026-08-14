<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomersProductPopulationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userIds = $this->parseUserIds($this->resource->user_id_raw ?? null);
        $picList = $this->parsePicList($this->resource->pic_list ?? null);

        $hasCustomer = ! empty($this->resource->customer_id ?? null);
        $hasPic      = ! empty($userIds);

        return [

            'id' => $this->resource->id ?? null,

            'customer' => $hasCustomer ? [
                'id'   => $this->resource->customer_id ?? null,
                'code' => $this->resource->customer_code ?? null,
                'name' => $this->resource->customer_name ?? null,
            ] : null,

            'pump_serial_no'             => $this->resource->pump_serial_no ?? null,
            'product_category'          => $this->resource->product_category ?? null,
            'product_display'           => $this->resource->product_display ?? null,
            'product_model'              => $this->resource->product_model ?? null,
            'tag_no'                     => $this->resource->tag_no ?? null,
            'qty'                        => (int) ($this->resource->qty ?? 0),
            'seal_plan'                  => $this->resource->seal_plan ?? null,
            'mechanical_seal_drawing_no' => $this->resource->mechanical_seal_drawing_no ?? null,

            // array id sales (buat pre-fill checkbox PIC di modal edit)
            'user_id'  => $userIds,
            // array {id, name} sales (buat ditampilkan langsung di tabel)
            'pic_list' => $picList,

            // complete | no_customer | no_pic | empty
            'status' => $this->resolveStatus($hasCustomer, $hasPic),

            'created_at' => !empty($this->resource->created_at ?? null)
                ? Carbon::parse($this->resource->created_at)->format('Y-m-d H:i:s')
                : null,

            'updated_at' => !empty($this->resource->updated_at ?? null)
                ? Carbon::parse($this->resource->updated_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }

    /**
     * Kolom pp.user_id bertipe bigint[] di Postgres, di-select sebagai
     * text lewat `pp.user_id::text` (contoh: "{3,5}"). Fungsi ini
     * parse balik jadi array PHP of int, misal [3, 5]. Array kosong
     * atau NULL -> [].
     */
    private function parseUserIds($raw): array
    {
        if (empty($raw) || $raw === '{}') {
            return [];
        }

        $trimmed = trim($raw, '{}');

        if ($trimmed === '') {
            return [];
        }

        return array_map('intval', explode(',', $trimmed));
    }

    /**
     * pic_list datang dari subquery json_agg() di controller. Tergantung
     * driver PDO, kolom json bisa balik sebagai string JSON mentah,
     * jadi di-decode ulang di sini biar konsisten selalu array.
     */
    private function parsePicList($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveStatus(bool $hasCustomer, bool $hasPic): string
    {
        if (! $hasCustomer && ! $hasPic) {
            return 'empty';
        }

        if (! $hasCustomer) {
            return 'no_customer';
        }

        if (! $hasPic) {
            return 'no_pic';
        }

        return 'complete';
    }
}