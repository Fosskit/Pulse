<?php

namespace App\Actions\Admin\Roles;

use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class DeleteRoleAction
{
    public function __invoke(Role $role): JsonResponse
    {
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }
}
