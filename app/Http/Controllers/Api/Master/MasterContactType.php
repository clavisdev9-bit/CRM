<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\ContactTypeValidationIndex;
use App\Http\Requests\ContactTypeValidationRequest;
use App\Http\Resources\ContactTypeResources;
use App\Http\Resources\ContactTypeResourcesCollection;
use App\Models\ContactType;

class MasterContactType extends Controller
{
    protected $ContactType;

    public function __construct(ContactType $ContactType)
    {
        $this->ContactType = $ContactType;
    }

    // list
    public function index(ContactTypeValidationIndex $request)
    {
        $validated = $request->validated();
        $search = $validated['search'] ?? null;
        $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $onlyDeleted = $validated['only_deleted'] ?? false;

        // ── withCount('contacts') supaya frontend bisa nampilin berapa
        // banyak Contact yang pakai jenis ini, dan nge-warn user sebelum
        // hapus jenis yang masih dipakai. ──
        $query = $this->ContactType
            ->withCount('contacts')
            ->onlyDeleted($onlyDeleted)
            ->search($search)
            ->sort($sortBy, $sortDir);

        $results = $query->paginate($perPage);
        $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
        return ApiResponse::paginate(new ContactTypeResourcesCollection($results), $message);
    }

    // show detail data
    public function show(string $id)
    {
        $contactType = $this->ContactType->withCount('contacts')->find($id);
        if (!$contactType) {
            return ApiResponse::error('Contact type not found', [
                'id' => ['Data with that ID is not available']
            ], 404);
        }
        return ApiResponse::success(new ContactTypeResources($contactType), 'Success, take the detailed Contact Type', 200);
    }

    // Add data
    public function store(ContactTypeValidationRequest $request)
    {
        $data = $request->validated();

        try {

            $errors = ContactType::isDuplicate($data);
            if (!empty($errors)) {
                return ApiResponse::error('Validation failed', $errors, 400);
            }

            $contactType = $this->ContactType->create([
                'name'        => $data['name'],
                'description' => $data['description'],
                'is_active'   => $data['is_active'] ?? true,
                // ── is_system & system_source_type SENGAJA tidak diambil
                // dari $data (ContactTypeValidationRequest memang tidak
                // punya rule untuk itu) -- selalu false/null di sini,
                // supaya jenis reserved cuma bisa terbentuk lewat seed
                // migration, tidak bisa lewat form Add/Edit biasa. ──
                'is_system'           => false,
                'system_source_type'  => null,
            ]);

            return ApiResponse::success(new ContactTypeResources($contactType), 'Success Create New Contact Type', 201);

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to create contact type (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while creating the contact type.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(ContactTypeValidationRequest $request, $id)
    {
        $data = $request->validated();

        $contactType = ContactType::find($id);

        if (!$contactType) {
            return ApiResponse::error(
                'Contact type with that ID was not found.',
                ['id' => ['Data not available.']],
                404
            );
        }

        // ── Jenis reserved (is_system) tidak boleh diubah lewat form
        // biasa -- nama & mapping system_source_type-nya harus tetap
        // konsisten supaya proses link (ContactController::storeLink())
        // tidak salah pasang jenis. ──
        if ($contactType->is_system) {
            return ApiResponse::error(
                'Jenis contact "' . $contactType->name . '" adalah jenis reserved dan tidak bisa diubah.',
                ['is_system' => ['Reserved contact type cannot be edited.']],
                403
            );
        }

        try {
            $errors = ContactType::isDuplicate($data, $id);
            if (!empty($errors)) {
                return ApiResponse::error('Validation failed', $errors, 400);
            }

            $contactType->update([
                'name'        => $data['name'],
                'description' => $data['description'],
                'is_active'   => $data['is_active'] ?? $contactType->is_active,
            ]);

            return ApiResponse::success(new ContactTypeResources($contactType), 'Success Update Contact Type', 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to update contact type (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update contact type', [
                'exception' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $contactType = $this->ContactType->find($id);
            if (!$contactType) {
                return ApiResponse::error('Contact type with that ID was not found.', [
                    'id' => ['Data not available.']
                ], 404);
            }

            if ($contactType->is_system) {
                return ApiResponse::error(
                    'Jenis contact "' . $contactType->name . '" adalah jenis reserved dan tidak bisa dihapus.',
                    ['is_system' => ['Reserved contact type cannot be deleted.']],
                    403
                );
            }

            // ── Cek masih dipakai Contact aktif atau tidak -- pesan error
            // ramah duluan, daripada nabrak FK restrictOnDelete di DB. ──
            $stillUsed = $contactType->contacts()->exists();
            if ($stillUsed) {
                return ApiResponse::error(
                    'Jenis contact ini masih dipakai oleh data Contact dan tidak bisa dihapus.',
                    ['id' => ['Contact type is still in use.']],
                    409
                );
            }

            $contactType->delete();
            return ApiResponse::success(new ContactTypeResources($contactType), 'Success Delete Contact Type', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete contact type', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}