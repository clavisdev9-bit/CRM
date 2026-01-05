<?php

namespace App\Http\Controllers\Api\Users\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApiResponse;
use App\Models\Attendances;
use App\Http\Resources\AttendanceResourceCollection;
use App\Http\Resources\AttendanceResource;
use App\Http\Requests\AttendanceValidationIndex;
use App\Http\Requests\AttendanceValidationRequest;
use App\Models\MsUsers;
use Carbon\Carbon;


class Attendance extends Controller
{
    protected $Attendances;
    protected $MsUsers;

    public function __construct(Attendances $Attendances, MsUsers $MsUsers)
    {
        $this->Attendances = $Attendances;
        $this->MsUsers = $MsUsers;
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



public function store(AttendanceValidationRequest $request)
{
    $user = auth('api')->user();

    if (!$user) {
        return ApiResponse::error('Unauthenticated', 401);
    }

    $today = Carbon::today();

    // 🔍 Ambil attendance hari ini
    $todayAttendances = Attendances::where('user_id', $user->id_user)
        ->whereDate('attendance_date', $today)
        ->get();

    $hasCheckIn  = $todayAttendances->where('attendance_type', 'IN')->isNotEmpty();
    $hasCheckOut = $todayAttendances->where('attendance_type', 'OUT')->isNotEmpty();

    // ❌ OUT sebelum IN
    if ($request->attendance_type === 'OUT' && !$hasCheckIn) {
        return ApiResponse::error(
            'Tidak bisa Check Out sebelum Check In',
            422
        );
    }

    // ❌ DOUBLE IN
    if ($request->attendance_type === 'IN' && $hasCheckIn) {
        return ApiResponse::error(
            'Anda sudah melakukan Check In hari ini',
            422
        );
    }

    // ❌ DOUBLE OUT
    if ($request->attendance_type === 'OUT' && $hasCheckOut) {
        return ApiResponse::error(
            'Anda sudah melakukan Check Out hari ini',
            422
        );
    }

    // ✅ SIMPAN ATTENDANCE
    $attendance = Attendances::create([
        'user_id'              => $user->id_user,
        'attendance_type'      => $request->attendance_type,
        'attendance_date'      => $today,
        'attendance_time'      => now()->format('H:i:s'),
        'attendance_datetime'  => now(),
        'location_name'        => $request->location_name,
        'latitude'             => $request->latitude,
        'longitude'            => $request->longitude,
        'device_type'          => $request->device_type,
        'ip_address'           => request()->ip(),
        'attendance_status'    => 'READY'
    ]);

    return ApiResponse::success(
        new AttendanceResource($attendance),
        'Attendance berhasil disimpan'
    );
}


}
