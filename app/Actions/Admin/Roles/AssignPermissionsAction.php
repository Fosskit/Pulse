<?php

namespace App\Actions\Admin\Roles;

use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssignPermissionsAction
{
    public function __invoke(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        $permissions = Permission::whereIn('name', $request->permissions)->where('guard_name', 'api')->get();
        $role->syncPermissions($permissions);

        $role->load('permissions');

        return response()->json([
            'message' => 'Permissions assigned to role successfully.',
            'data' => new RoleResource($role),
        ]);
    }
}
