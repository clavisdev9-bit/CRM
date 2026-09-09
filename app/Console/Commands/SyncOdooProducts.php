<?php

namespace App\Console\Commands;

use App\Models\OdooProduct;
use App\Services\OdooService;
use Illuminate\Console\Command;

class SyncOdooProducts extends Command
{
    // cara sync manual dari CLI: php artisan odoo:sync-products
    // (di aplikasi, sync dipicu tombol "Sync Sekarang" oleh Manager lewat
    // ProductController@sync, yang internally manggil command ini juga
    // lewat Artisan::call() -- jadi logic-nya cuma didefinisikan di sini,
    // ga dobel).
    protected $signature = 'odoo:sync-products';
    protected $description = 'Sync data product (product.product) dari Odoo ke tabel lokal';

    public function handle(OdooService $odoo)
    {
        // PENTING: sync ini SEKARANG narik product dari SEMUA company
        // sekaligus (bukan cuma 1 default company_id lagi). company_id asli
        // dari Odoo disimpan apa adanya per product (kolom company_id di
        // odoo_products), dan penentuan "product ini boleh dilihat company
        // CRM yang mana" dilakukan belakangan pas listing, di
        // ProductController -- bukan di tahap sync ini.
        //
        // Kalau sync dibatasi ke 1 company_id di sini kayak sebelumnya,
        // product punya company lain (misal PT My Everything) ga akan
        // pernah ke-tarik ke tabel lokal sama sekali, walaupun mapping
        // odoo_company_id di group_companies sudah bener.
        $this->info('Fetching products from Odoo (semua company)...');

        // sale_ok=true biar cuma produk yang memang bisa DIJUAL yang
        // ke-sync (bukan raw material/consumable internal Odoo). Odoo
        // search_read secara default cuma balikin active=true, jadi produk
        // yang di-archive di Odoo otomatis ga ke-pull lagi di sini.
        $domain = [
            ['sale_ok', '=', true],
        ];

        $products = $odoo->searchRead(
            'product.product',
            $domain,
            [
                'id', 'name', 'default_code', 'barcode',
                'categ_id', 'uom_id', 'company_id',
                'list_price', 'standard_price', 'qty_available',
                'active',
            ],
            0,
            'name asc'
        );

        $this->info(count($products) . ' product ditemukan. Menyimpan ke database...');

        $total = 0;

        foreach ($products as $p) {
            OdooProduct::updateOrCreate(
                ['odoo_product_id' => $p['id']],
                [
                    'name'           => $p['name'] ?? null,
                    'default_code'   => $p['default_code'] ?? null,
                    'barcode'        => $p['barcode'] ?? null,
                    // categ_id/uom_id balik sebagai [id, name] dari Odoo.
                    'categ_id'       => $p['categ_id'][0] ?? null,
                    'categ_name'     => $p['categ_id'][1] ?? null,
                    'uom_id'         => $p['uom_id'][0] ?? null,
                    'uom_name'       => $p['uom_id'][1] ?? null,
                    // company_id juga balik sebagai [id, name] -- ambil
                    // id-nya aja. false/kosong artinya shared/global.
                    'company_id'     => $p['company_id'][0] ?? null,
                    'list_price'     => $p['list_price'] ?? 0,
                    'standard_price' => $p['standard_price'] ?? 0,
                    'qty_available'  => $p['qty_available'] ?? 0,
                    'active'         => $p['active'] ?? true,
                ]
            );
            $total++;
        }

        $this->info("Selesai! {$total} product tersinkron.");

        return self::SUCCESS;
    }
}