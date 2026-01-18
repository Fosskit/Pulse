<?php

namespace App\Actions\Admin\Permissions;

use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class ShowPermissionAction
{
    public function __invoke(Permission $permission): JsonResponse
    {
        return response()->json([
            'data' => new PermissionResource($permission),
        ]);
    }
}
