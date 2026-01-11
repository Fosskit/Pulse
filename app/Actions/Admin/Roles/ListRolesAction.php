<?php

namespace App\Actions\Admin\Roles;

use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ListRolesAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $roles = Role::where('guard_name', 'api')
            ->with('permissions')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => RoleResource::collection($roles->items()),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
        ]);
    }
}
