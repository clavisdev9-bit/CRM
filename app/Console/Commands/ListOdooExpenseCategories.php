<?php

namespace App\Console\Commands;

use App\Services\OdooService;
use Illuminate\Console\Command;

/**
 * Command bantuan buat ambil daftar kategori expense yang SEBENARNYA dari
 * Odoo -- yaitu semua product yang ditandai "Can be Expensed" di sana.
 *
 * Kenapa perlu ini: fitur auto-match kategori->product Odoo
 * (ExpenseController::resolveOdooProductIdForCategory()) mencocokkan
 * Expense::CATEGORIES dengan nama product Odoo pakai ILIKE. Supaya selalu
 * ketemu, nama kategori di CRM harus SAMA PERSIS dengan nama product di
 * Odoo. Command ini query langsung ke Odoo (via OdooService yang sudah
 * ada, bukan bikin koneksi baru) supaya Bapak/Ibu bisa copy nama
 * persisnya, tanpa perlu screenshot manual.
 *
 * Cara pakai:
 *   php artisan expense:list-odoo-categories
 *
 * (Laravel 11/12 otomatis mendeteksi command di app/Console/Commands,
 * jadi tidak perlu didaftarkan manual di Kernel/routes/console.php.)
 */
class ListOdooExpenseCategories extends Command
{
    protected $signature = 'expense:list-odoo-categories';

    protected $description = 'Tampilkan daftar product Odoo yang ditandai "Can be Expensed" (calon isi Expense::CATEGORIES)';

    public function handle(OdooService $odooService)
    {
        $this->info('Menghubungi Odoo...');

        try {
            $products = $odooService->searchRead(
                'product.product',
                [['can_be_expensed', '=', true]],
                ['id', 'name'],
                0,
                'name asc'
            );
        } catch (\Throwable $e) {
            $this->error('Gagal ambil data dari Odoo: ' . $e->getMessage());
            $this->line('Cek dulu config odoo.url/db/username/api_key, dan pastikan Odoo bisa diakses dari server ini.');

            return self::FAILURE;
        }

        if (empty($products)) {
            $this->warn('Tidak ada product di Odoo yang ditandai "Can be Expensed".');
            $this->line('Cek di Odoo: Expenses > Configuration > Expense Categories, atau product list dengan filter "Can be Expensed".');

            return self::SUCCESS;
        }

        $this->info('Ditemukan ' . count($products) . ' kategori expense di Odoo:');
        $this->newLine();

        $rows = [];
        foreach ($products as $p) {
            $rows[] = [$p['id'], $p['name']];
        }
        $this->table(['Odoo Product ID', 'Nama (pakai ini sebagai KATEGORI di CRM)'], $rows);

        $this->newLine();
        $this->info('Salin kolom "Nama" di atas PERSIS seperti itu (spasi & huruf besar-kecil ikutan) ke Expense::CATEGORIES di app/Models/Expense.php, atau kirim hasil tabel ini ke saya biar saya yang update.');

        return self::SUCCESS;
    }
}