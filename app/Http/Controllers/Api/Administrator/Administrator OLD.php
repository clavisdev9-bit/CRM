<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use App\Models\MsRole;
use App\Http\Resources\RoleResources;
use App\Http\Resources\RoleResourcesCollection;
use App\Http\Requests\RoleValidationIndex;
use App\Http\Requests\RoleValidationRequest;

use App\Models\MsMenu;
use App\Http\Requests\MenuValidationIndex;
use App\Http\Requests\MenuValidationRequest;
use App\Http\Resources\MenuResources;
use App\Http\Resources\MenuResourcesCollection;

usE App\Models\MsSubmenu;
use App\Http\Requests\SubmenuValidationIndex;
use App\Http\Resources\SubmenuResources;
use App\Http\Resources\SubmenuResourcesCollection;
use App\Http\Requests\SubmenuValidationRequest;
use App\Http\Requests\SubmenuValidationUpdateRequest;

use App\Models\MsAccesMenu;
use App\Http\Requests\AccessMenuValidationIndex;
use App\Http\Resources\AccessMenuResource;
use App\Http\Resources\AccessMenuResourceCollection;

use App\Models\MsUsers;
use App\Http\Resources\UsersResources;
use App\Http\Resources\UsersResourcesCollection;
use App\Http\Requests\UsersValidationIndex;
use App\Http\Requests\UsersValidationRequest;
use App\Http\Requests\UsersValidationUpdateRequest;

use App\Models\MsAccessSubmenu;
use App\Http\Requests\UserSubmenuAccessRequestIndex;
use App\Http\Resources\UserSubmenuAccessResource;
use App\Http\Resources\UserSubmenuAccessResourceCollection;
use App\Http\Requests\UpdateUserSubmenuAccessRequest;



use App\Models\AppSettingModel;
use App\Http\Resources\AppSettingResources;
use App\Http\Resources\AppSettingResourcesCollection;
use App\Http\Requests\AppSettingValidationIndex;
use App\Http\Requests\AppSettingValidationRequest;
use App\Http\Requests\AppSettingValidationUpdateRequest;

use App\Models\MsCabang;
use App\Http\Resources\CabangResources;
use App\Http\Resources\CabangResourcesCollection;
use App\Http\Requests\CabangValidationIndex;
use App\Http\Requests\CabangValidationRequest;




class Administrator extends Controller
{

    protected $MsRole;
    protected $MsMenu;
    protected $MsSubmenu;
    protected $MsAccesMenu;
    protected $MsUsers;
    protected $MsAccessSubmenu;
    protected $AppSettingModel;

    protected $MsCabang;
    public function __construct(AppSettingModel $AppSettingModel, MsRole $MsRole, MsMenu $MsMenu, MsSubmenu $MsSubmenu, MsAccesMenu $MsAccesMenu, MsUsers $MsUsers, MsAccessSubmenu $MsAccessSubmenu, MsCabang $MsCabang) {
        $this->MsRole = $MsRole;
        $this->MsMenu = $MsMenu;
        $this->MsSubmenu = $MsSubmenu;
        $this->MsAccesMenu = $MsAccesMenu;
        $this->MsUsers = $MsUsers;
        $this->MsAccessSubmenu = $MsAccessSubmenu;
        $this->AppSettingModel = $AppSettingModel;
        $this->MsCabang = $MsCabang;
    }

      
    // code role
    // get Data
      public function Role(RoleValidationIndex $request) 
       {
            $validated = $request->validated();
            $search = $validated['search'] ?? null;
            $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortDir = $validated['sort_dir'] ?? 'desc';
            $onlyDeleted = $validated['only_deleted'] ?? false;

            $query = $this->MsRole
                ->onlyDeleted($onlyDeleted)
                ->search($search)
                ->sort($sortBy, $sortDir);
            $results = $query->paginate($perPage);
            $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
            return ApiResponse::paginate(new RoleResourcesCollection($results), $message);
       }

    //   show detail data 
       public function showRole(string $id)
        {
            $Role = $this->MsRole->find($id);
            if (!$Role) {
                return ApiResponse::error('Role not found', [
                    'id' => ['Data with that ID is not available']
                ], 404);
            }
            return ApiResponse::success(new RoleResources($Role), 'Success, take the detailed Role', 200);
        }

