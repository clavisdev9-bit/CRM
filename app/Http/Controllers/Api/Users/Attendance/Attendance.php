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

            // cek user  udah absen apa belum hari ini
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


                // get user by id
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
                            ->orWhereRaw("attendance_date::text ILIKE ?", ["%{$search}%"])
                            ->orWhereRaw("attendance_time::text ILIKE ?", ["%{$search}%"]);
                    });
                })
                ->orderBy($sortBy, $sortDir)
                ->orderBy('attendance_date', 'asc');
                    $results = $query->paginate($perPage);
                    return ApiResponse::paginate(
                        new AttendanceResourceCollection($results),
                        'Success'
                    );
                }

                // detect Device User
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
                    //  code untuk ambil attendance_mode dari tabel employee
                    // $employee = MsEmployee::find($user->id_user);
                    //  BENAR — cari lewat kolom user_id
                    $employee = MsEmployee::where('user_id', $user->id_user)->first();
                    if (!$employee) {
                        DB::rollBack();
                        return ApiResponse::error('Employee tidak ditemukan', 422);
                    }
                     $attendanceMode = $employee->attendance_mode ?? 'FREE'; 


                     // ===== ATTENDANCE STATUS =====
                    $attendanceStatus = 'COMPLETED';

                    // HANYA UNTUK CHECK IN
                    // if ($request->attendance_type === 'IN') {
                    //     // Jam batas (08:30 WIB)
                    //     $lateLimit = $now->copy()->setTime(8, 30, 0);

                    //     if ($now->gt($lateLimit)) {
                    //         $attendanceStatus = 'LATE';
                    //     }
                    // }

                    if ($request->attendance_type === 'IN') {
                                $lateLimit = $now->copy()->setTime(8, 30, 0);

                                if ($now->gt($lateLimit)) {
                                    $attendanceStatus = 'LATE';
                                } else {
                                    $attendanceStatus = 'ONTIME'; 
                                }
                            }

                        // ===== SAVE =====
                        $attendance = Attendances::create([
                                'user_id'         => $user->id_user,
                                // 'employee_id'     => $user->id_user,
                                'employee_id' => $employee->id_employee,


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
                                // 'accuracy'        => $accuracy,
                                'accuracy'        => 15,

                                'accuracy_status' => 'IGNORED',
                                'policy_status'   => 'ALLOWED',
                                'policy_reason'   => 'Free location (Sales)',

                                // 'device_type'     => 'WEB',
                                'device_type'     => $deviceType,
                                'ip_address'      => request()->ip(),

                                // 'attendance_status' => 'COMPLETED',
                                'attendance_status' => $attendanceStatus,
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

            //  khsusu office attendance
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




                // =====================================================================
    // LAPORAN BULANAN — hanya data milik user yang login (sales)
    // GET /api/attendance/my-report?month=8&year=2021
    // =====================================================================
    public function myReport(Request $request)
    {
        $user = auth('api')->user();
 
        if (!$user) {
            return ApiResponse::error('Unauthenticated', 401);
        }
 
        $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year'  => 'nullable|integer|min:2020|max:2100',
        ]);
 
        $month = (int) ($request->month ?? now()->month);
        $year  = (int) ($request->year  ?? now()->year);
 
        // Ambil semua absensi bulan ini milik user login
        $attendances = Attendances::where('user_id', $user->id_user)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->orderBy('attendance_date', 'asc')
            ->orderBy('attendance_time', 'asc')
            ->get();
 
        // Jumlah hari dalam bulan
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
 
        // Bangun data per hari (1 s/d akhir bulan)
        $attendanceDays = [];
 
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date    = Carbon::create($year, $month, $d);
            $dayRecs = $attendances->filter(
                fn($a) => Carbon::parse($a->attendance_date)->day === $d
            );
 
            $checkIn  = $dayRecs->where('attendance_type', 'IN')->sortBy('attendance_time')->first();
            $checkOut = $dayRecs->where('attendance_type', 'OUT')->sortByDesc('attendance_time')->first();
 
            // Status hari: pakai status dari check-in, atau L kalau weekend
            $status = null;
            if ($date->isWeekend()) {
                $status = 'L';
            } elseif ($checkIn) {
                $status = $checkIn->attendance_status; // ONTIME / LATE / COMPLETED
            }
 
            $attendanceDays[] = [
                'day'        => $d,
                'date'       => $date->toDateString(),
                'day_name'   => $date->locale('id')->isoFormat('ddd'),
                'is_weekend' => $date->isWeekend(),
                'status'     => $status,
                'check_in'   => $checkIn ? [
                    'id'              => $checkIn->id,
                    'time'            => $checkIn->attendance_time,
                    'photo_url'       => $checkIn->photo_path
                                          ? asset('storage/attendance/photos/' . $checkIn->photo_path)
                                          : null,
                    'location_name'   => $checkIn->location_name,
                    'latitude'        => $checkIn->latitude,
                    'longitude'       => $checkIn->longitude,
                    'policy_status'   => $checkIn->policy_status,
                    'accuracy_status' => $checkIn->accuracy_status,
                    'distance'        => $checkIn->distance_from_office,
                    'attendance_mode' => $checkIn->attendance_mode,
                    'device_type'     => $checkIn->device_type,
                ] : null,
                'check_out'  => $checkOut ? [
                    'id'              => $checkOut->id,
                    'time'            => $checkOut->attendance_time,
                    'photo_url'       => $checkOut->photo_path
                                          ? asset('storage/attendance/photos/' . $checkOut->photo_path)
                                          : null,
                    'location_name'   => $checkOut->location_name,
                    'latitude'        => $checkOut->latitude,
                    'longitude'       => $checkOut->longitude,
                    'policy_status'   => $checkOut->policy_status,
                    'attendance_mode' => $checkOut->attendance_mode,
                    'device_type'     => $checkOut->device_type,
                ] : null,
            ];
        }
 
        // Rekap summary
        $checkIns = $attendances->where('attendance_type', 'IN');
 
        $summary = [
            'ONTIME'    => $checkIns->where('attendance_status', 'ONTIME')->count(),
            'LATE'      => $checkIns->where('attendance_status', 'LATE')->count(),
            'COMPLETED' => $checkIns->where('attendance_status', 'COMPLETED')->count(),
            'LIBUR'     => collect($attendanceDays)->where('is_weekend', true)->count(),
            'TOTAL_HADIR' => $checkIns->count(),
            'TOTAL_CHECKOUT' => $attendances->where('attendance_type', 'OUT')->count(),
        ];
 
        return ApiResponse::success([
            'user' => [
                'id_user'  => $user->id_user,
                'fullname' => $user->fullname,
                'username' => $user->username,
                'email'    => $user->email,
            ],
            'period' => [
                'month'      => $month,
                'year'       => $year,
                'label'      => Carbon::create($year, $month)->locale('id')->isoFormat('MMMM YYYY'),
                'days_total' => $daysInMonth,
            ],
            'summary'         => $summary,
            'attendance_days' => $attendanceDays,
        ], 'Success');
    }
 
    // =====================================================================
    // RIWAYAT — list absensi milik user login (paginated)
    // GET /api/attendance/my-history?month=8&year=2021&per_page=15
    // =====================================================================
    public function myHistory(Request $request)
    {
        $user = auth('api')->user();
 
        if (!$user) {
            return ApiResponse::error('Unauthenticated', 401);
        }
 
        $perPage = is_numeric($request->per_page ?? null) ? (int) $request->per_page : 15;
        $sortDir = $request->sort_dir ?? 'desc';
 
        $query = $this->Attendances
            ->with(['user', 'employee'])
            ->where('user_id', $user->id_user)
            ->when($request->filled('month') && $request->filled('year'), function ($q) use ($request) {
                $q->whereMonth('attendance_date', $request->month)
                  ->whereYear('attendance_date', $request->year);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('location_name', 'ILIKE', "%{$search}%")
                        ->orWhere('attendance_type', 'ILIKE', "%{$search}%")
                        ->orWhereRaw("attendance_date::text ILIKE ?", ["%{$search}%"]);
                });
            })
            ->orderBy('attendance_date', $sortDir)
            ->orderBy('attendance_time', $sortDir);
 
        $results = $query->paginate($perPage);
 
        return ApiResponse::paginate(
            new AttendanceResourceCollection($results),
            'Success'
        );
    }
}
