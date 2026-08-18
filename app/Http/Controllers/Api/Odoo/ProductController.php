<?php

namespace App\Http\Controllers\Api\Odoo;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\ProductValidationIndex;
use App\Http\Resources\ProductResourceCollection;
use App\Models\OdooProduct;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * GET /products
     * List product hasil sync dari Odoo -- search/sort/pagination, sama
     * seperti daftar customer/aktivitas yang sudah ada. Bisa diakses Sales
     * maupun Manager (data product bukan data yang di-scope per sales).
     */
    public function index(ProductValidationIndex $request)
    {
        try {
            $validated = $request->validated();
 
            $search  = $validated['search'] ?? null;
            $perPage = $validated['per_page'] ?? 10;
            $sortBy  = $validated['sort_by'] ?? 'name';
            $sortDir = $validated['sort_dir'] ?? 'asc';
 
            $query = OdooProduct::query()->where('active', true);
 
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('default_code', 'ILIKE', "%{$search}%")
                        ->orWhere('barcode', 'ILIKE', "%{$search}%")
                        ->orWhere('categ_name', 'ILIKE', "%{$search}%");
                });
            }
 
            $query->orderBy($sortBy, $sortDir);
 
            $results = $query->paginate($perPage);
 
            return ApiResponse::paginate(
                ProductResourceCollection::make($results),
                $results->isEmpty() ? 'Data product not found' : 'Success'
            );
 
        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to load products', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
 
    /**
     * POST /products/sync
     * Manager-only. Trigger sync product dari Odoo secara manual (tombol
     * "Sync Sekarang" di frontend).
     */
    public function sync()
    {
        if (! $this->isManager()) {
            return ApiResponse::error('Unauthorized. Sync product khusus Manager.', [], 403);
        }
 
        try {
            $exitCode = Artisan::call('odoo:sync-products');
            $output   = trim(Artisan::output());
 
            if ($exitCode !== 0) {
                return ApiResponse::error('Sync product gagal, cek log server.', [
                    'output' => config('app.debug') ? $output : null,
                ], 500);
            }
 
            return ApiResponse::success([
                'total_products' => OdooProduct::count(),
                'last_synced_at' => OdooProduct::max('updated_at'),
                'output'         => config('app.debug') ? $output : null,
            ], 'Sync product berhasil.');
 
        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to sync products', [
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
 
    private function isManager(): bool
    {
        $user = auth()->user();
 
        if (! $user || empty($user->role_id)) {
            return false;
        }
 
        return DB::table('ms_role')
            ->where('id_role', $user->role_id)
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(role) = ?', ['manager'])
            ->exists();
    }
}