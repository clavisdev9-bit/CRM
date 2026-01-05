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
}
