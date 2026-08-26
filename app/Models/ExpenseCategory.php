<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Master kategori expense -- SUMBER KEBENARAN BARU untuk daftar kategori
 * (menggantikan Expense::CATEGORIES yang hardcode). Dikelola lewat tabel
 * ini supaya Admin bisa tambah/ubah/nonaktifkan kategori sendiri lewat
 * SQL langsung (atau nanti halaman admin), tanpa perlu deploy ulang kode.
 *
 * Kolom `category` di tabel expenses TETAP string biasa (bukan foreign
 * key ke sini) -- sengaja begitu supaya histori expense lama tidak ikut
 * berubah kalau nama kategori di sini diubah/dinonaktifkan.
 *
 * Pakai cast boolean eksplisit buat is_active supaya aman dari isu
 * Postgres yang bisa balikin boolean sebagai string "t"/"f" kalau lewat
 * DB::table() -- lewat Eloquent Model begini sudah otomatis di-cast jadi
 * boolean PHP asli.
 */
class ExpenseCategory extends Model
{
    use HasFactory;

    protected $table = 'expense_categories';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}