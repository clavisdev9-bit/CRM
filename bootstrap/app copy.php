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
use Illuminate\Console\Scheduling\Schedule;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )


    // ═══════════════════════════════════════════════════════════════════
    // SCHEDULER -- daftar command yang dijalankan otomatis (butuh cron
    // `* * * * * php artisan schedule:run` aktif di server).
    // ═══════════════════════════════════════════════════════════════════
    ->withSchedule(function (Schedule $schedule) {
        // Reminder email follow up: cek tiap jam, kirim email ke sales
        // (assigned_to, fallback created_by) + CC Manager/Admin begitu
        // follow_ups.follow_up_at tinggal <= 12 jam lagi & masih PENDING.
        // Detail logic-nya ada di App\Console\Commands\SendFollowUpReminders.
        $schedule->command('follow-up:send-reminders')->hourly();
        // $schedule->command('follow-up:send-reminders')->everyMinute();
    })


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