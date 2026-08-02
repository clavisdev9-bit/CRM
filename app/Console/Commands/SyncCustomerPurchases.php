<?php

namespace App\Console\Commands;

use App\Models\OdooCustomer;
use App\Models\OdooCustomerPurchaseItem;
use App\Services\OdooService;
use Illuminate\Console\Command;

class SyncCustomerPurchases extends Command
{

// cara sync php artisan odoo:sync-customers   
    protected $signature = 'odoo:sync-customer-purchases';
    protected $description = 'Sync data pembelian customer (sale.order + sale.order.line) dari Odoo';

    public function handle(OdooService $odoo)
    {
        $companyId = (int) config('odoo.default_company_id');

        $this->info("Fetching sale orders for company_id={$companyId}...");

        $orders = $odoo->searchRead(
            'sale.order',
            [
                ['company_id', '=', $companyId],
                ['state', 'in', ['sale', 'done']],
            ],
            ['id', 'name', 'date_order', 'partner_id', 'company_id'],
            0,
            'id asc'
        );

        if (empty($orders)) {
            $this->warn('Tidak ada sale order ditemukan.');
            return self::SUCCESS;
        }

        $this->info(count($orders) . ' sale orders ditemukan. Fetching order lines...');

        $orderById = collect($orders)->keyBy('id');
        $orderIds = $orderById->keys()->toArray();

        $chunks = array_chunk($orderIds, 200);
        $totalSynced = 0;

        foreach ($chunks as $chunkIds) {
            $lines = $odoo->searchRead(
                'sale.order.line',
                [
                    ['order_id', 'in', $chunkIds],
                    ['product_id', '!=', false],
                ],
                ['id', 'order_id', 'product_id', 'product_uom_qty', 'price_unit']
            );

            foreach ($lines as $line) {
                $orderId = $line['order_id'][0] ?? null;
                $order = $orderById->get($orderId);

                if (!$order) {
                    continue;
                }

                $customer = $order['partner_id'] ?? [null, null];
                $product = $line['product_id'] ?? [null, null];

                OdooCustomerPurchaseItem::updateOrCreate(
                    ['odoo_order_line_id' => $line['id']],
                    [
                        'odoo_order_id'    => $orderId,
                        'order_name'       => $order['name'] ?? null,
                        'order_date'       => $order['date_order'] ?? null,
                        'odoo_customer_id' => $customer[0] ?? 0,
                        'odoo_product_id'  => $product[0] ?? 0,
                        'product_name'     => $product[1] ?? null,
                        'qty'              => $line['product_uom_qty'] ?? 0,
                        'price_unit'       => $line['price_unit'] ?? 0,
                        'company_id'       => $order['company_id'][0] ?? $companyId,
                    ]
                );

                $totalSynced++;
            }

            $this->info("Chunk selesai, total baris tersinkron: {$totalSynced}");
        }

        // update status has_purchased & total_transaksi berdasarkan jumlah ORDER (bukan line item)
        $customerOrderCounts = collect($orders)
            ->groupBy(fn($o) => $o['partner_id'][0] ?? 0)
            ->map->count();

        foreach ($customerOrderCounts as $partnerId => $count) {
            if (!$partnerId) {
                continue;
            }

            OdooCustomer::where('odoo_partner_id', $partnerId)->update([
                'has_purchased'   => true,
                'total_transaksi' => $count,
            ]);
        }

        $this->info("Selesai! {$totalSynced} item pembelian tersinkron, " . $customerOrderCounts->count() . ' customer status ter-update.');

        return self::SUCCESS;
    }
}