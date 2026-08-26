<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel master kategori expense -- GANTI dari sebelumnya yang hardcode di
 * Expense::CATEGORIES (app/Models/Expense.php). Sekarang kategori bisa
 * ditambah/diubah/dinonaktifkan langsung lewat tabel ini (insert manual
 * pakai SQL, atau nanti lewat halaman admin), TANPA perlu ubah kode &
 * deploy ulang.
 *
 * Tidak dibuat sebagai foreign key ke tabel expenses.category (yang tetap
 * kolom string) supaya data expense lama tidak ikut berubah/rusak kalau
 * suatu saat nama kategori diubah atau dinonaktifkan -- histori expense
 * tetap utuh apa adanya.
 *
 * Mapping ke product Odoo (expense_category_odoo_products, auto-match by
 * name) TIDAK berubah sama sekali -- masih tetap cocokkan berdasarkan
 * nama kategori seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed 8 kategori awal (sama persis dengan yang dulu ada di
        // Expense::CATEGORIES, sudah dicocokkan dengan nama product
        // "Can be Expensed" di Odoo) supaya dropdown tidak kosong begitu
        // fitur ini aktif.
        DB::table('expense_categories')->insert(
            collect([
                'Gasoline',
                'Entertainment',
                'Parking',
                'Transportation',
                'Toll',
                'Hotel',
                'Restaurant',
                'Other',
            ])->map(fn ($name) => [
                'name'       => $name,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};