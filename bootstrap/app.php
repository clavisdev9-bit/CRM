<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\App;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Http\Middleware\FormatUnauthenticated;
use App\Http\Middleware\Authenticate;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;
use Tymon\JWTAuth\Http\Middleware\RefreshToken as JWTRefreshToken;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\UpdateSessionActivity;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
     //untuk pengelolaan api yang bersipat SPA
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            EnsureFrontendRequestsAreStateful::class,
            SubstituteBindings::class,
            'throttle:api',
            FormatUnauthenticated::class,
            UpdateSessionActivity::class,
            
        ]);
         // Middleware bawaan Laravel 12
        $middleware->statefulApi();

         
        //  Middleware alias untuk JWT
        $middleware->alias([
            'jwt.auth'    => JWTAuthenticate::class,
            'jwt.refresh' => JWTRefreshToken::class,

            //  Kalau kamu sudah punya middleware role
            'role' => RoleMiddleware::class,
        ]);
    })
    
    
//     ->withExceptions(function (Exceptions $exceptions) {
//         // Validation errors (422)
//         $exceptions->renderable(function (ValidationException $e, $request): JsonResponse {
//             return ApiResponse::error(
//                 'Validation failed',
//                 $e->errors(),
//                 $e->status
//             );
//         });

//       // 🔒 Unauthenticated (401)
//     $exceptions->renderable(function (AuthenticationException $e, $request) {
//         if ($request->expectsJson() || $request->is('api/*')) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Silakan login terlebih dahulu.'
//             ], 401);
//         }
//     });

//     // ⏰ Token expired
//     $exceptions->renderable(function (TokenExpiredException $e, $request) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Token sudah kadaluarsa, silakan login ulang.'
//         ], 401);
//     });

//     // ❌ Token invalid
//     $exceptions->renderable(function (TokenInvalidException $e, $request) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Token tidak valid.'
//         ], 401);
//     });

//     // 🚫 Token missing
//     $exceptions->renderable(function (JWTException $e, $request) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Token tidak ditemukan.'
//         ], 401);
//     });

//         // Not Found (404)
//         // $exceptions->renderable(function (NotFoundHttpException $e, $request): JsonResponse {
//         //     return ApiResponse::error(
//         //         'Resource tidak ditemukan.',
//         //         null,
//         //         404
//         //     );
//         // });

//        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
//     if ($request->expectsJson() || $request->is('api/*')) {
//         return ApiResponse::error(
//             'Resource tidak ditemukan.',
//             null,
//             404
//         );
//     }
// });



//         // Database connection error (PDO)
//         // $exceptions->render(function (\PDOException $e, $request): JsonResponse {
//         //     return ApiResponse::error(
//         //         'Tidak bisa terhubung ke database.',
//         //         null,
//         //         500
//         //     );
//         // });

//         $exceptions->render(function (\PDOException $e, $request): JsonResponse {
//             if (App::isProduction() && !App::isLocal()) {
//                 // Production, tampilkan pesan umum
//                 return ApiResponse::error(
//                     'Tidak bisa terhubung ke database.',
//                     null,
//                     500
//                 );
//             } else {
//                 // Development/local, tampilkan detail error untuk debugging
//                 return ApiResponse::error(
//                     $e->getMessage(),
//                     null,
//                     500
//                 );
//             }
//         });

//         // Query error (misalnya syntax SQL salah)
//         $exceptions->render(function (QueryException $e, $request): JsonResponse {
//             return ApiResponse::error(
//                 'Terjadi kesalahan saat menjalankan query.',
//                 null,
//                 500
//             );
//         });

//         // Reportable error (untuk log dan bisa kirim ke external kita, misalnya Sentry, ke gmail, ke telegram and other)
//         $exceptions->reportable(function (Throwable $e) {
//             // adalah hook untuk mencatat (melaporkan) semua error ke log atau layanan monitoring seperti:
//             // Laravel log file (storage/logs/laravel.log)
//             // Sentry, Bugsnag, Rollbar, atau layanan observabilitas lainnya
//             // Atau bahkan kamu bisa pakai buat kirim notifikasi ke email / Telegram / Slack

//             // Tujuan reportable di Laravel
//             // Memisahkan proses rendering (apa yang ditampilkan ke user) dengan reporting (apa yang perlu dicatat developer).
//             // Berguna untuk error yang tidak ditampilkan ke pengguna, tapi tetap perlu dicatat.

//             // 1. Kirim error ke storage/logs/laravel.log:
//             // logger()->error($e->getMessage());

//             // 2. Kirim ke Sentry:
//             // $exceptions->reportable(function (Throwable $e) {
//             //     if (app()->bound('sentry')) {
//             //         app('sentry')->captureException($e);
//             //     }
//             // });

//             // 3. Kirim ke Telegram / Email (manual):
//             // $exceptions->reportable(function (Throwable $e) {
//             //     Notification::route('mail', 'admin@example.com')
//             //         ->notify(new ErrorOccurredNotification($e));
//             // });
            
            


//         });
//     })

->withExceptions(function (Exceptions $exceptions) {

    // 422 Validation
    $exceptions->renderable(function (ValidationException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::error(
                'Validation failed',
                $e->errors(),
                422
            );
        }
    });

    // 401 Unauthenticated (belum login / token kosong)
    $exceptions->renderable(function (AuthenticationException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::error(
                'Silakan login terlebih dahulu.',
                null,
                401
            );
        }
    });

    // ⏰ Token expired
    $exceptions->renderable(function (TokenExpiredException $e, $request) {
        return ApiResponse::error(
            'Token sudah kadaluarsa, silakan login ulang.',
            null,
            401
        );
    });

    // ❌ Token invalid
    $exceptions->renderable(function (TokenInvalidException $e, $request) {
        return ApiResponse::error(
            'Token tidak valid.',
            null,
            401
        );
    });

    // 🚫 Token tidak ditemukan (JWTException sisa)
    $exceptions->renderable(function (JWTException $e, $request) {
        return ApiResponse::error(
            'Token tidak ditemukan.',
            null,
            401
        );
    });

    // 🔍 API 404
    $exceptions->renderable(function (NotFoundHttpException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::error(
                'Resource tidak ditemukan.',
                null,
                404
            );
        }
    });

    // 💥 Database error
    $exceptions->render(function (\PDOException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::error(
                App::isProduction() ? 'Tidak bisa terhubung ke database.' : $e->getMessage(),
                null,
                500
            );
        }
    });

    // 💥 Query error
    $exceptions->render(function (QueryException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::error(
                'Terjadi kesalahan saat menjalankan query.',
                null,
                500
            );
        }
    });

    // 📝 Report (logging)
    $exceptions->reportable(function (Throwable $e) {
        logger()->error($e);
    });
})


    ->create();