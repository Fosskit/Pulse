<?php

namespace App\Actions\Admin\Users;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class StoreUserAction
{
    public function __invoke(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

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

        return response()->json([
            'message' => 'User created successfully.',
            'data' => new UserResource($user),
        ], 201);
    }
}
