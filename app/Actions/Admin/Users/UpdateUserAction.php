<?php

namespace App\Actions\Admin\Users;

use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UpdateUserAction
{
    public function __invoke(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Assign roles if provided
        if ($request->has('roles')) {
            $roles = Role::whereIn('name', $request->roles)->where('guard_name', 'api')->get();
            $user->syncRoles($roles);
        }

        // Assign permissions if provided
        if ($request->has('permissions')) {
            $permissions = Permission::whereIn('name', $request->permissions)->where('guard_name', 'api')->get();
            $user->syncPermissions($permissions);
        }

        $user->load(['roles.permissions', 'permissions']);

        // Log activity
        app(\App\Services\ActivityLogService::class)->log(
            action: 'user.updated',
            model: 'User',
            modelId: $user->id,
            description: "User '{$user->name}' was updated",
            request: $request
        );

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new UserResource($user),
        ]);
    }
}
