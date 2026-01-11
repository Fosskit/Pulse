<?php

namespace App\Actions\Admin\Permissions;

use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class ListPermissionsAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $permissions = Permission::where('guard_name', 'api')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => PermissionResource::collection($permissions->items()),
            'meta' => [
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
            ],
        ]);
    }
}
