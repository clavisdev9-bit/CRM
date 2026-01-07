<?php

namespace App\Http\Controllers\Api\Users\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ApiResponse;
use App\Models\Attendances;
use App\Http\Resources\AttendanceResourceCollection;
use App\Http\Resources\AttendanceResource;
use App\Http\Requests\AttendanceValidationIndex;
use App\Http\Requests\AttendanceValidationRequest;
use App\Models\MsUsers;
use Carbon\Carbon;
use App\Models\MsOffice;
use App\Models\MsEmployee;
use App\Models\MsAttendancePolicies;




class Attendance extends Controller
{
    protected $Attendances;
    protected $MsUsers;

    public function __construct(Attendances $Attendances, MsUsers $MsUsers)
    {
        $this->Attendances = $Attendances;
        $this->MsUsers = $MsUsers;
    }


    public function checkToday()
        {
            $user = auth('api')->user();

            if (!$user) {
                return ApiResponse::error('Unauthenticated', 401);
            }

            $today = Carbon::today();

            $records = Attendances::where('user_id', $user->id_user)
                ->whereDate('attendance_date', $today)
                ->get();

            if ($records->isEmpty()) {
                return ApiResponse::success([
                    'has_attendance_today' => false,
                    'status' => 'NONE',
                    'check_in' => null,
                    'check_out' => null
                ]);
            }

            $checkIn = $records->where('attendance_type', 'IN')->min('attendance_time');
            $checkOut = $records->where('attendance_type', 'OUT')->max('attendance_time');

            return ApiResponse::success([
                'has_attendance_today' => true,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $checkOut ? 'COMPLETE' : 'IN_ONLY'
            ]);
        }


