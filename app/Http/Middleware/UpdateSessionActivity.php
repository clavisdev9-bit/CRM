<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpdateSessionActivity
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = JWTAuth::getToken();
            if ($token) {
                $jti = JWTAuth::setToken($token)->getPayload()->get('jti');
                DB::table('tb_user_sessions')->where('jti', $jti)->update(['last_used_at' => now()]);
            }
        } catch (\Exception $e) {
            // token tidak valid / expired, biarkan request tetap lanjut
        }

        return $next($request);
    }
}