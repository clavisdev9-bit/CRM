<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid atau kadaluarsa.'], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Role tidak diizinkan.'], 403);
        }

        return $next($request);
    }
}