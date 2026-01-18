<?php

namespace App\Actions\Admin\Permissions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class ListPermissionGroupsAction
{
    public function __invoke(): JsonResponse
    {
        // Get all unique permission groups (prefix before first dot)
        $groups = Permission::query()
            ->select(DB::raw('DISTINCT SUBSTRING_INDEX(name, ".", 1) as group_name'))
            ->pluck('group_name')
            ->filter()
            ->values();

        return response()->json([
            'data' => $groups,
        ]);
    }
}
