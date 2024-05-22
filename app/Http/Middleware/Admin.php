<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Role;
use App\RoleAndPermission;


class Admin
{
    public function handle($request, Closure $next, ...$role_array_num)
    {
    
        if(!Auth::check() || optional(Auth::user())->type != 'admin'){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = Auth::user();
        $role_permissions = RoleAndPermission::select('admin_permission')->where('role_id', $user->member_role)->first();
        $permissions= json_decode($role_permissions->admin_permission);
        $routeids = [];

        foreach($permissions as $row) {
            $routeids[]=$row->route;
        }

        if(in_array($role_array_num[0], $routeids) || $role_array_num[0] == 1)
                return $next($request);

        return _failed('You have no permission to view this page!');
        // return $next($request);
    }

   
}
