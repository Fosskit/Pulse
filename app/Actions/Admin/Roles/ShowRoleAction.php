<?php

namespace App\Actions\Admin\Roles;

use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class ShowRoleAction
{
    public function __invoke(Role $role): JsonResponse
    {
        $role->load('permissions');

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }
}
