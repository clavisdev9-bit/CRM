<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class SignAuth extends Controller
{
  
       public function signIn(Request $request)
    {
        // 1️ Validasi input
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 2️ Cek user berdasarkan email
        $user = Users::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'The email you entered is not registered.',
            ], 404);
        }

        // 3️ Cek password manual
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The email / password you entered is incorrect.',
            ], 401);
        }

        // 4 Generate token
        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat token.',
            ], 500);
        }

        // 5️ Tentukan redirect berdasarkan role
       $redirect = match ($user->role_id) {
        1 => '/administrator-dashboard',
        2 => '/sales-home',
        3 => '/manager-home',
        default => '/home',
    };

        // 6️ Return JSON
         return response()->json([
        'success'      => true,
        'message'      => 'Login succesfully.',
        'user'         => [
            'id'       => $user->id_user,
            'fullname' => $user->fullname,
            'email'    => $user->email,
            'username' => $user->username,
            'role_id'  => $user->role_id,
        ],
        'access_token' => $token,
        'token_type'   => 'bearer',
        'expires_in'   => auth()->factory()->getTTL() * 60,
        'redirect_url' => $redirect,
    ]);
    }


     public function signOut()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout or invalid token.',
            ], 500);
        }
    }


    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'success'      => true,
                'message'      => 'Token updated successfully.',
                'access_token' => $newToken,
                'token_type'   => 'bearer',
                'expires_in'   => auth()->factory()->getTTL() * 60,
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update token.',
            ], 500);
        }
    }

    /**
     *  PROFILE (cek user login)
     */
    // public function profile()
    // {
    //     try {
    //         $user = auth()->user();

    //         if (!$user) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Token tidak valid atau sudah kadaluarsa.',
    //             ], 401);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'user'    => $user,
    //         ]);
    //     } catch (JWTException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal memuat profil.',
    //         ], 500);
    //     }
    // }

    public function profile()
{
    try {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah kadaluarsa.',
            ], 401);
        }

        // kalau pakai relasi
        $user->load(['division', 'groups', 'role', 'employee']);

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal memuat profil.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}




    // code for forgot password
    public function requestResetPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors'  => $validator->errors(),
        ], 422);
    }

    // cek user
    $user = Users::where('email', $request->email)->first();
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Email not found.',
        ], 404);
    }

    // generate token
    $token = Str::random(64);

    // simpan ke table password_resets
    DB::table('tb_password_resets')->updateOrInsert(
        ['email' => $request->email],
        [
            'email'      => $request->email,
            'token'      => $token,
            'created_at' => Carbon::now()
        ]
    );

    Mail::send('emails.forgot_password', [
    'token' => $token,
    'user' => $user,
    'resetUrl' => url('/reset-password?email=' . $user->email . '&token=' . $token),
], function ($message) use ($user) {
    $message->to($user->email);
    $message->subject('Reset Your Password');
});


    return response()->json([
        'success' => true,
        'message' => 'The password reset link has been successfully sent to your email, please check your email.',
    ]);
}




public function resetPassword(Request $request)
{
    
    $request->validate([
    'token' => 'required',
    'email' => 'required|email',
    'password' => 'required|min:6|confirmed',
]);


    // Cek token di tabel password_resets
    $resetData = DB::table('tb_password_resets')
        ->where('email', $request->email)
        ->where('token', $request->token)
        ->first();

    if (!$resetData) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid token or expired.',
        ], 400);
    }

    // Update password baru user
    DB::table('ms_users')
        ->where('email', $request->email)
        ->update([
            'password' => Hash::make($request->password),
            'updated_at' => now(),
        ]);

    // Hapus token supaya tidak reusable
    DB::table('tb_password_resets')
        ->where('email', $request->email)
        ->delete();

    return response()->json([
        'success' => true,
        'message' => 'Your password has been successfully reset.',
    ]);
}


   
}