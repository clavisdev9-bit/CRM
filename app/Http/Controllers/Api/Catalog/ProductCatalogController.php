<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\ProductCatalogValidationIndex;
use App\Http\Requests\ProductCatalogValidationRequest;
use App\Http\Resources\ProductCatalogResources;
use App\Http\Resources\ProductCatalogResourcesCollection;
use App\Models\ProductCatalog;
use Illuminate\Support\Facades\Storage;

class ProductCatalogController extends Controller
{
    protected $Product;

    protected string $thumbnailFolder = 'product-catalogs/thumbnails';
    protected string $mediaFolder = 'product-catalogs/media';

    public function __construct(ProductCatalog $Product)
    {
        $this->Product = $Product;
    }

    // list
    public function index(ProductCatalogValidationIndex $request)
    {
        $validated = $request->validated();
        $search = $validated['search'] ?? null;
        $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $categoryId = $validated['category_id'] ?? null;

        $query = $this->Product
            ->withCount('catalogs')
            ->with('category:id,name')
            ->byCategory($categoryId)
            ->search($search)
            ->sort($sortBy, $sortDir);

        $results = $query->paginate($perPage);
        $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
        return ApiResponse::paginate(new ProductCatalogResourcesCollection($results), $message);
    }

    // show detail data (dipakai juga oleh sisi Sales -- eager load catalogs)
    public function show(string $id)
    {
        $product = $this->Product->with(['category:id,name', 'catalogs'])->find($id);
        if (!$product) {
            return ApiResponse::error('Product not found', [
                'id' => ['Data with that ID is not available']
            ], 404);
        }
        return ApiResponse::success(new ProductCatalogResources($product), 'Success, take the detailed Product', 200);
    }

    // Add data
    public function store(ProductCatalogValidationRequest $request)
    {
        $data = $request->validated();

        try {
            if (ProductCatalog::isDuplicate($data)) {
                return ApiResponse::error('Validation failed', [
                    'sku' => ['SKU sudah dipakai oleh produk lain.']
                ], 400);
            }

            $payload = [
                'category_id' => $data['category_id'],
                'sku'         => $data['sku'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'price'       => $data['price'] ?? 0,
                'stock'       => $data['stock'] ?? 0,
            ];

            if ($request->hasFile('thumbnail')) {
                $file = $request->file('thumbnail');
                $filename = uniqid('product_') . '.' . $file->getClientOriginalExtension();
                $file->storeAs($this->thumbnailFolder, $filename, 'public');
                $payload['thumbnail_url'] = $filename;
            }

            $product = $this->Product->create($payload);

            return ApiResponse::success(new ProductCatalogResources($product), 'Success Create New Product', 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to create product (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while creating the product.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(ProductCatalogValidationRequest $request, $id)
    {
        $data = $request->validated();

        $product = ProductCatalog::find($id);

        if (!$product) {
            return ApiResponse::error(
                'Product with that ID was not found.',
                ['id' => ['Data not available.']],
                404
            );
        }

        try {
            if (ProductCatalog::isDuplicate($data, $id)) {
                return ApiResponse::error('Validation failed', [
                    'sku' => ['SKU sudah dipakai oleh produk lain.']
                ], 400);
            }

            $payload = [
                'category_id' => $data['category_id'],
                'sku'         => $data['sku'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'price'       => $data['price'] ?? $product->price,
                'stock'       => $data['stock'] ?? $product->stock,
            ];

            if ($request->hasFile('thumbnail')) {
                // ── hapus thumbnail lama dulu sebelum simpan yang baru ──
                if ($product->thumbnail_url && Storage::disk('public')->exists($this->thumbnailFolder . '/' . $product->thumbnail_url)) {
                    Storage::disk('public')->delete($this->thumbnailFolder . '/' . $product->thumbnail_url);
                }

                $file = $request->file('thumbnail');
                $filename = uniqid('product_') . '.' . $file->getClientOriginalExtension();
                $file->storeAs($this->thumbnailFolder, $filename, 'public');
                $payload['thumbnail_url'] = $filename;
            }

            $product->update($payload);

            return ApiResponse::success(new ProductCatalogResources($product), 'Success Update Product', 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to update product (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update product', [
                'exception' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            // ── PENTING: pakai with('catalogs'), BUKAN withCount('catalogs')
            // -- di sini kita perlu isi relasinya (untuk loop hapus file),
            // bukan cuma jumlahnya. ──
            $product = $this->Product->with('catalogs')->find($id);
            if (!$product) {
                return ApiResponse::error('Product with that ID was not found.', [
                    'id' => ['Data not available.']
                ], 404);
            }

            // ── Hapus semua file media (upload) milik product ini dulu ──
            foreach ($product->catalogs as $catalog) {
                if ($catalog->source_type === 'upload' && Storage::disk('public')->exists($catalog->url)) {
                    Storage::disk('public')->delete($catalog->url);
                }
            }

            // ── Hapus thumbnail ──
            if ($product->thumbnail_url && Storage::disk('public')->exists($this->thumbnailFolder . '/' . $product->thumbnail_url)) {
                Storage::disk('public')->delete($this->thumbnailFolder . '/' . $product->thumbnail_url);
            }

            $product->delete();
            return ApiResponse::success(new ProductCatalogResources($product), 'Success Delete Product', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete product', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}