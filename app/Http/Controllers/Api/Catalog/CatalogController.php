<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\CatalogValidationRequest;
use App\Http\Resources\CatalogResources;
use App\Models\Catalog;
use App\Models\ProductCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CatalogController extends Controller
{
    protected $Catalog;

    protected string $mediaFolder = 'product-catalogs/media';

    public function __construct(Catalog $Catalog)
    {
        $this->Catalog = $Catalog;
    }

    // list media milik satu product (?product_id=...), non-paginated,
    // urut sort_order -- dipakai buat nampilin daftar PDF/Video di
    // halaman detail product (Admin/Manager maupun Sales).
    public function index(Request $request)
    {
        $productId = $request->query('product_id');

        if (!$productId) {
            return ApiResponse::error('Validation failed', [
                'product_id' => ['product_id wajib diisi.']
            ], 422);
        }

        $catalogs = $this->Catalog->where('product_id', $productId)->sort()->get();

        return ApiResponse::success(CatalogResources::collection($catalogs), 'Success', 200);
    }

    public function show(string $id)
    {
        $catalog = $this->Catalog->find($id);
        if (!$catalog) {
            return ApiResponse::error('Catalog not found', [
                'id' => ['Data with that ID is not available']
            ], 404);
        }
        return ApiResponse::success(new CatalogResources($catalog), 'Success, take the detailed Catalog', 200);
    }

    public function store(CatalogValidationRequest $request)
    {
        $data = $request->validated();

        try {
            $product = ProductCatalog::find($data['product_id']);
            if (!$product) {
                return ApiResponse::error('Product not found', [
                    'product_id' => ['Data with that ID is not available']
                ], 404);
            }

            $payload = [
                'product_id'  => $data['product_id'],
                'media_type'  => $data['media_type'],
                'source_type' => $data['source_type'],
                'title'       => $data['title'] ?? null,
                'sort_order'  => $data['sort_order'] ?? 0,
            ];

            if ($data['source_type'] === 'upload' && $request->hasFile('file')) {
                $file = $request->file('file');
                $filename = uniqid('catalog_') . '.' . $file->getClientOriginalExtension();
                $file->storeAs($this->mediaFolder, $filename, 'public');
                // ── simpan folder/filename supaya konsisten dipakai balik
                // sebagai path di Storage::disk('public')->url()/->path() ──
                $payload['url'] = $this->mediaFolder . '/' . $filename;
            } else {
                $payload['url'] = $data['url'];
            }

            $catalog = $this->Catalog->create($payload);

            return ApiResponse::success(new CatalogResources($catalog), 'Success Create New Catalog', 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to create catalog (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while creating the catalog.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(CatalogValidationRequest $request, $id)
    {
        $data = $request->validated();

        $catalog = Catalog::find($id);

        if (!$catalog) {
            return ApiResponse::error(
                'Catalog with that ID was not found.',
                ['id' => ['Data not available.']],
                404
            );
        }

        try {
            $payload = [
                'product_id'  => $data['product_id'],
                'media_type'  => $data['media_type'],
                'source_type' => $data['source_type'],
                'title'       => $data['title'] ?? null,
                'sort_order'  => $data['sort_order'] ?? $catalog->sort_order,
            ];

            if ($data['source_type'] === 'upload' && $request->hasFile('file')) {
                // ── hapus file lama dulu (kalau memang sebelumnya upload) ──
                if ($catalog->source_type === 'upload' && $catalog->url && Storage::disk('public')->exists($catalog->url)) {
                    Storage::disk('public')->delete($catalog->url);
                }

                $file = $request->file('file');
                $filename = uniqid('catalog_') . '.' . $file->getClientOriginalExtension();
                $file->storeAs($this->mediaFolder, $filename, 'public');
                $payload['url'] = $this->mediaFolder . '/' . $filename;
            } elseif ($data['source_type'] !== 'upload') {
                // ── beralih dari upload ke link manual -- hapus file lama ──
                if ($catalog->source_type === 'upload' && $catalog->url && Storage::disk('public')->exists($catalog->url)) {
                    Storage::disk('public')->delete($catalog->url);
                }
                $payload['url'] = $data['url'] ?? $catalog->url;
            } else {
                // ── tetap upload, tidak ganti file baru -- pertahankan url lama ──
                $payload['url'] = $catalog->url;
            }

            $catalog->update($payload);

            return ApiResponse::success(new CatalogResources($catalog), 'Success Update Catalog', 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to update catalog (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update catalog', [
                'exception' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $catalog = $this->Catalog->find($id);
            if (!$catalog) {
                return ApiResponse::error('Catalog with that ID was not found.', [
                    'id' => ['Data not available.']
                ], 404);
            }

            if ($catalog->source_type === 'upload' && $catalog->url && Storage::disk('public')->exists($catalog->url)) {
                Storage::disk('public')->delete($catalog->url);
            }

            $catalog->delete();
            return ApiResponse::success(new CatalogResources($catalog), 'Success Delete Catalog', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete catalog', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}