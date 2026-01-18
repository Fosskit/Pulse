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

        $query = Permission::where('guard_name', 'api');

        // Filter by group (prefix before first dot)
        if ($request->filled('group') && $request->input('group') !== 'all') {
            $group = $request->input('group');
            $query->where(function ($q) use ($group) {
                $q->where('name', 'like', $group . '.%')
                  ->orWhere('name', $group); // In case group is the whole name
            });
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%$search%");
        }

        $permissions = $query->paginate($request->get('per_page', 15));

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
