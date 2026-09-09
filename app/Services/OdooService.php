<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class OdooService
{
    protected string $url;
    protected string $db;
    protected string $username;
    protected string $apiKey;
    protected ?int $defaultCompanyId = null;
    protected ?int $uid = null;

    public function __construct()
    {
        // ── Koneksi dibaca dari tabel odoo_settings (menu Odoo Settings),
        // BUKAN langsung dari .env lagi. Schema::hasTable() dicek dulu
        // supaya OdooService tidak ikut error kalau migration
        // create_odoo_settings_table belum sempat dijalankan di server
        // (misalnya di tengah proses deploy) -- fallback ke config('odoo.*')
        // (nilai .env lama) di semua kondisi itu.
        $settings = Schema::hasTable('odoo_settings')
            ? DB::table('odoo_settings')->first()
            : null;

        $this->url      = $settings->url      ?? config('odoo.url');
        $this->db       = $settings->db       ?? config('odoo.db');
        $this->username = $settings->username ?? config('odoo.username');
        $this->apiKey   = $settings->api_key  ?? config('odoo.api_key');

        $defaultCompanyId = $settings->default_company_id ?? config('odoo.default_company_id');
        $this->defaultCompanyId = $defaultCompanyId !== null ? (int) $defaultCompanyId : null;
    }

    protected function call(string $service, string $method, array $args)
    {
        $response = Http::post("{$this->url}/jsonrpc", [
            'jsonrpc' => '2.0',
            'method'  => 'call',
            'params'  => [
                'service' => $service,
                'method'  => $method,
                'args'    => $args,
            ],
            'id' => rand(1, 999999),
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            throw new \Exception(json_encode($data['error']));
        }

        return $data['result'];
    }

    public function authenticate(): int
    {
        if ($this->uid) {
            return $this->uid;
        }

        $this->uid = $this->call('common', 'authenticate', [
            $this->db, $this->username, $this->apiKey, []
        ]);

        return $this->uid;
    }

    /**
     * Search & read records from Odoo.
     *
     * @param string $model  Nama model odoo, misal 'product.template'
     * @param array  $domain Domain filter, misal [['sale_ok', '=', true]]
     * @param array  $fields Field yang mau diambil
     * @param int    $limit  0 = tanpa limit
     * @param string $order  Contoh: 'name asc', 'id desc'
     */
    public function searchRead(string $model, array $domain = [], array $fields = [], int $limit = 0, string $order = '')
    {
        $uid = $this->authenticate();

        $options = [
            'fields' => $fields,
            'limit'  => $limit,
        ];

        if ($order !== '') {
            $options['order'] = $order;
        }

        return $this->call('object', 'execute_kw', [
            $this->db,
            $uid,
            $this->apiKey,
            $model,
            'search_read',
            [$domain],
            $options,
        ]);
    }

    /**
     * Hitung jumlah record yang cocok dengan domain, tanpa fetch datanya.
     * Berguna untuk validasi/debug apakah filter sudah benar
     * (bandingkan dengan jumlah yang tampil di UI Odoo).
     */
    public function searchCount(string $model, array $domain = [])
    {
        $uid = $this->authenticate();

        return $this->call('object', 'execute_kw', [
            $this->db,
            $uid,
            $this->apiKey,
            $model,
            'search_count',
            [$domain],
        ]);
    }

    /**
     * Buat satu record baru di Odoo.
     * Dipakai fitur Expenses buat push expense yang sudah di-approve
     * sebagai record hr.expense baru.
     *
     * @param string $model  Nama model Odoo, misal 'hr.expense'
     * @param array  $values Field-value yang mau disimpan, misal
     *                       ['name' => 'Lunch meeting', 'employee_id' => 5, 'product_id' => 12, ...]
     * @return int ID record yang baru dibuat di Odoo
     */
    public function create(string $model, array $values): int
    {
        $uid = $this->authenticate();

        return $this->call('object', 'execute_kw', [
            $this->db,
            $uid,
            $this->apiKey,
            $model,
            'create',
            [$values],
        ]);
    }

    /**
     * Update record yang SUDAH ADA di Odoo. Dipakai fitur Quotations --
     * quotation boleh diedit bebas di CRM (tidak ada approval workflow
     * kayak Expenses), jadi push ke Odoo bisa dipicu berkali-kali. Kalau
     * quotation itu sudah pernah punya odoo_sale_order_id, push
     * berikutnya WAJIB pakai write() (update record yang sama), BUKAN
     * create() lagi -- supaya tidak numpuk record duplikat di Odoo tiap
     * di-push ulang.
     *
     * @param string $model  Nama model Odoo, misal 'sale.order'
     * @param int    $id     ID record Odoo yang mau diupdate
     * @param array  $values Field-value yang mau diupdate
     * @return bool
     */
    public function write(string $model, int $id, array $values): bool
    {
        $uid = $this->authenticate();

        return $this->call('object', 'execute_kw', [
            $this->db,
            $uid,
            $this->apiKey,
            $model,
            'write',
            [[$id], $values],
        ]);
    }

// start code untuk debug  mencari field di oddo ini berelasi ke controller
    public function fieldsGet(string $model)
    {
        $uid = $this->authenticate();

        return $this->call('object', 'execute_kw', [
            $this->db,
            $uid,
            $this->apiKey,
            $model,
            'fields_get',
            [],
            ['attributes' => ['string', 'type']],
        ]);
    }

    public function searchModels(string $keyword)
    {
        $uid = $this->authenticate();

        return $this->call('object', 'execute_kw', [
            $this->db,
            $uid,
            $this->apiKey,
            'ir.model',
            'search_read',
            [[['model', 'like', $keyword]]],
            ['fields' => ['model', 'name']],
        ]);
    }

    /**
     * Company_id Odoo default GLOBAL (dari odoo_settings.default_company_id,
     * fallback ke config('odoo.default_company_id') / .env). Dipakai untuk
     * proses yang memang TIDAK per-CRM-company -- misalnya SyncOdooProducts
     * (katalog produk dianggap shared, bukan milik 1 company CRM tertentu).
     */
    public function defaultCompanyId(): ?int
    {
        return $this->defaultCompanyId;
    }

    /**
     * Company_id Odoo untuk 1 company CRM tertentu. Odoo mendukung
     * multi-company dalam SATU instance/database -- kolom
     * group_companies.odoo_company_id memetakan tiap company CRM
     * (group_id) ke company_id yang berkorespondensi di Odoo.
     *
     * Kalau $groupId tidak dikirim, atau company itu belum di-mapping
     * (odoo_company_id masih null), fallback ke defaultCompanyId() --
     * supaya proses push ke Odoo tidak mendadak gagal cuma karena
     * mapping-nya belum sempat diisi admin di menu Odoo Settings.
     */
    public function companyIdFor(?int $groupId = null): ?int
    {
        if ($groupId) {
            $mapped = DB::table('group_companies')
                ->where('id_group', $groupId)
                ->value('odoo_company_id');

            if ($mapped !== null) {
                return (int) $mapped;
            }
        }

        return $this->defaultCompanyId;
    }
}