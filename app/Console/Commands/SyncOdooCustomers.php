<?php

namespace App\Console\Commands;

use App\Models\OdooCustomer;
use App\Services\OdooService;
use Illuminate\Console\Command;

class SyncOdooCustomers extends Command
{
    // cara sync php artisan odoo:sync-customer-purchases
    protected $signature = 'odoo:sync-customers';
    protected $description = 'Sync data customer (res.partner) dari Odoo ke tabel lokal';

    public function handle(OdooService $odoo)
    {
        $companyId = (int) config('odoo.default_company_id');

        $this->info("Fetching customers for company_id={$companyId}...");

        $domain = [
            ['customer_rank', '>', 0],
            '|',
            ['company_id', '=', $companyId],
            ['company_id', '=', false],
        ];

       $customers = $odoo->searchRead(
    'res.partner',
    $domain,
    ['id', 'name', 'email', 'phone', 'contact_address', 'is_company', 'company_id'],
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
                    'company_id' => $c['company_id'][0] ?? null,
                ]
            );
            $total++;
        }

        $this->info("Selesai! {$total} customer tersinkron.");

        return self::SUCCESS;
    }
}