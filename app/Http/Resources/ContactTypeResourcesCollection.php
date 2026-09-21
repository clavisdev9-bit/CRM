<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * ── NOTE: saya tidak punya isi asli RoleResourcesCollection.php (cuma
 * di-review, tidak diedit, sebelumnya) untuk dicontoh persis -- file ini
 * saya buat mengikuti pola standar Laravel ResourceCollection yang
 * paling umum dipakai (mirip yang dipanggil Administrator::Role() lewat
 * ApiResponse::paginate(new RoleResourcesCollection($results), ...)).
 * Kalau ternyata RoleResourcesCollection.php Anda strukturnya beda,
 * kirim isinya, nanti saya samakan. ──
 */
class ContactTypeResourcesCollection extends ResourceCollection
{
    public $collects = ContactTypeResources::class;

    public function toArray($request)
    {
        return $this->collection;
    }
}