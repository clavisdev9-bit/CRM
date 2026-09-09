<?php

namespace App\Http\Controllers\Api\Odoo;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\OdooCompanyMappingValidationRequest;
use App\Http\Requests\OdooConnectionValidationRequest;
use App\Services\OdooService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * ODOO SETTINGS -- menu config koneksi Odoo (Administrator/IT saja)
 * ----------------------------------------------------------------------------
 * Satu instance Odoo dipakai bersama oleh SEMUA company CRM (url/db/
 * username/api_key sama) -- disimpan di tabel odoo_settings (cuma 1 baris),
 * menggantikan pembacaan langsung dari .env (config/odoo.php).
 *
 * Yang beda per company CUMA company_id di sisi Odoo (Odoo mendukung
 * multi-company dalam 1 instance) -- disimpan di
 * group_companies.odoo_company_id, di-manage lewat updateCompanyMapping().
 * Lihat OdooService::companyIdFor() untuk cara pemakaiannya di fitur lain
 * (Expenses, Quotations, dst).
 *
 * Semua endpoint di sini dicek ulang manual role_id === 1 (bukan cuma
 * mengandalkan middleware route) -- konsisten dengan pola defense-in-depth
 * yang sudah dipakai di ExpenseController/QuotationController untuk data
 * sensitif.
 * ============================================================================
 */
class OdooSettingsController extends Controller
{
    protected OdooService $odooService;

    public function __construct(OdooService $odooService)
    {
        $this->odooService = $odooService;
    }

    private function ensureAdmin($user): bool
    {
        return $user && (int) $user->role_id === 1;
    }

    // ════════════════════════════════════════════
    // GET -- koneksi global + daftar company & mapping-nya
    // ════════════════════════════════════════════
    public function index()
    {
        $user = auth()->user();
        if (!$this->ensureAdmin($user)) {
            return ApiResponse::error('Anda tidak memiliki akses ke pengaturan ini', 403);
        }

        $settings = DB::table('odoo_settings')->first();

        $connection = [
            'url'                => $settings->url ?? null,
            'db'                 => $settings->db ?? null,
            'username'           => $settings->username ?? null,
            'api_key'            => $settings->api_key ?? null,
            'default_company_id' => $settings->default_company_id ?? null,
            'updated_at'         => $settings->updated_at ?? null,
        ];

        $companies = DB::table('group_companies')
            ->select('id_group', 'name_group', 'odoo_company_id', 'is_active')
            ->orderBy('name_group', 'asc')
            ->get();

        return ApiResponse::success([
            'connection' => $connection,
            'companies'  => $companies,
        ], 'Success');
    }

    // ════════════════════════════════════════════
    // PUT -- update koneksi global
    // ════════════════════════════════════════════
    public function updateConnection(OdooConnectionValidationRequest $request)
    {
        $user = auth()->user();
        if (!$this->ensureAdmin($user)) {
            return ApiResponse::error('Anda tidak memiliki akses ke pengaturan ini', 403);
        }

        $validated = $request->validated();
        $exists    = DB::table('odoo_settings')->exists();

        $payload = [
            'url'                => $validated['url'],
            'db'                 => $validated['db'],
            'username'           => $validated['username'],
            'api_key'            => $validated['api_key'],
            'default_company_id' => $validated['default_company_id'] ?? null,
            'updated_by'         => $user->id_user,
            'updated_at'         => now(),
        ];

        if ($exists) {
            // Tabel ini sengaja cuma 1 baris -- limit(1) jaga-jaga saja
            // kalau suatu saat ada >1 baris ke-insert manual di luar sistem.
            DB::table('odoo_settings')->limit(1)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('odoo_settings')->insert($payload);
        }

        return ApiResponse::success(
            DB::table('odoo_settings')->first(),
            'Pengaturan koneksi Odoo berhasil disimpan'
        );
    }

    // ════════════════════════════════════════════
    // PUT -- update mapping 1 company (group_companies.odoo_company_id)
    // ════════════════════════════════════════════
    public function updateCompanyMapping(OdooCompanyMappingValidationRequest $request, $groupId)
    {
        $user = auth()->user();
        if (!$this->ensureAdmin($user)) {
            return ApiResponse::error('Anda tidak memiliki akses ke pengaturan ini', 403);
        }

        $validated = $request->validated();

        $company = DB::table('group_companies')->where('id_group', $groupId)->first();
        if (!$company) {
            return ApiResponse::error('Company tidak ditemukan', 404);
        }

        DB::table('group_companies')
            ->where('id_group', $groupId)
            ->update([
                'odoo_company_id' => $validated['odoo_company_id'] ?? null,
                'updated_at'      => now(),
            ]);

        return ApiResponse::success(
            DB::table('group_companies')
                ->select('id_group', 'name_group', 'odoo_company_id', 'is_active')
                ->where('id_group', $groupId)
                ->first(),
            'Mapping company berhasil disimpan'
        );
    }

    // ════════════════════════════════════════════
    // POST -- test koneksi Odoo PAKAI NILAI DI FORM (belum tentu tersimpan)
    // ════════════════════════════════════════════
    // Sengaja TIDAK lewat $this->odooService (itu baca dari odoo_settings
    // yang SUDAH TERSIMPAN) -- di endpoint ini kita test kredensial yang
    // lagi diketik user di form, SEBELUM tombol Save Changes ditekan,
    // supaya kredensial salah ketahuan sebelum disimpan ke database.
    public function testConnection(OdooConnectionValidationRequest $request)
    {
        $user = auth()->user();
        if (!$this->ensureAdmin($user)) {
            return ApiResponse::error('Anda tidak memiliki akses ke pengaturan ini', 403);
        }

        $validated = $request->validated();

        try {
            $response = Http::timeout(10)->post(rtrim($validated['url'], '/') . '/jsonrpc', [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => [
                    'service' => 'common',
                    'method'  => 'authenticate',
                    'args'    => [$validated['db'], $validated['username'], $validated['api_key'], []],
                ],
                'id' => rand(1, 999999),
            ]);

            $data = $response->json();

            if (isset($data['error'])) {
                $message = $data['error']['data']['message']
                    ?? $data['error']['message']
                    ?? 'Unknown error dari Odoo';

                return ApiResponse::error('Koneksi ke Odoo gagal: ' . $message, 422);
            }

            $uid = $data['result'] ?? null;

            if (!$uid) {
                return ApiResponse::error('Koneksi ke Odoo gagal: username atau API key tidak valid', 422);
            }

            return ApiResponse::success(['uid' => $uid], 'Koneksi ke Odoo berhasil');
        } catch (\Throwable $e) {
            Log::error('Test koneksi Odoo gagal: ' . $e->getMessage());

            return ApiResponse::error('Koneksi ke Odoo gagal: ' . $e->getMessage(), 422);
        }
    }
}