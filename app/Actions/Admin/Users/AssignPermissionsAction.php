<?php

namespace App\Actions\Admin\Users;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class AssignPermissionsAction
{
    public function __invoke(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        $permissions = Permission::whereIn('name', $request->permissions)->where('guard_name', 'api')->get();
        $user->syncPermissions($permissions);

        $user->load(['roles.permissions', 'permissions']);

        return response()->json([
            'message' => 'Permissions assigned successfully.',
            'data' => new \App\Http\Resources\UserResource($user),
        ]);
    }
}
