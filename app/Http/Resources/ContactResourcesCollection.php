<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Sama seperti ContactTypeResourcesCollection -- lihat catatan di file
 * itu soal RoleResourcesCollection.php yang tidak saya punya isi
 * aslinya.
 */
class ContactResourcesCollection extends ResourceCollection
{
    public $collects = ContactResources::class;

    public function toArray($request)
    {
        return $this->collection;
    }
}