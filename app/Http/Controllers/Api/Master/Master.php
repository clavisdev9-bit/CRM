<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;

use App\Models\MsEmployee;
use App\Http\Resources\EmployeeResources;
use App\Http\Resources\EmployeeResourcesCollection;
use App\Http\Requests\EmployeeValidationindex;
use App\Http\Requests\EmployeeValidationRequest;
use App\Http\Requests\EmployeeValidationUpdateRequest;
use App\Models\MsUsers;
use App\Models\MsOffice;


class Master extends Controller
{
    protected $MsEmployee;
    protected $MsOffice;
    public function __construct(MsEmployee $MsEmployee, MsOffice $MsOffice) {
        $this->MsEmployee = $MsEmployee;
        $this->MsOffice = $MsOffice;
    }


    //  public function Employee(EmployeeValidationindex $request) 
    //    {
    //         $validated = $request->validated();
    //         $search = $validated['search'] ?? null;
    //         $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
    //         $sortBy = $validated['sort_by'] ?? 'created_at';
    //         $sortDir = $validated['sort_dir'] ?? 'desc';
    //         $onlyDeleted = $validated['only_deleted'] ?? false;

    //     $query = $this->MsEmployee
    //     ->with([
    //         'user:id_user,fullname,username,email,image,is_active,divisi_id,group_id',
    //         'user.division:id,name_division',
    //         'user.groups:id_group,name_group',
    //     ])
    //     ->onlyDeleted($onlyDeleted)
    //     ->search($search)
    //     ->sort($sortBy, $sortDir);

    //         $results = $query->paginate($perPage);
    //         $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
    //         return ApiResponse::paginate(new EmployeeResourcesCollection($results), $message);
    //    }

    public function Employee(EmployeeValidationindex $request) 
{
    $validated = $request->validated();
    $search = $validated['search'] ?? null;
    $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
    $sortBy = $validated['sort_by'] ?? 'created_at';
    $sortDir = $validated['sort_dir'] ?? 'desc';
    $onlyDeleted = $validated['only_deleted'] ?? false;

    $query = $this->MsEmployee
    ->with([
        'user:id_user,fullname,username,email,image,is_active,divisi_id,group_id',
        'user.division:id,name_division',
        'user.groups:id_group,name_group',
        'office:id,office_name',  // ← TAMBAHKAN INI
    ])
    ->onlyDeleted($onlyDeleted)
    ->search($search)
    ->sort($sortBy, $sortDir);

    $results = $query->paginate($perPage);
    $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
    return ApiResponse::paginate(new EmployeeResourcesCollection($results), $message);
}


       public function showEmployee(int $id)
            {
                try {
                    $employee = MsEmployee::with([
                    'user:id_user,fullname,username,email,image,is_active,divisi_id,group_id',
                    'user.division:id,name_division',
                    'user.groups:id_group,name_group',
                    'office:id,office_name,latitude,longitude,radius',
                ])->findOrFail($id);


                    return ApiResponse::success(
                        new EmployeeResources($employee),
                        'Success, employee detail retrieved'
                    );

                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    return ApiResponse::error(
                        'Employee not found',
                        ['id' => ['Data employee dengan ID tersebut tidak ditemukan']],
                        404
                    );
                }
            }


            public function storeEmployee(EmployeeValidationRequest $request)
            {
                $data = $request->validated();

                DB::beginTransaction();

                try {
                    $employee = MsEmployee::create([
                        'user_id'         => $data['user_id'],
                        'office_id'         => $data['office_id'],
                        'nik'             => $data['nik'],
                        'tempat_lahir'    => $data['tempat_lahir'],
                        'tanggal_lahir'   => $data['tanggal_lahir'],
                        'jenis_kelamin'   => $data['jenis_kelamin'],
                        'alamat'          => $data['alamat'],
                        'no_hp'           => $data['no_hp'],
                        'tanggal_masuk'   => $data['tanggal_masuk'],
                        'status_karyawan' => $data['status_karyawan'],
                        'attendance_mode' => $data['attendance_mode'],
                    ]);

                    DB::commit();

                    return ApiResponse::success(
                        new EmployeeResources($employee->load('user')),
                        'Success Create New Employee',
                        201
                    );

                } catch (\Illuminate\Database\QueryException $e) {

                    DB::rollBack();

                    return ApiResponse::error(
                        'Failed to create employee (query error)',
                        config('app.debug') ? ['exception' => $e->getMessage()] : null,
                        422
                    );

                } catch (\Throwable $e) {

                    DB::rollBack();

                    return ApiResponse::error(
                        'An error occurred while creating the employee.',
                        config('app.debug') ? ['exception' => $e->getMessage()] : null,
                        500
                    );
                }
            }


