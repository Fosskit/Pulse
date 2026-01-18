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

        return response()->json([
            'message' => 'Permission created successfully.',
            'data' => new PermissionResource($permission),
        ], 201);
    }
}
