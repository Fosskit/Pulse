<?php

namespace App\Actions\Admin\Roles;

use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class UpdateRoleAction
{
    public function __invoke(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $role->update($request->only('name'));
        $role->load('permissions');

        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => new RoleResource($role),
        ]);
    }
}
