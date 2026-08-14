<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Referensi model buat tabel `product_populations`. Karena kolom
 * `user_id` bertipe bigint[] (array native Postgres) dan Eloquent
 * tidak punya cast bawaan untuk itu, controller
 * (CustomersProductPopulation) sengaja baca/tulis kolom ini lewat
 * DB::table() + raw query (unnest/ANY/toPgArray), bukan lewat model
 * ini. Model ini cukup dipakai untuk relasi ringan kalau dibutuhkan
 * di tempat lain (mis. eager load dari sisi Customer).
 *
 * Kalau model ini sudah ada di project dengan isi lain, silakan
 * merge saja bagian $table / $fillable / relasinya ke file yang
 * sudah ada — file ini cuma referensi.
 */
class CustomersProductPopulationModel extends Model
{
    protected $table = 'product_populations';

    protected $fillable = [
        'customer_id',
        'pump_serial_no',
        'product_category',
        'product_display',
        'product_model',
        'tag_no',
        'qty',
        'seal_plan',
        'mechanical_seal_drawing_no',
        'user_id',
    ];

    public function customer()
    {
        return $this->belongsTo(MsCustomers::class, 'customer_id', 'id');
    }
}