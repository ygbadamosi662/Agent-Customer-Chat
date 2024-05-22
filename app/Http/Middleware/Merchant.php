<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Role;
use App\RoleAndPermission;

class Merchant
{
    public function handle($request, Closure $next, ... $role_array_num)

    {
        $member_type = ['merchant', 'premium', 'merchant_member'];
        if (!Auth::check() || !Auth::user() || !in_array(optional(Auth::user())->type, $member_type)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (Auth::check() &&  (in_array(optional(Auth::user())->type, $member_type) == false)) {
            return _failed('You have no permission to view this page!');
        }

        $user = Auth::user();
        $role_permissions = RoleAndPermission::select('permission')->where('role_id', $user->member_role)->first();
        $permissions= json_decode($role_permissions->permission);
        $routeids = [];

        if (!empty($permissions)){
            foreach($permissions as $row) {
                $routeids[]=$row->route;
            }
            if(in_array($role_array_num[0], $routeids) || $role_array_num[0] == 1)
                    return $next($request);
        }

        return _failed('You have no permission to view this page!');


    }


}
