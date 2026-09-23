<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\CategoryProductCatalogValidationIndex;
use App\Http\Requests\CategoryProductCatalogValidationRequest;
use App\Http\Resources\CategoryProductCatalogResources;
use App\Http\Resources\CategoryProductCatalogResourcesCollection;
use App\Models\CategoryProductCatalog;
use Illuminate\Support\Facades\DB;

class CategoryProductCatalogController extends Controller
{
    protected $Category;

    public function __construct(CategoryProductCatalog $Category)
    {
        $this->Category = $Category;
    }

    // list
    public function index(CategoryProductCatalogValidationIndex $request)
    {
        $validated = $request->validated();
        $search = $validated['search'] ?? null;
        $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $parentId = $validated['parent_id'] ?? null;

        $query = $this->Category
            ->withCount(['products', 'children'])
            ->with('parent:id,name')
            ->when($parentId !== null, fn ($q) => $q->where('parent_id', $parentId))
            ->search($search)
            ->sort($sortBy, $sortDir);

        $results = $query->paginate($perPage);
        $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
        return ApiResponse::paginate(new CategoryProductCatalogResourcesCollection($results), $message);
    }

    // dropdown flat (tanpa pagination), dipakai di form Add/Edit Product
    public function selectCategories()
    {
        return response()->json(
            DB::table('categories_product_catalog')
                ->select('id', 'name', 'parent_id')
                ->orderBy('name', 'asc')
                ->get()
        );
    }

    // show detail data
    public function show(string $id)
    {
        $category = $this->Category->withCount(['products', 'children'])->with('parent:id,name')->find($id);
        if (!$category) {
            return ApiResponse::error('Category not found', [
                'id' => ['Data with that ID is not available']
            ], 404);
        }
        return ApiResponse::success(new CategoryProductCatalogResources($category), 'Success, take the detailed Category', 200);
    }

    // Add data
    public function store(CategoryProductCatalogValidationRequest $request)
    {
        $data = $request->validated();

        try {
            $slug = CategoryProductCatalog::generateUniqueSlug($data['name']);

            $category = $this->Category->create([
                'name'      => $data['name'],
                'slug'      => $slug,
                'parent_id' => $data['parent_id'] ?? null,
            ]);

            return ApiResponse::success(new CategoryProductCatalogResources($category), 'Success Create New Category', 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to create category (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while creating the category.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(CategoryProductCatalogValidationRequest $request, $id)
    {
        $data = $request->validated();

        $category = CategoryProductCatalog::find($id);

        if (!$category) {
            return ApiResponse::error(
                'Category with that ID was not found.',
                ['id' => ['Data not available.']],
                404
            );
        }

        try {
            // ── slug cuma di-generate ulang kalau nama berubah, supaya
            // slug lama (yang mungkin sudah dipakai di link eksternal)
            // tidak berubah-ubah tanpa alasan. ──
            $slug = $category->slug;
            if (strcasecmp($category->name, $data['name']) !== 0) {
                $slug = CategoryProductCatalog::generateUniqueSlug($data['name'], $category->id);
            }

            $category->update([
                'name'      => $data['name'],
                'slug'      => $slug,
                'parent_id' => $data['parent_id'] ?? null,
            ]);

            return ApiResponse::success(new CategoryProductCatalogResources($category), 'Success Update Category', 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to update category (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update category', [
                'exception' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $category = $this->Category->withCount(['products', 'children'])->find($id);
            if (!$category) {
                return ApiResponse::error('Category with that ID was not found.', [
                    'id' => ['Data not available.']
                ], 404);
            }

            if ($category->products_count > 0 || $category->children_count > 0) {
                return ApiResponse::error(
                    'Kategori ini masih memiliki produk atau sub-kategori dan tidak bisa dihapus.',
                    ['id' => ['Category is still in use.']],
                    409
                );
            }

            $category->delete();
            return ApiResponse::success(new CategoryProductCatalogResources($category), 'Success Delete Category', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete category', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}