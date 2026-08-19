<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CustomersProductPopulationModel;
use App\Http\Resources\ProductPopulationExternalResource;

/**
 * ======================================================
 * PopulationProductCustumers — versi EXTERNAL dari fitur
 * Customer Product Population, buat dikonsumsi sistem
 * internal lain di luar CRM ini (ERP, gudang, dst).
 * ------------------------------------------------------
 * READ-ONLY, cuma 1 endpoint: index() — semua data langsung
 * dibalikin lewat situ (nggak ada endpoint detail/show
 * terpisah, karena konsumernya n8n cukup loop dari 1
 * response list ini). Nggak ada store/update/destroy di sini
 * juga, itu tetap lewat controller internal
 * (App\Http\Controllers\Api\Users\Sales\Costumers\CustomersProductPopulation).
 *
 * Data yang dibalikin SENGAJA lebih ringkas dibanding versi
 * internal: cuma info produk + identitas customer, TANPA
 * detail internal kayak pic_list/user_id (siapa sales yang
 * pegang) — karena itu urusan internal sales/assignment,
 * bukan sesuatu yang perlu diketahui sistem eksternal.
 *
 * ⚠️ BELUM ADA AUTH/API-KEY di controller ini — ini
 * disepakati sementara buat kebutuhan testing/integrasi
 * awal. SEBELUM endpoint ini beneran dipakai sistem lain,
 * pasang middleware auth (API key per konsumer, atau
 * Sanctum token khusus service-to-service) di route-nya,
 * supaya data customer/produk nggak kebuka bebas ke luar.
 * ======================================================
 */
class PopulationProductCustumers extends Controller
{
    protected $CustomersProductPopulationModel;

    public function __construct(CustomersProductPopulationModel $CustomersProductPopulationModel)
    {
        $this->CustomersProductPopulationModel = $CustomersProductPopulationModel;
    }

    /**
     * ======================================================
     * INDEX — LIST PRODUCT POPULATION (READ-ONLY, external).
     * ------------------------------------------------------
     * SENGAJA TANPA PAGINATION — konsumernya n8n, jadi
     * response-nya langsung array data lengkap (bukan
     * envelope {data, pagination} kayak versi internal),
     * biar gampang di-loop langsung di workflow n8n tanpa
     * perlu logic "fetch next page" segala.
     *
     * Query param yang didukung: search, sort_by, sort_dir.
     *
     * NOTE: karena nggak ada limit/pagination, endpoint ini
     * bakal narik SEMUA baris yang cocok filter dalam 1x
     * response. Kalau volume data product_populations bakal
     * jadi sangat besar (puluhan-ratusan ribu baris), ini
     * bisa berat/lambat — kabarin aku kalau nanti perlu
     * dikasih semacam batas atas (misal max 5.000 baris per
     * panggilan) biar tetap aman.
     * ======================================================
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');

            $allowedSort = ['pp.created_at', 'pp.tag_no', 'pp.pump_serial_no', 'pp.qty', 'customer_name'];

            $sortBy = $request->query('sort_by', 'pp.created_at');
            $sortBy = in_array($sortBy, $allowedSort) ? $sortBy : 'pp.created_at';

            $sortDir = strtolower($request->query('sort_dir', 'desc'));
            $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc';

            $query = $this->baseSelect()
                ->leftJoin('customers as c', 'c.id', '=', 'pp.customer_id');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('pp.pump_serial_no', 'ILIKE', "%{$search}%")
                        ->orWhere('pp.tag_no', 'ILIKE', "%{$search}%")
                        ->orWhere('pp.product_model', 'ILIKE', "%{$search}%")
                        ->orWhere('pp.product_category', 'ILIKE', "%{$search}%")
                        ->orWhere('c.company_name', 'ILIKE', "%{$search}%")
                        ->orWhere('c.customer_code', 'ILIKE', "%{$search}%");
                });
            }

            $rows = $query->orderBy($sortBy, $sortDir)->get();

            return ApiResponse::success(
                ProductPopulationExternalResource::collection($rows),
                $rows->isEmpty() ? 'Data product population not found' : 'Success'
            );

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to fetch product population (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while fetching product population.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * HELPERS
     * ======================================================
     */

    /**
     * Select dasar khusus external — cuma kolom produk +
     * identitas customer. TIDAK menyertakan pp.user_id /
     * pic_list (siapa sales yang pegang) karena itu detail
     * internal yang nggak relevan buat konsumer eksternal.
     */
    private function baseSelect()
    {
        return DB::table('product_populations as pp')
            ->select([
                'pp.id',
                'pp.customer_id',
                'pp.pump_serial_no',
                'pp.product_category',
                'pp.product_display',
                'pp.product_model',
                'pp.tag_no',
                'pp.qty',
                'pp.seal_plan',
                'pp.mechanical_seal_drawing_no',
                'pp.created_at',
                'pp.updated_at',

                'c.company_name as customer_name',
                'c.customer_code as customer_code',
            ]);
    }
}