            public function updateEmployee(EmployeeValidationUpdateRequest $request, $id)
                {
                    $data = $request->validated();

                    $employee = MsEmployee::find($id);

                    if (!$employee) {
                        return ApiResponse::error(
                            'Employee with that ID was not found.',
                            ['id_employee' => ['Data not available.']],
                            404
                        );
                    }

                    try {
                        // OPTIONAL: cek duplikasi NIK (jika ada method)
                        if (method_exists(MsEmployee::class, 'isDuplicate')) {
                            $errors = MsEmployee::isDuplicate($data, $id);
                            if (!empty($errors)) {
                                return ApiResponse::error('Validation failed', $errors, 400);
                            }
                        }

                        $employee->update($data);

                        return ApiResponse::success(
                            new EmployeeResources($employee->load('user')),
                            'Success Update Employee',
                            200
                        );

                    } catch (\Illuminate\Database\QueryException $e) {

                        return ApiResponse::error(
                            'Failed to update Employee (query error)',
                            [
                                'exception' => config('app.debug') ? $e->getMessage() : null
                            ],
                            422
                        );

                    } catch (\Exception $e) {

                        return ApiResponse::error(
                            'Failed to update Employee',
                            [
                                'exception' => config('app.debug') ? $e->getMessage() : 'Please try again later'
                            ],
                            500
                        );
                    }
                }




       public function deleteEmployee($id)
        {
            $employee = MsEmployee::find($id);

            if (!$employee) {
                return ApiResponse::error(
                    'Employee with that ID was not found.',
                    ['id' => ['Data not available.']],
                    404
                );
            }

            try {
                $employee->delete(); // Soft delete

                return ApiResponse::success(
                    null,
                    'Success Delete Employee',
                    200
                );

            } catch (\Illuminate\Database\QueryException $e) {

                return ApiResponse::error(
                    'Failed to delete Employee (query error)',
                    [
                        'exception' => config('app.debug') ? $e->getMessage() : null
                    ],
                    422
                );

            } catch (\Exception $e) {

                return ApiResponse::error(
                    'Failed to delete Employee',
                    [
                        'exception' => config('app.debug') ? $e->getMessage() : 'Please try again later'
                    ],
                    500
                );
            }
        }




        public function restoreEmployee($id)
            {
                $employee = MsEmployee::onlyTrashed()->find($id);

                if (!$employee) {
                    return ApiResponse::error(
                        'Deleted employee not found.',
                        ['id' => ['Data not available.']],
                        404
                    );
                }

                $employee->restore();

                return ApiResponse::success(
                    new EmployeeResources($employee->load('user')),
                    'Success Restore Employee',
                    200
                );
            }





        public function getAvailableUsers(Request $request)
            {
                $employeeId = $request->employee_id;

                $users = MsUsers::where('is_active', true)
                    ->where(function ($query) use ($employeeId) {
                        $query->whereDoesntHave('employee');

                        if ($employeeId) {
                            $query->orWhereHas('employee', function ($q) use ($employeeId) {
                                $q->where('id_employee', $employeeId);
                            });
                        }
                    })
                    ->select('id_user', 'fullname', 'username', 'email')
                    ->orderBy('fullname')
                    ->get();

                return ApiResponse::success(
                    $users,
                    'Success get available users'
                );
            }



                public function selectOffice()
            {
                return response()->json(
                    MsOffice::query()
                        ->select('id', 'office_name')
                        // ->where('i', true)
                        ->orderBy('id', 'asc')
                        ->get()
                );
            }

}