        public function GetAttendanceById(AttendanceValidationIndex $request)
        {
            $validated = $request->validated();

            $search      = $validated['search'] ?? null;
            $perPage     = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
            $sortBy      = $validated['sort_by'] ?? 'attendance_date';
            $sortDir     = $validated['sort_dir'] ?? 'desc';
            $onlyDeleted = $validated['only_deleted'] ?? false;

            //  JWT USER (INI YANG BENAR)
            $user = auth('api')->user(); // atau auth('jwt')

            if (!$user) {
                return ApiResponse::error('Unauthenticated', 401);
            }

            $userId = $user->id_user;

            $query = $this->Attendances
                ->with(['user', 'employee'])
                ->where('user_id', $userId)
                ->when($onlyDeleted, fn ($q) => $q->onlyTrashed())
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('location_name', 'ILIKE', "%{$search}%")
                            ->orWhere('attendance_type', 'ILIKE', "%{$search}%")
                            ->orWhere('policy_status', 'ILIKE', "%{$search}%");
                    });
                })
                ->orderBy($sortBy, $sortDir);

            $results = $query->paginate($perPage);

            return ApiResponse::paginate(
                new AttendanceResourceCollection($results),
                'Success'
            );
        }


        private function detectDeviceTypeFromUserAgent(): string
            {
                $agent = strtolower(request()->userAgent() ?? '');

                if (str_contains($agent, 'android')) {
                    return 'ANDROID';
                }

                if (
                    str_contains($agent, 'iphone') ||
                    str_contains($agent, 'ipad') ||
                    str_contains($agent, 'ios')
                ) {
                    return 'IOS';
                }

                return 'WEB';
            }


            public function showAttendance(string $id)
                {
                    $attendance = $this->Attendances
                        ->with(['user', 'employee'])
                        ->find($id);

                    if (!$attendance) {
                        return ApiResponse::error('Attendance not found', [
                            'id' => ['Data with that ID is not available']
                        ], 404);
                    }

                    return ApiResponse::success(
                        new AttendanceResource($attendance),
                        'Success, take the detailed Attendance',
                        200
                    );
                }




        public function storeAttendanceFreeLocation(AttendanceValidationRequest $request)
        {
            $user = auth('api')->user();
            if (!$user) {
                return ApiResponse::error('Unauthenticated', 401);
            }

            $now   = now()->timezone('Asia/Jakarta');
            $today = $now->toDateString();

            // ===== VALIDATION =====
            if (!in_array($request->device_type, ['WEB', 'ANDROID', 'IOS'])) {
                return ApiResponse::error('Device tidak valid', 422);
            }

            if (!in_array($request->attendance_type, ['IN', 'OUT'])) {
                return ApiResponse::error('Attendance type tidak valid', 422);
            }

            try {
                DB::beginTransaction();

                // ===== LOCK HARI INI =====
                $todayAttendances = Attendances::where('user_id', $user->id_user)
                    ->whereDate('attendance_date', $today)
                    ->lockForUpdate()
                    ->get();

                $hasCheckIn  = $todayAttendances->where('attendance_type', 'IN')->isNotEmpty();
                $hasCheckOut = $todayAttendances->where('attendance_type', 'OUT')->isNotEmpty();

                if ($request->attendance_type === 'OUT' && !$hasCheckIn) {
                    DB::rollBack();
                    return ApiResponse::error('Tidak bisa Check Out sebelum Check In', 422);
                }

                if ($request->attendance_type === 'IN' && $hasCheckIn) {
                    DB::rollBack();
                    return ApiResponse::error('Anda sudah Check In hari ini', 422);
                }

                if ($request->attendance_type === 'OUT' && $hasCheckOut) {
                    DB::rollBack();
                    return ApiResponse::error('Anda sudah Check Out hari ini', 422);
                }
                $deviceType = $this->detectDeviceTypeFromUserAgent();
                // ===== FOTO =====
                $photoName = null;
                if ($request->hasFile('photo_path')) {
                    $file = $request->file('photo_path');

                    // Gunakan tanggal dari attendance request atau tanggal sekarang
                    $date = $request->attendance_date ?? date('Y-m-d');
                    $time = date('His'); // hhmmss untuk unik

                    $userId = $user->id_user;
                    $random = Str::random(5);
                    $photoName = $date . '_' . $time . '_Iduser-' . $userId . '_' . $random . '.' . $file->getClientOriginalExtension();


                    // Simpan file di folder 'attendance/photos'
                    $file->storeAs('attendance/photos', $photoName, 'public');
                }

                $accuracy = (float) $request->accuracy;
                $maxAccuracyAllowed = 20; // meter

                if ($accuracy > $maxAccuracyAllowed) {
                    DB::rollBack();
                    return ApiResponse::error(
                        'Akurasi lokasi terlalu rendah, silakan ulangi absensi',
                        422
                    );
                }

                //  code untuk ambil attendance_mode dari tabel employee
                 $employee = MsEmployee::find($user->id_user);
                    if (!$employee) {
                        DB::rollBack();
                        return ApiResponse::error('Employee tidak ditemukan', 422);
                    }

                     $attendanceMode = $employee->attendance_mode ?? 'FREE'; 
                     
                        // ===== SAVE =====
                        $attendance = Attendances::create([
                                'user_id'         => $user->id_user,
                                'employee_id'     => $user->id_user,

                                //  INI SEKARANG AKAN MASUK DB
                                'attendance_mode' => $attendanceMode,

                                 'attendance_type' => $request->attendance_type,

                                'attendance_date' => $today,
                                'attendance_time' => $now->format('H:i:s'),
                                'attendance_datetime' => $now,

                                'photo_path'      => $photoName,

                                'location_name'   => $request->location_name,
                                'latitude'        => $request->latitude,
                                'longitude'       => $request->longitude,
                                'accuracy'        => $accuracy,

                                'accuracy_status' => 'IGNORED',
                                'policy_status'   => 'ALLOWED',
                                'policy_reason'   => 'Free location (Sales)',

                                // 'device_type'     => 'WEB',
                                'device_type'     => $deviceType,
                                'ip_address'      => request()->ip(),

                                'attendance_status' => 'READY',
                            ]);
                                DB::commit();

                return ApiResponse::success(
                    new AttendanceResource($attendance),
                    'Attendance berhasil disimpan'
                );

            } catch (\Throwable $e) {
                    DB::rollBack();
                    $errorMessage = "Error: {$e->getMessage()} di file {$e->getFile()} baris {$e->getLine()}";
                    
                    return ApiResponse::error($errorMessage, 500);
                }
        }






            // code untuk attendance office
            private function calculateDistance($lat1, $lon1, $lat2, $lon2)
            {
                $earthRadius = 6371000; // meter

                $lat1 = deg2rad($lat1);
                $lon1 = deg2rad($lon1);
                $lat2 = deg2rad($lat2);
                $lon2 = deg2rad($lon2);

                $dLat = $lat2 - $lat1;
                $dLon = $lon2 - $lon1;

                $a = sin($dLat / 2) ** 2 +
                    cos($lat1) * cos($lat2) *
                    sin($dLon / 2) ** 2;

                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

                return $earthRadius * $c;
            }





            public function storeAttendanceOffice(AttendanceValidationRequest $request)
            {
                $user = auth('api')->user();
                if (!$user) {
                    return ApiResponse::error('Unauthenticated', 401);
                }

                $now   = now()->timezone('Asia/Jakarta');
                $today = $now->toDateString();

                // ===== VALIDASI DASAR =====
                if (!in_array($request->attendance_type, ['IN', 'OUT'])) {
                    return ApiResponse::error('Attendance type tidak valid', 422);
                }

                try {
                    DB::beginTransaction();

                    // ===== LOCK HARI INI =====
                    $todayAttendances = Attendances::where('user_id', $user->id_user)
                        ->whereDate('attendance_date', $today)
                        ->lockForUpdate()
                        ->get();

                    $hasCheckIn  = $todayAttendances->where('attendance_type', 'IN')->isNotEmpty();
                    $hasCheckOut = $todayAttendances->where('attendance_type', 'OUT')->isNotEmpty();

                    if ($request->attendance_type === 'OUT' && !$hasCheckIn) {
                        DB::rollBack();
                        return ApiResponse::error('Tidak bisa Check Out sebelum Check In', 422);
                    }

                    if ($request->attendance_type === 'IN' && $hasCheckIn) {
                        DB::rollBack();
                        return ApiResponse::error('Anda sudah Check In hari ini', 422);
                    }

                    if ($request->attendance_type === 'OUT' && $hasCheckOut) {
                        DB::rollBack();
                        return ApiResponse::error('Anda sudah Check Out hari ini', 422);
                    }

                    // ===== OFFICE DATA (HARDCODE / DB) =====
                    $officeLat = -6.2000000;
                    $officeLng = 106.8166667;
                    $allowedRadius = 100; // meter

                    // ===== HITUNG JARAK =====
                    $distance = $this->calculateDistance(
                        $request->latitude,
                        $request->longitude,
                        $officeLat,
                        $officeLng
                    );

                    // ===== POLICY =====
                    if ($distance > $allowedRadius) {
                        DB::rollBack();
                        return ApiResponse::error(
                            'Anda berada di luar radius kantor',
                            422
                        );
                    }

                    // ===== STATUS =====
                    $attendanceStatus = 'ONTIME';
                    if ($request->attendance_type === 'IN' && $now->format('H:i') > '08:30') {
                        $attendanceStatus = 'LATE';
                    }

                    // ===== FOTO =====
                    $photoPath = null;
                    if ($request->hasFile('photo_path')) {
                        $photoPath = $request->file('photo_path')
                            ->store('attendance/photos', 'public');
                    }

                    // ===== SAVE =====
                    $attendance = Attendances::create([
                        'user_id'         => $user->id_user,
                        'employee_id'     => $user->id_user,

                        'attendance_mode' => 'OFFICE',

                        'attendance_type' => $request->attendance_type,
                        'attendance_date' => $today,
                        'attendance_time' => $now->format('H:i:s'),
                        'attendance_datetime' => $now,

                        'photo_path'      => $photoPath,

                        'location_name'   => $request->location_name,
                        'latitude'        => $request->latitude,
                        'longitude'       => $request->longitude,
                        'accuracy'        => $request->accuracy,

                        'accuracy_status' => 'HIGH',

                        'policy_status'   => 'ALLOWED',
                        'policy_reason'   => 'Inside office radius',

                        // ===== OFFICE SNAPSHOT =====
                        'office_latitude'       => $officeLat,
                        'office_longitude'      => $officeLng,
                        'distance_from_office'  => round($distance, 2),
                        'allowed_radius'        => $allowedRadius,

                        'device_type'     => $request->device_type,
                        'ip_address'      => request()->ip(),

                        'attendance_status' => 'READY',
                    ]);

                    DB::commit();

                    return ApiResponse::success(
                        new AttendanceResource($attendance),
                        'Attendance OFFICE berhasil disimpan'
                    );

                } catch (\Throwable $e) {
                    DB::rollBack();
                    return ApiResponse::error(
                        "Error: {$e->getMessage()}",
                        500
                    );
                }
            }



            public function deleteAttendance($id_attendance)
            {
                try {
                    // ===== FIND ATTENDANCE =====
                    $attendance = Attendances::findOrFail($id_attendance);

                    // ===== DELETE PHOTO =====
                    if ($attendance->photo_path && Storage::disk('public')->exists($attendance->photo_path)) {
                        Storage::disk('public')->delete($attendance->photo_path);
                    }

                    // ===== SOFT / HARD DELETE =====
                    $attendance->delete(); // soft delete if model uses SoftDeletes

                    return ApiResponse::success(
                        null,
                        'Attendance successfully deleted'
                    );

                } catch (ModelNotFoundException $e) {
                    return ApiResponse::error(
                        'Attendance not found',
                        404
                    );
                } catch (\Throwable $e) {
                    return ApiResponse::error(
                        'Failed to delete attendance',
                        config('app.debug') ? ['exception' => $e->getMessage()] : null,
                        500
                    );
                }
            }




            public function updateAttendance(Request $request, $id_attendance)
                {
                    try {
                        // ===== FIND ATTENDANCE =====
                        $attendance = Attendances::findOrFail($id_attendance);

                        // ===== VALIDATION =====
                    $request->validate([
                            'attendance_datetime' => 'nullable|date', // timestamp
                            'attendance_date'     => 'nullable|date', // date
                            'attendance_time'     => 'nullable|date_format:H:i', // time
                            'photo_path'          => 'nullable|image|max:2048',
                            'noted'               => 'nullable|string|max:255',
                        ]);


                        
                    

                        // ===== UPDATE PHOTO =====
                        if ($request->hasFile('photo_path')) {
                            $file = $request->file('photo_path');

                            // Hapus foto lama jika ada
                            if ($attendance->photo_path && Storage::disk('public')->exists('attendance/photos/' . $attendance->photo_path)) {
                                Storage::disk('public')->delete('attendance/photos/' . $attendance->photo_path);
                            }

                            // Nama file unik: update-YYYYMMDD-HHMMSS-random
                            $timestamp = date('Ymd-His'); // 20260107-143012
                            $random = Str::random(5);     // abc12
                            $photoName = "update-{$timestamp}-{$random}." . $file->getClientOriginalExtension();

                            // Simpan file di folder 'attendance/photos'
                            $file->storeAs('attendance/photos', $photoName, 'public');

                            // Simpan nama file saja di database
                            $attendance->photo_path = $photoName;
                        }


                        // ===== UPDATE FIELD LAIN YANG BOLEH DIEDIT =====
                    $attendance->attendance_datetime = $request->attendance_datetime ?? $attendance->attendance_datetime;
                        $attendance->attendance_date     = $request->attendance_date ?? $attendance->attendance_date;
                        $attendance->attendance_time     = $request->attendance_time ?? $attendance->attendance_time;
                        $attendance->noted               = $request->noted ?? $attendance->noted;

                        $attendance->save();

                        // ===== RESPONSE =====
                        return ApiResponse::success(
                            $attendance,
                            'Attendance updated successfully'
                        );

                    } catch (ModelNotFoundException $e) {
                        return ApiResponse::error('Attendance not found', 404);
                    } catch (\Throwable $e) {
                        return ApiResponse::error(
                            'Failed to update attendance',
                            config('app.debug') ? ['exception' => $e->getMessage()] : null,
                            500
                        );
                    }
                }
}
