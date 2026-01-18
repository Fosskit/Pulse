<?php

namespace App\Actions\Admin\Roles;

use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class StoreRoleAction
{
    public function __invoke(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'api',
        ]);

        $role->load('permissions');

        // Log activity
        app(\App\Services\ActivityLogService::class)->log(
            action: 'role.created',
            model: 'Role',
            modelId: $role->id,
            description: "Role '{$role->name}' was created",
            request: $request
        );

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => new RoleResource($role),
        ], 201);
    }
}
