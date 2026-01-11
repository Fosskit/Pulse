<?php

namespace App\Actions\Admin\Users;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AssignRolesAction
{
    public function __invoke(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
        ]);

        $roles = Role::whereIn('name', $request->roles)->where('guard_name', 'api')->get();
        $user->syncRoles($roles);

        $user->load(['roles.permissions', 'permissions']);

        return response()->json([
            'message' => 'Roles assigned successfully.',
            'data' => new \App\Http\Resources\UserResource($user),
        ]);
    }
}
