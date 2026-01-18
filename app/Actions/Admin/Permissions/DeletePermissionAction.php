<?php

namespace App\Actions\Admin\Permissions;

use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class DeletePermissionAction
{
    public function __invoke(Permission $permission): JsonResponse
    {
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully.',
        ]);
    }
}