    //   Add data
    public function storeRole(RoleValidationRequest $request)
        {
            $data = $request->validated();

            try {
        
                $errors = MsRole::isDuplicate($data); 
                if (!empty($errors)) {
                        return ApiResponse::error('Validation failed', $errors, 400);
                    }

                $Role = $this->MsRole->create([
                    'role'        => $data['role'],
                    'description' => $data['description'],
                ]);

                return ApiResponse::success(new RoleResources($Role), 'Success Create New Role', 201);

            } catch (\Illuminate\Database\QueryException $e) {
                return ApiResponse::error('Failed to create role (query error)', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 422);
            } catch (\Exception $e) {
                return ApiResponse::error('An error occurred while creating the role.', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }


        
    public function updateRole(RoleValidationRequest $request, $id_role)
        {
            $data = $request->validated();

            $Role = MsRole::find($id_role);

            if (!$Role) {
                return ApiResponse::error(
                    'Role with that ID was not found.',
                    ['id_role' => ['Data not available.']],
                    404
                );
            }

            try {
                $errors = MsRole::isDuplicate($data, $id_role);
                if (!empty($errors)) {
                    return ApiResponse::error('Validation failed', $errors, 400);
                }

                $Role->update($data);
                return ApiResponse::success(new RoleResources($Role), 'Success Update Role', 200);
            } catch (\Illuminate\Database\QueryException $e) {
                return ApiResponse::error('Failed to update Role (query error)', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 422);
            } catch (\Exception $e) {
                return ApiResponse::error('Failed to update Role', [
                    'exception' => config('app.debug') ? $e->getMessage() : 'Please try again later'
                ], 500);
            }
        }


    public function destroyRole(string $id_role)
        {
            try {
                $Role = $this->MsRole->find($id_role);
            if (!$Role) {
                    return ApiResponse::error('Role with that ID was not found.', [
                        'id' => ['Data not availiable.']
                    ], 404);
                }
                $Role->delete();
                return ApiResponse::success(new RoleResources($Role), 'Success Delete Role', 200);
            } catch (\Exception $e) {
                return ApiResponse::error('Failed to delete Role', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }


        // code menu
    public function Menu(MenuValidationIndex $request) 
       {
            $validated = $request->validated();
            $search = $validated['search'] ?? null;
            $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortDir = $validated['sort_dir'] ?? 'desc';
            $onlyDeleted = $validated['only_deleted'] ?? false;

            $query = $this->MsMenu
                ->onlyDeleted($onlyDeleted)
                ->search($search)
                ->sort($sortBy, $sortDir);
            $results = $query->paginate($perPage);
            $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
            return ApiResponse::paginate(new MenuResourcesCollection($results), $message);
        }


         //   show detail data 
       public function showMenu(string $id)
        {
            $Menu = $this->MsMenu->find($id);
            if (!$Menu) {
                return ApiResponse::error('Menu not found', [
                    'id' => ['Data with that ID is not available']
                ], 404);
            }
            return ApiResponse::success(new MenuResources($Menu), 'Success, take the detailed Menu', 200);
        }


        public function storeMenu(MenuValidationRequest $request)
        {
            $data = $request->validated();

            try {
        
                $errors = MsMenu::isDuplicate($data); 
                if (!empty($errors)) {
                        return ApiResponse::error('Validation failed', $errors, 400);
                    }

                $Menu = $this->MsMenu->create([
                    'menu' => $data['menu'],
                ]);

                return ApiResponse::success(new MenuResources($Menu), 'Success Create New Menu', 201);

            } catch (\Illuminate\Database\QueryException $e) {
                return ApiResponse::error('Failed to create menu (query error)', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 422);
            } catch (\Exception $e) {
                return ApiResponse::error('An error occurred while creating the menu.', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }



        public function updateMenu(MenuValidationRequest $request, $id_menu)
        {
            $data = $request->validated();

            $Menu = MsMenu::find($id_menu);
            if (!$Menu) {
                return ApiResponse::error(
                    'Menu with that ID was not found.',
                    ['id_menu' => ['Data not available.']],
                    404
                );
            }

            try {
                $errors = MsMenu::isDuplicate($data, $id_menu);
                if (!empty($errors)) {
                    return ApiResponse::error('Validation failed', $errors, 400);
                }

                $Menu->update($data);
                return ApiResponse::success(new MenuResources($Menu), 'Success Update Menu', 200);
            } catch (\Illuminate\Database\QueryException $e) {
                return ApiResponse::error('Failed to update Menu (query error)', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 422);
            } catch (\Exception $e) {
                return ApiResponse::error('Failed to update Menu', [
                    'exception' => config('app.debug') ? $e->getMessage() : 'Please try again later'
                ], 500);
            }
        }


    public function destroyMenu(string $id_menu)
        {
            try {
                $Menu = $this->MsMenu->find($id_menu);
            if (!$Menu) {
                    return ApiResponse::error('Menu with that ID was not found.', [
                        'id' => ['Data not availiable.']
                    ], 404);
                }
                $Menu->delete();
                return ApiResponse::success(new MenuResources($Menu), 'Success Delete Menu', 200);
            } catch (\Exception $e) {
                return ApiResponse::error('Failed to delete Menu', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
    }



        // code submenu
        public function Submenu(SubmenuValidationIndex $request)
            {
                $validated = $request->validated();
                $search      = $validated['search'] ?? null;
                $perPage     = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
                $onlyDeleted = $validated['only_deleted'] ?? false;
                $sortBy      = $validated['sort_by'] ?? 'created_at';
                $sortDir = ($validated['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';


                $query = MsSubmenu::with(['menu:id_menu,menu', 'children'])
                        ->onlyDeleted($onlyDeleted)
                        ->whereNull('parent_id')
                        ->when($search, function ($q) use ($search) {
                            $q->where(function ($sub) use ($search) {
                                $sub->where('title', 'LIKE', "%{$search}%")
                                    ->orWhereHas('children', function ($child) use ($search) {
                                        $child->search($search);
                                    });
                            });
                        })
                        ->orderBy($sortBy, $sortDir)
                        ->orderBy('id_submenu', 'Desc');

                $results = $query->paginate($perPage);
                return ApiResponse::paginate(
                    new SubmenuResourcesCollection($results),
                    $results->isEmpty()
                        ? 'The data you are looking for was not found'
                        : 'Success'
                );
            }

    

        public function showSubmenu(int $id)
        {
            try {
                $submenu = MsSubmenu::with([
                        'menu:id_menu,menu',
                        'children' => function ($q) {
                            $q->orderBy('title', 'asc');
                        }
                    ])
                    ->findOrFail($id);

                return ApiResponse::success(
                    new SubmenuResources($submenu),
                    'Success, take the detailed submenu'
                );
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return ApiResponse::error(
                    'Submenu not found',
                    ['id' => ['Data with that ID is not available']],
                    404
                );
            }
        }



        public function storeSubMenu(SubmenuValidationRequest $request)
            {
                $data = $request->validated();

                DB::beginTransaction();

                try {
                    $submenu = MsSubmenu::create([
                        'id_menu'   => $data['id_menu'],
                        'title'     => $data['title'],
                        'url'       => $data['url'],
                        'icon'      => $data['icon'] ?? null,
                        'noted'     => $data['noted'] ?? null,
                        'parent_id' => $data['parent_id'] ?? null,
                        'is_active' => $data['is_active'],
                    ]);

                    DB::commit();

                    return ApiResponse::success(
                        new SubmenuResources($submenu),
                        'Success Create New Submenu',
                        201
                    );

                } catch (\Illuminate\Database\QueryException $e) {

                    DB::rollBack();

                    return ApiResponse::error(
                        'Failed to create submenu (query error)',
                        config('app.debug') ? ['exception' => $e->getMessage()] : null,
                        422
                    );

                } catch (\Throwable $e) {

                    DB::rollBack();

                    return ApiResponse::error(
                        'An error occurred while creating the submenu.',
                        config('app.debug') ? ['exception' => $e->getMessage()] : null,
                        500
                    );
                }
            }

            public function updateSubMenu(SubmenuValidationUpdateRequest $request, $id)
            {
                $data = $request->validated();

                try {
                    $submenu = MsSubmenu::findOrFail($id);

                    $submenu->update([
                        'title'     => $data['title'],
                        'url'       => $data['url'],
                        'icon'      => $data['icon'],
                        'id_menu'   => $data['id_menu'],
                        'noted'     => $data['noted'] ?? null,
                        'is_active' => $data['is_active'],
                        'parent_id' => $data['parent_id'] ?? null,
                    ]);

                    return ApiResponse::success(
                        new SubmenuResources($submenu->fresh(['menu', 'children'])),
                        'Success update submenu'
                    );
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    return ApiResponse::error('Submenu not found', null, 404);
                } catch (\Exception $e) {
                    return ApiResponse::error(
                        'Failed to update submenu',
                        config('app.debug') ? $e->getMessage() : null,
                        500
                    );
                }
            }



            public function destroySubmenu($id)
            {
                try {
                    $submenu = MsSubmenu::withCount('children')->findOrFail($id);

                    //  Tidak boleh delete jika masih punya child
                    if ($submenu->children_count > 0) {
                        return ApiResponse::error(
                            'Submenu still has children, delete children first.',
                            null,
                            422
                        );
                    }

                    $submenu->delete(); // soft delete

                    return ApiResponse::success(
                        null,
                        'Submenu successfully deleted'
                    );
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    return ApiResponse::error('Submenu not found', null, 404);
                } catch (\Exception $e) {
                    return ApiResponse::error(
                        'Failed to delete submenu',
                        config('app.debug') ? $e->getMessage() : null,
                        500
                    );
                }
            }

           

        //  code access role - access menu
        /**
         * GET MENU + ACCESS ROLE
         */
        public function AccessRoleToMenu($roleId, AccessMenuValidationIndex $request)
        {
    
        $validated = $request->validated();
        $search  = $validated['search'] ?? null;
        $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
        $sortBy  = $validated['sort_by'] ?? 'mm.id_menu';
        $sortDir = $validated['sort_dir'] ?? 'asc';

        $query = DB::table('ms_menu as mm')
            // ->leftJoin('ms_access_menu as mam', function ($join) use ($roleId) {
            //     $join->on('mm.id_menu', '=', 'mam.id_menu')
            //         ->whereNull('mam.deleted_at')
            //         ->where('mam.id_role', '=', $roleId);
            // })
             ->whereNull('mm.deleted_at') // ⬅️ tambahan
    ->leftJoin('ms_access_menu as mam', function ($join) use ($roleId) {
        $join->on('mm.id_menu', '=', 'mam.id_menu')
            ->whereNull('mam.deleted_at')
            ->where('mam.id_role', '=', $roleId);
    })
            ->select(
                'mm.id_menu',
                'mm.menu',
                DB::raw('CASE WHEN mam.id_role IS NOT NULL THEN true ELSE false END as has_access')
            );

        //  Search
        if ($search) {
            $query->where('mm.menu', 'ILIKE', "%{$search}%");
        }

        //  Sorting
        $query->orderBy($sortBy, $sortDir);

        //  Pagination
        $results = $query->paginate($perPage);

        $message = $results->isEmpty()
            ? "The data you are looking for was not found"
            : "Success";

        return ApiResponse::paginate(
            new AccessMenuResourceCollection($results),
            $message
        );
    }



        public function changeAccessMenu(Request $request)
        {
            $menuId = $request->menuId;
            $roleId = $request->roleId;

            $exists = MsAccesMenu::where('id_role', $roleId)
                ->where('id_menu', $menuId)
                ->first();

            if ($exists) {
                $exists->delete();

                return response()->json([
                    'status' => 'removed',
                    'message' => 'Menu access has been successfully removed'
                ]);
            } else {
                MsAccesMenu::create([
                    'id_role' => $roleId,
                    'id_menu' => $menuId,
                ]);

                return response()->json([
                    'status' => 'added',
                    'message' => 'Menu access has been successfully added'
                ]);
            }
        }


        //  code user
        public function Users(UsersValidationIndex $request)
        {
            $validated = $request->validated();
            $search      = $validated['search'] ?? null;
            $perPage     = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
            $onlyDeleted = $validated['only_deleted'] ?? false;
            $sortBy      = $validated['sort_by'] ?? 'created_at';
            $sortDir     = ($validated['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

            $query = MsUsers::query()
                ->with([
                    'role:id_role,role',
                    'division:id,name_division',
                    'groups:id_group,name_group',
                ])
                ->when($onlyDeleted, function ($q) {
                    $q->onlyTrashed();
                })
                ->when(!$onlyDeleted, function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('fullname', 'LIKE', "%{$search}%")
                            ->orWhere('username', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
                })
                // ->orderBy($sortBy, $sortDir)
                // ->orderBy('id_user', 'desc');
                ->orderBy($sortBy, $sortDir)
            ->orderBy('id_user', 'desc');


            $results = $query->paginate($perPage);

            return ApiResponse::paginate(
                new UsersResourcesCollection($results),
                $results->isEmpty()
                    ? 'The data you are looking for was not found'
                    : 'Success'
            );
        }



        public function storeUser(UsersValidationRequest $request)
        {
            $data = $request->validated();

            try {

                /* ================= DUPLICATE CHECK ================= */
                $errors = MsUsers::isDuplicate($data);
                if (!empty($errors)) {
                    return ApiResponse::error('Validation failed', $errors, 400);
                }


            // default image
                        $data['image'] = 'default.png';

                        if ($request->hasFile('image')) {
                            $file = $request->file('image');
                            $filename = uniqid() . '.' . $file->getClientOriginalExtension();

                            $file->storeAs('users', $filename, 'public');

                            $data['image'] = $filename;
                        }



                /* ================= PASSWORD ================= */
                $data['password'] = Hash::make($data['password']);

                $user = $this->MsUsers->create($data);

                return ApiResponse::success(
                    new UsersResources($user),
                    'Success Create New User',
                    201
                );

            } catch (\Illuminate\Database\QueryException $e) {

                return ApiResponse::error(
                    'Failed to create user (query error)',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    422
                );

            } catch (\Exception $e) {

                return ApiResponse::error(
                    'An error occurred while creating the user.',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    500
                );
            }
        }




        // public function updateUser(UsersValidationUpdateRequest $request, $id_user)
        public function updateUser(UsersValidationUpdateRequest $request, $id_user)
        {
            $data = $request->validated();

            try {

                $user = $this->MsUsers->findOrFail($id_user);

                /* ================= DUPLICATE CHECK (IGNORE ID) ================= */
                $errors = MsUsers::isDuplicate($data, $id_user);
                if (!empty($errors)) {
                    return ApiResponse::error('Validation failed', $errors, 400);
                }

                    if ($request->hasFile('image')) {

                    if ($user->image && $user->image !== 'default.png'
                        && Storage::disk('public')->exists('users/' . $user->image)) {
                        Storage::disk('public')->delete('users/' . $user->image);
                    }

                    $file = $request->file('image');
                    $filename = uniqid() . '.' . $file->getClientOriginalExtension();

                    $file->storeAs('users', $filename, 'public');

                    $data['image'] = $filename;
                }

            // JIKA TIDAK ADA IMAGE → BIARKAN IMAGE LAMA



                /* ================= PASSWORD ================= */
                if (!empty($data['password'])) {
                    $data['password'] = Hash::make($data['password']);
                } else {
                    unset($data['password']);
                }

            $user->update($data);

        // //  WAJIB
        $user->refresh()->load('role');

        return ApiResponse::success(
            new UsersResources($user),
            'Success Update User'
        );


            } catch (\Illuminate\Database\QueryException $e) {

                return ApiResponse::error(
                    'Failed to update user (query error)',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    422
                );

            } catch (\Exception $e) {

                return ApiResponse::error(
                    'An error occurred while updating the user.',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    500
                );
            }
        }



        public function deleteUser($id_user)
        {
            try {
                $user = $this->MsUsers->findOrFail($id_user);

                /* ================= DELETE IMAGE ================= */
                if (
                    $user->image &&
                    $user->image !== 'default.png' &&
                    Storage::disk('public')->exists('users/' . $user->image)
                ) {
                    Storage::disk('public')->delete('users/' . $user->image);
                }

                /* ================= SOFT DELETE USER ================= */
                $user->delete();

                return ApiResponse::success(
                    null,
                    'Success Delete User'
                );

            } catch (\Illuminate\Database\QueryException $e) {

                return ApiResponse::error(
                    'Failed to delete user (query error)',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    422
                );

            } catch (\Exception $e) {

                return ApiResponse::error(
                    'An error occurred while deleting the user.',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    500
                );
            }
        }


        public function showUser($id_user)
        {
            try {
                $user = $this->MsUsers
                    ->with('role')
                    ->where('id_user', $id_user)
                    ->firstOrFail();

                return ApiResponse::success(
                    new UsersResources($user),
                    'Success Get User Detail'
                );

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

                return ApiResponse::error(
                    'User not found',
                    null,
                    404
                );

            } catch (\Exception $e) {

                return ApiResponse::error(
                    'An error occurred while fetching user detail',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    500
                );
            }
        }


                public function selectSubmenu()
                {
                    return response()->json(
                        MsSubmenu::query()
                            ->select('id_submenu', 'title', 'parent_id')
                            ->where('is_active', true)
                            ->orderBy('id_submenu', 'asc')
                            ->get()
                    );
                }

              public function selectMenu()
                {
                    return response()->json(
                        DB::table('ms_menu')
                            ->select('id_menu', 'menu')
                            ->orderBy('menu', 'asc')
                            ->get()
                    );
                }

                public function selectRole()
                {
                    return response()->json(
                        DB::table('ms_role')
                            ->select('id_role', 'role')
                            ->orderBy('role', 'asc')
                            ->get()
                    );
                }

                public function selectDivision()
                {
                    return response()->json(
                        DB::table('ms_division')
                            ->select('id', 'name_division')
                            ->orderBy('name_division', 'asc')
                            ->get()
                    );
                }

                public function selectGroup()
                {
                    return response()->json(
                        DB::table('group_companies')
                            ->select('id_group', 'name_group','is_active')
                            ->where('is_active', true)
                            ->orderBy('name_group', 'asc')
                            ->get()
                    );
                }



           public function submenuAccess(UserSubmenuAccessRequestIndex $request, $id_user)
            {
                $validated   = $request->validated();
                $search      = $validated['search'] ?? null;
                $perPage     = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
                $sortBy      = $validated['sort_by'] ?? 'sm.title';
                $sortDir     = $validated['sort_dir'] ?? 'asc';
                $onlyDeleted = $validated['only_deleted'] ?? false;

                $query = DB::table('ms_submenu as sm')
                ->leftJoin('ms_menu as mn', 'sm.id_menu', '=', 'mn.id_menu')
                ->leftJoin('ms_access_submenu as a', function ($join) use ($id_user) {
                    $join->on('a.id_submenu', '=', 'sm.id_submenu')
                        ->where('a.id_user', '=', $id_user);
                })
                ->leftJoin('ms_users as u', 'u.id_user', '=', DB::raw($id_user))
                ->select(
                    'mn.menu as menu_name',
                    'a.id_access_submenu',
                    DB::raw($id_user . ' as id_user'),
                    'u.fullname',
                    'sm.id_submenu',
                    'sm.title as submenu_name',
                    DB::raw('COALESCE(a.can_view, false) as can_view'),
                    DB::raw('COALESCE(a.can_create, false) as can_create'),
                    DB::raw('COALESCE(a.can_update, false) as can_update'),
                    DB::raw('COALESCE(a.can_delete, false) as can_delete'),
                    'a.created_at',
                    'a.updated_at'
                )
                ->orderBy('mn.menu')
                ->orderBy('sm.title');


                //  search
            // 🔍 search (menu & submenu)
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('sm.title', 'ILIKE', "%{$search}%")   // submenu
                    ->orWhere('mn.menu', 'ILIKE', "%{$search}%"); // menu
                });
            }

                // ↕ sorting (whitelist)
            $sortMap = [
                'submenu.title'      => 'sm.title',
                'submenu.created_at' => 'sm.created_at',
            ];

            if (isset($sortMap[$sortBy])) {
                $query->orderBy($sortMap[$sortBy], $sortDir);
            }


                // 🗑 soft delete (kalau ada)
                if ($onlyDeleted) {
                    $query->whereNotNull('a.deleted_at');
                }

                $results = $query->paginate($perPage);

                $message = $results->isEmpty()
                    ? "Data yang Anda cari tidak ditemukan"
                    : "Success";

                return ApiResponse::paginate(
                    new UserSubmenuAccessResourceCollection($results),
                    $message
                );
            }


        public function updateSubmenuAccess(
            UpdateUserSubmenuAccessRequest $request,
            $id_user,
            $id_submenu
        ) {
            $validated = $request->validated();

            DB::table('ms_access_submenu')
                ->updateOrInsert(
                    [
                        'id_user'    => $id_user,
                        'id_submenu' => $id_submenu
                    ],
                    [
                        'can_view'   => $validated['can_view'],
                        'can_create' => $validated['can_create'],
                        'can_update' => $validated['can_update'],
                        'can_delete' => $validated['can_delete'],
                        'updated_at' => now()
                    ]
                );

            return ApiResponse::success(
                null,
                'Permission berhasil diperbarui'
            );
        }





// code setting application
   public function SettingApp(AppSettingValidationIndex $request) 
       {
            $validated = $request->validated();
            $search = $validated['search'] ?? null;
            $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortDir = $validated['sort_dir'] ?? 'desc';
            $onlyDeleted = $validated['only_deleted'] ?? false;

            $query = $this->AppSettingModel
                ->onlyDeleted($onlyDeleted)
                ->search($search)
                ->sort($sortBy, $sortDir);
            $results = $query->paginate($perPage);
            $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
            return ApiResponse::paginate(new AppSettingResourcesCollection($results), $message);
       }


       public function showAppSetting(string $id)
        {
            $appSetting = AppSettingModel::find($id);

            if (!$appSetting) {
                return ApiResponse::error(
                    'App setting not found',
                    [
                        'id' => ['Data with that ID is not available']
                    ],
                    404
                );
            }

            return ApiResponse::success(
                new AppSettingResources($appSetting),
                'Success, take the detailed App Setting',
                200
            );
        }


       public function storeSetting(AppSettingValidationRequest $request)  
       {
           $data = $request->validated();

            try {

                /* ================= DUPLICATE CHECK ================= */
                $errors = AppSettingModel::isDuplicate($data);
                if (!empty($errors)) {
                    return ApiResponse::error('Validation failed', $errors, 400);
                }

                /* ================= DEFAULT FILE ================= */
                $data['app_logo'] = $data['app_logo'] ?? 'default-logo.png';
                $data['app_logo_small'] = $data['app_logo_small'] ?? 'default-logo-small.png';
                $data['favicon'] = $data['favicon'] ?? 'default-favicon.ico';

                /* ================= FILE UPLOAD ================= */
                if ($request->hasFile('app_logo')) {
                    $file = $request->file('app_logo');
                    $filename = uniqid('logo_') . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('app-setting', $filename, 'public');
                    $data['app_logo'] = $filename;
                }

                if ($request->hasFile('app_logo_small')) {
                    $file = $request->file('app_logo_small');
                    $filename = uniqid('logo_small_') . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('app-setting', $filename, 'public');
                    $data['app_logo_small'] = $filename;
                }

                if ($request->hasFile('favicon')) {
                    $file = $request->file('favicon');
                    $filename = uniqid('favicon_') . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('app-setting', $filename, 'public');
                    $data['favicon'] = $filename;
                }

                /* ================= CREATE ================= */
                $appSetting = AppSettingModel::create($data);

                return ApiResponse::success(
                    new AppSettingResources($appSetting),
                    'Success Create App Setting',
                    201
                );

            } catch (\Illuminate\Database\QueryException $e) {

                return ApiResponse::error(
                    'Failed to create app setting (query error)',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    422
                );

            } catch (\Exception $e) {

                return ApiResponse::error(
                    'An error occurred while creating the app setting.',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    500
                );
            }
        }


        public function updateSetting(AppSettingValidationUpdateRequest $request, $id)
        {
            $data = $request->validated();

            try {

                /* ================= FIND DATA ================= */
                $appSetting = AppSettingModel::find($id);

                if (!$appSetting) {
                    return ApiResponse::error(
                        'App setting not found',
                        [],
                        404
                    );
                }

                /* ================= FOLDER ================= */
                $folder = 'app-setting';

                /* ================= APP LOGO ================= */
                if ($request->hasFile('app_logo')) {

                    // DELETE OLD FILE (SESUAI STORE)
                    if (
                        $appSetting->app_logo &&
                        Storage::disk('public')->exists($folder . '/' . $appSetting->app_logo)
                    ) {
                        Storage::disk('public')->delete($folder . '/' . $appSetting->app_logo);
                    }

                    $file = $request->file('app_logo');
                    $filename = uniqid('logo_') . '.' . $file->getClientOriginalExtension();
                    $file->storeAs($folder, $filename, 'public');

                    // SIMPAN NAMA FILE SAJA
                    $data['app_logo'] = $filename;
                }

                /* ================= APP LOGO SMALL ================= */
                if ($request->hasFile('app_logo_small')) {

                    if (
                        $appSetting->app_logo_small &&
                        Storage::disk('public')->exists($folder . '/' . $appSetting->app_logo_small)
                    ) {
                        Storage::disk('public')->delete($folder . '/' . $appSetting->app_logo_small);
                    }

                    $file = $request->file('app_logo_small');
                    $filename = uniqid('logo_small_') . '.' . $file->getClientOriginalExtension();
                    $file->storeAs($folder, $filename, 'public');

                    $data['app_logo_small'] = $filename;
                }

                /* ================= FAVICON ================= */
                if ($request->hasFile('favicon')) {

                    if (
                        $appSetting->favicon &&
                        Storage::disk('public')->exists($folder . '/' . $appSetting->favicon)
                    ) {
                        Storage::disk('public')->delete($folder . '/' . $appSetting->favicon);
                    }

                    $file = $request->file('favicon');
                    $filename = uniqid('favicon_') . '.' . $file->getClientOriginalExtension();
                    $file->storeAs($folder, $filename, 'public');

                    $data['favicon'] = $filename;
                }

                /* ================= UPDATE ================= */
                $appSetting->update($data);

                return ApiResponse::success(
                    new AppSettingResources($appSetting->fresh()),
                    'Success Update App Setting',
                    200
                );

            } catch (\Illuminate\Database\QueryException $e) {

                return ApiResponse::error(
                    'Failed to update app setting (query error)',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    422
                );

            } catch (\Exception $e) {

                return ApiResponse::error(
                    'An error occurred while updating the app setting.',
                    ['exception' => config('app.debug') ? $e->getMessage() : null],
                    500
                );
            }
        }


        public function deleteSetting($id)
            {
                try {

                    $appSetting = AppSettingModel::find($id);

                    if (!$appSetting) {
                        return ApiResponse::error(
                            'App setting not found',
                            [],
                            404
                        );
                    }

                    $folder = 'app-setting';

                    /* ================= DELETE FILE ================= */
                    if (
                        $appSetting->app_logo &&
                        Storage::disk('public')->exists($folder . '/' . $appSetting->app_logo)
                    ) {
                        Storage::disk('public')->delete($folder . '/' . $appSetting->app_logo);
                    }

                    if (
                        $appSetting->app_logo_small &&
                        Storage::disk('public')->exists($folder . '/' . $appSetting->app_logo_small)
                    ) {
                        Storage::disk('public')->delete($folder . '/' . $appSetting->app_logo_small);
                    }

                    if (
                        $appSetting->favicon &&
                        Storage::disk('public')->exists($folder . '/' . $appSetting->favicon)
                    ) {
                        Storage::disk('public')->delete($folder . '/' . $appSetting->favicon);
                    }

                    /* ================= DELETE DATA ================= */
                    $appSetting->delete();

                    return ApiResponse::success(
                        null,
                        'Success Delete App Setting',
                        200
                    );

                } catch (\Illuminate\Database\QueryException $e) {

                    return ApiResponse::error(
                        'Failed to delete app setting (query error)',
                        ['exception' => config('app.debug') ? $e->getMessage() : null],
                        422
                    );

                } catch (\Exception $e) {

                    return ApiResponse::error(
                        'An error occurred while deleting the app setting.',
                        ['exception' => config('app.debug') ? $e->getMessage() : null],
                        500
                    );
                }
            }





            // code for cabang

            public function Cabang(CabangValidationIndex $request) 
       {
            $validated = $request->validated();
            $search = $validated['search'] ?? null;
            $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortDir = $validated['sort_dir'] ?? 'desc';
            $onlyDeleted = $validated['only_deleted'] ?? false;

            $query = $this->MsCabang
                ->onlyDeleted($onlyDeleted)
                ->search($search)
                ->sort($sortBy, $sortDir);
            $results = $query->paginate($perPage);
            $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
            return ApiResponse::paginate(new CabangResourcesCollection($results), $message);
       }


        public function showCabang(string $id)
        {
            $Cabang = $this->MsCabang->find($id);
            if (!$Cabang) {
                return ApiResponse::error('Cabang not found', [
                    'id' => ['Data with that ID is not available']
                ], 404);
            }
            return ApiResponse::success(new CabangResources($Cabang), 'Success, take the detailed Cabang', 200);
        }



        public function storeCabang(CabangValidationRequest $request)
        {
            $data = $request->validated();

            try {
        
                $errors = MsCabang::isDuplicate($data); 
                if (!empty($errors)) {
                        return ApiResponse::error('Validation failed', $errors, 400);
                    }

                $Cabang = $this->MsCabang->create([
                    'cabang'        => $data['cabang'],
                    'alamat' => $data['alamat'] ?? null,
                    'no_telp' => $data['no_telp'] ?? null,
                ]);

                return ApiResponse::success(new CabangResources($Cabang), 'Success Create New Cabang', 201);

            } catch (\Illuminate\Database\QueryException $e) {
                return ApiResponse::error('Failed to create cabang (query error)', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 422);
            } catch (\Exception $e) {
                return ApiResponse::error('An error occurred while creating the cabang.', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }


        public function updateCabang(CabangValidationRequest $request, $id_cabang)
        {
            $data = $request->validated();

            $Cabang = MsCabang::find($id_cabang);

            if (!$Cabang) {
                return ApiResponse::error(
                    'Cabang with that ID was not found.',
                    ['id_cabang' => ['Data not available.']],
                    404
                );
            }

            try {
                $errors = MsCabang::isDuplicate($data, $id_cabang);
                if (!empty($errors)) {
                    return ApiResponse::error('Validation failed', $errors, 400);
                }

                $Cabang->update($data);
                return ApiResponse::success(new CabangResources($Cabang), 'Success Update Cabang', 200);
            } catch (\Illuminate\Database\QueryException $e) {
                return ApiResponse::error('Failed to update Cabang (query error)', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 422);
            } catch (\Exception $e) {
                return ApiResponse::error('Failed to update Cabang', [
                    'exception' => config('app.debug') ? $e->getMessage() : 'Please try again later'
                ], 500);
            }
        }



         public function destroyCabang(string $id_cabang)
        {
            try {
                $Cabang = $this->MsCabang->find($id_cabang);
            if (!$Cabang) {
                    return ApiResponse::error('Cabang with that ID was not found.', [
                        'id' => ['Data not availiable.']
                    ], 404);
                }
                $Cabang->delete();
                return ApiResponse::success(new CabangResources($Cabang), 'Success Delete Cabang', 200);
            } catch (\Exception $e) {
                return ApiResponse::error('Failed to delete Cabang', [
                    'exception' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }




}