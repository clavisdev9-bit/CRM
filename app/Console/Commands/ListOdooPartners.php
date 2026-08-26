<?php

namespace App\Console\Commands;

use App\Services\OdooService;
use Illuminate\Console\Command;

/**
 * Command bantuan buat ambil daftar contact/partner di Odoo (res.partner)
 * -- dipakai buat cocokkan nama CUSTOMER di CRM (customers.company_name)
 * dengan nama PARTNER di Odoo, karena resolveOdooPartnerId() di
 * QuotationController mencocokkan pakai EXACT match nama (bukan ILIKE),
 * jadi harus sama persis biar auto-match jalan. Pola sama persis dengan
 * expense:list-odoo-employees buat fitur Expenses.
 *
 * Kalau nama di CRM & Odoo memang beda (atau ternyata ada lebih dari 1
 * partner di Odoo dengan nama yang sama -- auto-match dianggap ambigu
 * dan TIDAK akan pernah ketemu), solusinya isi manual kolom
 * customers.odoo_partner_id + odoo_partner_name pakai ID yang didapat
 * dari command ini (lihat contoh query UPDATE di akhir output).
 *
 * Cara pakai:
 *   php artisan quotation:list-odoo-partners
 *   php artisan quotation:list-odoo-partners --search="merak"   (filter nama)
 */
class ListOdooPartners extends Command
{
    protected $signature = 'quotation:list-odoo-partners {--search= : Filter nama partner/contact (ILIKE)}';

    protected $description = 'Tampilkan daftar contact/partner di Odoo (res.partner) -- buat dicocokkan dengan company_name customer di tabel customers';

    public function handle(OdooService $odooService)
    {
        $search = $this->option('search');

        $domain = [];
        if ($search) {
            $domain[] = ['name', 'ilike', $search];
        }

        $this->info('Menghubungi Odoo...');

        try {
            $partners = $odooService->searchRead(
                'res.partner',
                $domain,
                ['id', 'name', 'email', 'is_company', 'city'],
                0,
                'name asc'
            );
        } catch (\Throwable $e) {
            $this->error('Gagal ambil data dari Odoo: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (empty($partners)) {
            $this->warn('Tidak ada partner/contact ditemukan' . ($search ? " untuk pencarian \"{$search}\"." : '.'));

            return self::SUCCESS;
        }

        $this->info('Ditemukan ' . count($partners) . ' partner/contact di Odoo:');
        $this->newLine();

        $rows = [];
        foreach ($partners as $p) {
            $rows[] = [
                $p['id'],
                $p['name'],
                $p['is_company'] ? 'Company' : 'Contact',
                $p['city'] ?? '-',
                $p['email'] ?? '-',
            ];
        }
        $this->table(['Odoo Partner ID', 'Nama', 'Tipe', 'Kota', 'Email'], $rows);

        $this->newLine();
        $this->info('Kalau nama di atas TIDAK PERSIS SAMA dengan company_name customer di tabel customers (atau nama yang sama muncul lebih dari 1 baris), auto-match tidak akan pernah ketemu.');
        $this->info('Solusi cepat: isi manual mapping-nya lewat SQL, contoh (ganti ID_CUSTOMER_CRM & ID_PARTNER_ODOO & NAMA_DI_ODOO):');
        $this->newLine();
        $this->line("UPDATE customers SET odoo_partner_id = ID_PARTNER_ODOO, odoo_partner_name = 'NAMA_DI_ODOO' WHERE id = ID_CUSTOMER_CRM;");

        return self::SUCCESS;
    }
}