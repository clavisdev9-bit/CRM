<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OdooService
{
    protected string $url;
    protected string $db;
    protected string $username;
    protected string $apiKey;
    protected ?int $uid = null;

    public function __construct()
    {
        $this->url      = config('odoo.url');
        $this->db       = config('odoo.db');
        $this->username = config('odoo.username');
        $this->apiKey   = config('odoo.api_key');
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
}