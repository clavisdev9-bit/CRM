<?php

namespace App\Console\Commands;

use App\Models\OdooCustomer;
use App\Services\OdooService;
use Illuminate\Console\Command;

class SyncOdooCustomers extends Command
{
    // cara sync php artisan odoo:sync-customers
    protected $signature = 'odoo:sync-customers';
    protected $description = 'Sync data customer (res.partner) dari Odoo ke tabel lokal';

    public function handle(OdooService $odoo)
    {
        // PENTING: sync ini SEKARANG narik customer dari SEMUA company
        // sekaligus (bukan cuma 1 default company_id lagi) -- pola sama
        // persis kayak SyncOdooProducts. company_id asli dari Odoo
        // disimpan apa adanya per customer (kolom company_id di
        // odoo_customers), dan penentuan "customer ini boleh dilihat
        // company CRM yang mana" dilakukan belakangan pas listing, di
        // OdooSync controller -- bukan di tahap sync ini.
        //
        // Kalau sync dibatasi ke 1 company_id di sini kayak sebelumnya,
        // customer punya company lain (misal PT My Everything) ga akan
        // pernah ke-tarik ke tabel lokal sama sekali, walaupun mapping
        // odoo_company_id di group_companies sudah bener.
        $this->info('Fetching customers from Odoo (semua company)...');

        $domain = [
            ['customer_rank', '>', 0],
        ];

        // Catatan: field 'mobile' ditambahin ke daftar fields -- sebelumnya
        // ga diminta ke Odoo padahal dipakai di bawah ($c['mobile']),
        // jadinya kolom mobile selalu kesimpen null. Sekalian dibenerin.
        $customers = $odoo->searchRead(
            'res.partner',
            $domain,
            ['id', 'name', 'email', 'phone', 'mobile', 'contact_address', 'is_company', 'company_id'],
            0,
            'name asc'
        );

        $this->info(count($customers) . ' customer ditemukan. Menyimpan ke database...');

        $total = 0;

        foreach ($customers as $c) {
            OdooCustomer::updateOrCreate(
                ['odoo_partner_id' => $c['id']],
                [
                    'name'       => $c['name'] ?? null,
                    'email'      => $c['email'] ?? null,
                    'phone'      => $c['phone'] ?? null,
                    'mobile'     => $c['mobile'] ?? null,
                    'address'    => $c['contact_address'] ?? null,
                    'is_company' => $c['is_company'] ?? false,
                    // company_id balik sebagai [id, name] dari Odoo -- ambil
                    // id-nya aja. false/kosong artinya shared/global.
                    'company_id' => $c['company_id'][0] ?? null,
                ]
            );
            $total++;
        }

        $this->info("Selesai! {$total} customer tersinkron.");

        return self::SUCCESS;
    }
}