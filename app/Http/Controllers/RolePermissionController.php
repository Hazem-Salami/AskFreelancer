<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\ResponseTrait;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use function Symfony\Component\Console\Style\success;

class RolePermissionController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        return $this->success('Roles', Role::paginate(10));
    }

    public function allRoles()
    {
        return $this->success('Roles', Role::all());
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'name' => 'required|string|min:3|max:15|unique:roles,name',
            'permission' => 'required|array|min:1',
            'permission.*' => 'required|integer|min:1|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        } else {
            $Permissions = [];
            $role = Role::create([
                'name' => $request->get('name'),
            ]);

            if ($request->has('permission')) {
                $permissions = $request->get('permission');
                foreach ($permissions as $permission) {
                    $Permission = Permission::find($permission);
                    $Permissions[] = $Permission;
                    RolePermission::create([
                        'permission_id' => $Permission->id,
                        'role_id' => $role->id,
                    ]);
                }
            }

            $message = 'Creating Success';
            $response = [
                'role' => $role,
                'permissions' => $Permissions
            ];
            return $this->success($message, $response);
        }
    }

    public function show($id)
    {
        $role = Role::find($id);
        if ($role === null)
            return $this->failed('There is no role with this ID');

        $permissions = DB::table('role_permissions')
            ->Join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role_id', $id)
            ->select('permissions.id', 'permissions.name')
            ->get();

        $response = [
            'role' => $role,
            'permission' => $permissions
        ];
        return $this->success('Role ' . $id, $response);
    }

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:15',
            'permission' => 'array',
            'permission.*' => 'required|integer|min:1|exists:permissions,id',
            'delete_permission' => 'array',
            'delete_permission.*' => 'required|integer|min:1|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        } else {
            $role = Role::find($id);
            if ($role === null)
                return $this->failed('There is no role with this ID');
            $role->name = $request->get('name');

            if ($request->has('delete_permission')) {

                $permissions = $request->get('delete_permission');

                foreach ($permissions as $Permission) {

                    DB::table('role_permissions')
                        ->Join('roles', 'role_permissions.role_id', '=', 'roles.id')
                        ->where('role_permissions.permission_id', $Permission)
                        ->delete();
                }
            }

            if ($request->has('permission')) {

                $permissions = $request->get('permission');

                foreach ($permissions as $Permission) {

                    RolePermission::create([
                        'permission_id' => $Permission,
                        'role_id' => $role->id,
                    ]);
                }
            }

            $role->save();
            $message = 'Updating Success';
            return $this->success($message);
        }
    }

    public function destroy($id)
    {
        $role = Role::find($id);
        if ($role === null)
            return $this->failed('There is no role with this ID');
        Role::destroy($id);
        return $this->success('Deleted Success!');
    }

    public function permissions()
    {
        return $this->success('Permissions', Permission::paginate(10));
    }

    public function allPermissions()
    {
        return $this->success('Permissions', Permission::all());
    }

    public function getExceptPermission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'except' => 'array',
            'except.*' => 'required|integer|min:1|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        } else {
            if ($request->has('except')) {
                $except = $request->get('except');
                return $this->success('My Permissions',
                    Permission::whereNotIn('id', $except)->get());
            } else {
                return $this->success('My Permissions', Permission::all());
            }
        }
    }
}
