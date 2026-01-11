<?php

namespace App\Actions\Admin\Users;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListUsersAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $users = User::with(['roles.permissions', 'permissions'])
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }
}
