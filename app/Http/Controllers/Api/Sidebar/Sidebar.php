<?php

namespace App\Http\Controllers\Api\Sidebar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class Sidebar extends Controller
{


public function getMenusByRole($role_id)
    {
        $menus = DB::table('ms_menu as mm')
            ->leftJoin('ms_access_menu as mam', 'mm.id_menu', '=', 'mam.id_menu')
            ->select('mm.id_menu', 'mm.menu', 'mam.id_access_menu', 'mam.id_role')
            ->where('mam.id_role', $role_id)
             ->whereNull('mam.deleted_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $menus
        ]);
    }






public function getSubmenu(Request $request)
{
    $user_id = $request->id_user ?? Auth::id();  
    $menu_ids = $request->menu_ids ?? [];
    // $user_id = 1;
    // $menu_ids = [1];

    if (empty($menu_ids)) {
        return response()->json([
            'status' => 'error',
            'message' => 'menu_ids is required'
        ], 400);
    }

    // AMBIL SUBMENU PARENT
    $parents = DB::table('ms_submenu AS s')
        ->join('ms_access_submenu AS ua', 's.id_submenu', '=', 'ua.id_submenu')
        ->whereIn('s.id_menu', $menu_ids)
        ->where('ua.id_user', $user_id)
        ->where('s.is_active', 1)
        ->whereNull('s.deleted_at') 
        ->where(function ($q) {
            $q->whereNull('s.parent_id')->orWhere('s.parent_id', 0);
        })
        ->select(
            's.id_submenu',
            's.id_menu',
            's.title',
            's.url',
            's.icon',
            's.parent_id',

            // PERMISSION BARU
            'ua.can_view',
            'ua.can_create',
            'ua.can_update',
            'ua.can_delete'
        )
        ->orderBy('s.id_submenu', 'asc')
        ->get();

    // AMBIL SUBMENU CHILD
    $children = DB::table('ms_submenu AS s')
        ->join('ms_access_submenu AS ua', 's.id_submenu', '=', 'ua.id_submenu')
        ->whereIn('s.id_menu', $menu_ids)
        ->where('ua.id_user', $user_id)
        ->where('s.is_active', 1)
        ->whereNull('s.deleted_at') 
        ->whereNotNull('s.parent_id')
        ->select(
            's.id_submenu',
            's.id_menu',
            's.title',
            's.url',
            's.icon',
            's.parent_id',

            // PERMISSION BARU
            'ua.can_view',
            'ua.can_create',
            'ua.can_update',
            'ua.can_delete'
        )
        // ->orderBy('s.title', 'asc')
        ->orderBy('s.id_submenu', 'asc')
        ->get();

    // GROUP CHILD BY PARENT
    $groupedChildren = $children->groupBy('parent_id');

    // COMBINE PARENT + CHILD
    $result = $parents->map(function ($p) use ($groupedChildren) {
        $p->children = $groupedChildren[$p->id_submenu] ?? [];
        return $p;
    });

    return response()->json([
        'status' => 'success',
        'data' => $result
    ]);
}




public function getUserPermissions(Request $request)
{
    $user_id = $request->id_user ?? Auth::id();
    // $user_id = 1;

    if (!$user_id) {
        return response()->json([
            'success' => false,
            'message' => 'id_user is required'
        ], 422);
    }

    // Ambil semua submenu + permission milik user
    $permissions = DB::table('ms_submenu AS s')
        ->join('ms_access_submenu AS ua', 's.id_submenu', '=', 'ua.id_submenu')
        ->where('ua.id_user', $user_id)
        ->select(
            's.url',
            'ua.can_view',
            'ua.can_create',
            'ua.can_update',
            'ua.can_delete'
        )
        ->get();

    // Ubah menjadi object keyed by url
    $result = [];

    foreach ($permissions as $p) {
        $result[$p->url] = [
            "can_view" => (bool)$p->can_view,
            "can_create" => (bool)$p->can_create,
            "can_update" => (bool)$p->can_update,
            "can_delete" => (bool)$p->can_delete
        ];
    }

    return response()->json([
        'status' => 'success',
        'permissions' => $result
    ]);
}

}
