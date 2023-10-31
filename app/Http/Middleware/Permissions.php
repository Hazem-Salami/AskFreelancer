<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\RolePermission;
use Closure;
use Illuminate\Http\Request;

class Permissions
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param null $permission
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        $permissions = [];
        $response = [
            'status' => false,
            'message' => 'You do not have permission',
            'data' => $permission,
        ];

        config(['auth.guards.admin-api.driver' => 'session']);
        $user = Admin::find(auth('admin-api')->user()->id);

        if ($user->role_id == 1)
            return $next($request);

        $role_has_permissions = RolePermission::where('role_id', $user->role_id)->get();

        foreach ($role_has_permissions as $role_has_permission) {
            $permissions = Permission::
            where('id', $role_has_permission->permission_id)->get();
        }

        $permission = $request->route()->getName();

        for ($i = 0; $i < sizeof($permissions); $i++) {
            if ($permission === $permissions[$i]->name)
                return $next($request);
        }
        return response($response, 403);

    }
}
