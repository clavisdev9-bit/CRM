<?php

namespace App\Http\Controllers\Api\GeoLocation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class Location extends Controller
{
    public function reverse(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        $lat = round($request->lat, 6);
        $lon = round($request->lon, 6);

        // 🔑 Cache key
        $cacheKey = "geo_{$lat}_{$lon}";

        $data = Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lon) {

            // 👉 AUTO: local = verify false, production = true
            $verifySSL = app()->environment('production');

            $response = Http::withOptions([
                'verify' => $verifySSL
            ])->withHeaders([
                'User-Agent' => 'CRM-Clavis-Attendance/1.0 (admin@clavis.com)'
            ])->timeout(10)->get(
                'https://nominatim.openstreetmap.org/reverse',
                [
                    'format' => 'json',
                    'lat' => $lat,
                    'lon' => $lon,
                ]
            );

            if ($response->failed()) {
                return null;
            }

            return $response->json();
        });

        if (!$data) {
            return response()->json([
                'message' => 'Reverse geocoding failed'
            ], 500);
        }

        return response()->json($data);
    }

    /**
     * FORWARD geocoding -- kebalikan dari reverse() di atas: dari ALAMAT
     * TEKS ke koordinat (lat/lon), buat kebutuhan Customer Master (auto-fill
     * Latitude/Longitude begitu user selesai isi/paste field Address).
     *
     * Sengaja dibikin method BARU (bukan ubah reverse()) dan pakai Nominatim
     * juga -- SAMA PERSIS pola/konvensinya kayak reverse() (Http::withOptions
     * verify-SSL otomatis berdasarkan environment, header User-Agent yang
     * sama, cache 7 hari) -- cuma beda endpoint Nominatim-nya (/search,
     * bukan /reverse) dan beda struktur field yang dibaca dari response-nya.
     *
     * Nominatim /search balikin ARRAY of results (beda sama /reverse yang
     * balikin 1 object) -- kita ambil hasil pertama aja (limit=1).
     *
     * countrycodes=id sengaja di-hardcode buat bias hasil pencarian ke
     * wilayah Indonesia (mayoritas alamat customer kemungkinan besar
     * Indonesia) -- kalau nanti ternyata butuh support alamat luar negeri
     * juga, tinggal dibikin jadi query param opsional.
     */
    public function search(Request $request)
    {
        $request->validate([
            'address' => 'required|string|min:3|max:255',
        ]);

        $address = trim($request->address);

        // ── TANGGA FALLBACK, dari paling spesifik ke paling umum ──
        // Alamat Indonesia yang detail (RT/RW, nomor rumah) SERING belum
        // ke-cover data-nya di OpenStreetMap/Nominatim, walau nama jalan +
        // kelurahan/kecamatan/kota-nya sendiri sebenernya ADA. Daripada
        // langsung nyerah begitu versi paling detail gagal, kita coba
        // beberapa versi yang makin umum (buang segmen paling kiri/spesifik
        // satu-satu, TETAP pertahankan hierarki administratif di kanannya),
        // berhenti begitu salah satu ketemu.
        //
        // Level paling umum ("kota + provinsi") emang cuma ngasih titik
        // tengah kota -- BUKAN lokasi presisi customer -- tapi masih lebih
        // baik daripada kosong total, apalagi field Latitude/Longitude di
        // form tetap bisa digeser manual sama sales/manager kalau memang
        // meleset. Level yang dipakai dikembalikan di 'precision_level'
        // biar frontend bisa kasih warning kalau levelnya kasar.
        $candidates = $this->buildAddressFallbackLadder($address);

        $data          = null;
        $debugAttempts = [];
        $precisionLevel = null;
        $matchedQuery   = null;

        foreach ($candidates as $level => $candidateAddress) {
            $data = $this->queryNominatimSearch($candidateAddress);

            $debugAttempts[] = [
                'level'   => $level,
                'query'   => $candidateAddress,
                'debug'   => $this->lastDebug,
                'success' => !empty($data),
            ];

            if (!empty($data)) {
                $precisionLevel = $level;
                $matchedQuery   = $candidateAddress;
                break;
            }
        }

        if (!$data || empty($data)) {
            $payload = [
                'message' => 'Location not found for this address',
            ];

            // ── DEBUG (CUMA MUNCUL DI NON-PRODUCTION) ──
            // Nunjukin status HTTP & body asli dari Nominatim (atau pesan
            // exception kalau request-nya sama sekali ga nyampe/connection
            // error) buat SETIAP level di tangga fallback -- biar ketauan
            // level mana yang gagal & kenapa: beneran "alamat ga ketemu"
            // (status 200, hasil array kosong) vs diblokir/rate-limit
            // (403/429) vs server lokal ga bisa konek ke internet sama
            // sekali (connection_error).
            if (!app()->environment('production')) {
                $payload['debug'] = $debugAttempts;
            }

            return response()->json($payload, 404);
        }

        $result = $data[0];

        return response()->json([
            'lat' => (float) $result['lat'],
            'lon' => (float) $result['lon'],
            'display_name' => $result['display_name'] ?? null,

            // level 0 = alamat asli persis ketemu (paling presisi).
            // Makin besar levelnya, makin umum/kasar titiknya (bisa jadi
            // cuma level kecamatan atau kota) -- frontend boleh pakai ini
            // buat nampilin warning "koordinat perkiraan area, mohon cek
            // ulang pin-nya" kalau precision_level > 1.
            'precision_level' => $precisionLevel,
            'matched_query'   => $matchedQuery,
        ]);
    }

    /**
     * Bikin daftar versi alamat dari yang PALING SPESIFIK ke yang PALING
     * UMUM, buat dicoba satu-satu ke Nominatim (search() berhenti di versi
     * pertama yang ketemu). Urutannya:
     *
     *   0. Alamat asli apa adanya
     *   1. Alamat asli dikurangi RT/RW & kode pos (biasanya bikin gagal
     *      match padahal jalannya sendiri ada di OSM)
     *   2..N. Alamat dipecah per-koma, lalu segmen paling kiri (paling
     *      spesifik -- biasanya nama jalan+nomor) dibuang SATU PER SATU,
     *      tetap pertahankan sisa hierarki administratif di kanannya
     *      (kelurahan → kecamatan → kota → provinsi), sampai minimal
     *      tersisa 2 segmen (kira-kira setara kota + provinsi) -- di
     *      bawah itu titiknya udah kelewat kasar buat berguna.
     */
    private function buildAddressFallbackLadder(string $address): array
    {
        $candidates = [$address];

        $cleaned = $this->cleanAddressForGeocoding($address);
        if ($cleaned && $cleaned !== $address) {
            $candidates[] = $cleaned;
        }

        $base = $cleaned ?? $address;

        $segments = array_values(array_filter(
            array_map('trim', explode(',', $base)),
            fn ($s) => $s !== ''
        ));

        // Buang dari kiri satu-satu, minimal sisain 2 segmen terakhir
        // (kira-kira "kota" + "provinsi").
        while (count($segments) > 2) {
            array_shift($segments);
            $candidates[] = implode(', ', $segments);
        }

        // Dedup (jaga-jaga ada versi yang kebetulan identik) sambil
        // pertahankan urutan dari paling spesifik ke paling umum.
        return array_values(array_unique($candidates));
    }

    /**
     * Bersihin alamat dari bagian-bagian yang SERING bikin Nominatim gagal
     * nemuin hasil buat alamat Indonesia yang detail: Google Plus Code
     * (contoh "6CC3+W2X" -- muncul kalau user copy alamat dari Google Maps
     * buat lokasi yang belum punya nama jalan resmi, misal pabrik/lokasi
     * di area perkebunan), RT/RW, dan kode pos (5 digit di akhir). Nama
     * jalan, nomor rumah, kelurahan, kecamatan, kota, dan provinsi TETAP
     * dipertahankan -- itu semua yang justru dipakai Nominatim buat
     * matching.
     *
     * PENTING: Plus Code itu sistem grid GOOGLE sendiri, Nominatim/OSM
     * SAMA SEKALI ga ngerti format ini -- kalau dibiarkan, query pasti
     * gagal di percobaan manapun dan sistem jatuh ke fallback yang jauh
     * lebih generik (kecamatan/kabupaten), yang buat lokasi terpencil
     * (pabrik, area perkebunan, dll) bisa meleset BELASAN KM dari titik
     * aslinya. Membuang Plus Code-nya duluan jauh lebih efektif daripada
     * mengandalkan fallback level bawah.
     */
    private function cleanAddressForGeocoding(string $address): ?string
    {
        $cleaned = $address;

        // Buang Google Plus Code di awal alamat, misal "6CC3+W2X, " atau
        // "7QM3+2P8 " -- pola umumnya: 4-8 karakter alfanumerik, "+",
        // 2-4 karakter alfanumerik, biasanya nempel di awal string persis
        // hasil copy-paste dari Google Maps.
        $cleaned = preg_replace('/^[A-Z0-9]{4,8}\+[A-Z0-9]{2,4}\s*,?\s*/i', '', $cleaned);

        // Buang "RT.003/RW.001", "RT 003 / RW 001", "RT:003 RW:001", dst.
        $cleaned = preg_replace('/RT\.?\s*\d+\s*\/?\s*RW\.?\s*\d+/i', '', $cleaned);

        // Buang kode pos 5 digit di akhir alamat
        $cleaned = preg_replace('/\b\d{5}\b\s*$/', '', $cleaned);

        // Rapikan koma/spasi ganda hasil dari penghapusan di atas
        $cleaned = preg_replace('/,\s*,/', ',', $cleaned);
        $cleaned = preg_replace('/\s{2,}/', ' ', $cleaned);
        $cleaned = trim($cleaned, " ,\t\n\r");

        return $cleaned !== '' ? $cleaned : null;
    }

    /**
     * Nyimpen info debug dari request TERAKHIR ke Nominatim (status HTTP,
     * body mentah, atau pesan exception kalau request-nya gagal total/ga
     * nyampe). Cuma keisi kalau request-nya BENERAN dijalanin (cache miss)
     * -- kalau hasilnya diambil dari cache, tetap null (ga masalah, karena
     * cache cuma nyimpen hasil yang SUKSES -- lihat catatan di
     * queryNominatimSearch()).
     */
    private ?array $lastDebug = null;

    /**
     * Query ke Nominatim /search buat 1 versi alamat. Dipisah jadi method
     * sendiri karena search() sekarang bisa manggil ini 2x (alamat asli +
     * fallback alamat yang sudah dibersihkan).
     *
     * ── SOAL CACHING (PENTING) ──
     * SENGAJA TIDAK pakai Cache::remember() polos kayak reverse() di atas.
     * Kalau pakai remember(), hasil GAGAL/KOSONG (array []) ikut ke-cache
     * 7 hari juga -- padahal [] bukan null, jadi Cache::remember() nganggep
     * itu "sudah ada di cache" dan CACHE::REMEMBER TIDAK PERNAH manggil
     * Nominatim lagi buat alamat itu selama 7 hari, walau alamatnya
     * sebenernya valid (misal gagal karena Nominatim lagi sibuk/timeout
     * doang, sekali doang). Makanya di sini cache dibaca/ditulis manual:
     * cuma hasil yang BENERAN SUKSES & ada isinya yang disimpan 7 hari;
     * hasil gagal/kosong TIDAK di-cache sama sekali, jadi selalu dicoba
     * lagi fresh di request berikutnya.
     *
     * Dibungkus try/catch karena Http::get() bisa THROW
     * Illuminate\Http\Client\ConnectionException kalau request-nya sama
     * sekali ga nyampe ke server Nominatim (misal server lokal ga punya
     * akses internet keluar, DNS gagal, dll) -- ini beda kasus sama
     * response gagal (403/429/5xx) yang ditangani via $response->failed().
     * Tanpa try/catch ini, kasus connection error bakal keluar sebagai
     * 500 Internal Server Error yang generic, bukan pesan yang jelas.
     */
    private function queryNominatimSearch(string $address): ?array
    {
        $cacheKey = 'geo_search_' . md5(strtolower($address));

        // ── Cek cache manual dulu ──
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // 👉 AUTO: local = verify false, production = true (SAMA PERSIS
        // kayak reverse() di atas)
        $verifySSL = app()->environment('production');

        try {
            $response = Http::withOptions([
                'verify' => $verifySSL
            ])->withHeaders([
                'User-Agent' => 'CRM-Clavis-Attendance/1.0 (admin@clavis.com)'
            ])->timeout(10)->get(
                'https://nominatim.openstreetmap.org/search',
                [
                    'format' => 'json',
                    'q' => $address,
                    'limit' => 1,
                    'countrycodes' => 'id',
                ]
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->lastDebug = [
                'address' => $address,
                'connection_error' => $e->getMessage(),
            ];

            return null; // TIDAK di-cache -- boleh dicoba lagi kapan saja
        }

        if ($response->failed()) {
            $this->lastDebug = [
                'address' => $address,
                'status'  => $response->status(),
                'body'    => substr($response->body(), 0, 500),
            ];

            return null; // TIDAK di-cache
        }

        $json = $response->json();

        if (empty($json)) {
            $this->lastDebug = [
                'address' => $address,
                'status'  => $response->status(),
                'note'    => 'Request sukses (200) tapi hasilnya array kosong -- alamat ini beneran ga ketemu di Nominatim.',
            ];

            return null; // TIDAK di-cache
        }

        // Hasil BENERAN ketemu -- baru ini yang disimpan 7 hari
        Cache::put($cacheKey, $json, now()->addDays(7));

        return $json;
    }
}