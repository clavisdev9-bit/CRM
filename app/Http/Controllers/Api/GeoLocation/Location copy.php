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
 
        // Cache key dari hash alamatnya (bukan alamat mentah) -- jaga-jaga
        // ada karakter aneh/panjang yang bisa bermasalah kalau dipakai
        // langsung sebagai bagian cache key.
        $cacheKey = 'geo_search_' . md5(strtolower($address));
 
        $data = Cache::remember($cacheKey, now()->addDays(7), function () use ($address) {
 
            // 👉 AUTO: local = verify false, production = true (SAMA PERSIS
            // kayak reverse() di atas)
            $verifySSL = app()->environment('production');
 
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
 
            if ($response->failed()) {
                return null;
            }
 
            return $response->json();
        });
 
        if (!$data || empty($data)) {
            return response()->json([
                'message' => 'Location not found for this address'
            ], 404);
        }
 
        $result = $data[0];
 
        return response()->json([
            'lat' => (float) $result['lat'],
            'lon' => (float) $result['lon'],
            'display_name' => $result['display_name'] ?? null,
        ]);
    }
}
