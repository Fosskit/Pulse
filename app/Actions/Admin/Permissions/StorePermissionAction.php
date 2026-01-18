<?php

namespace App\Actions\Admin\Permissions;

use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class StorePermissionAction
{
    public function __invoke(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => 'api',
        ]);

        // Log activity
        app(\App\Services\ActivityLogService::class)->log(
            action: 'permission.created',
            model: 'Permission',
            modelId: $permission->id,
            description: "Permission '{$permission->name}' was created",
            request: $request
        );

        return response()->json([
            'message' => 'Permission created successfully.',
            'data' => new PermissionResource($permission),
        ], 201);
    }
}
