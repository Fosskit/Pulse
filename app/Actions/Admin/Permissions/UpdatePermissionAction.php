<?php

namespace App\Actions\Admin\Permissions;

use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class UpdatePermissionAction
{
    public function __invoke(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission->update($request->only('name'));

        return response()->json([
            'message' => 'Permission updated successfully.',
            'data' => new PermissionResource($permission),
        ]);
    }
}
