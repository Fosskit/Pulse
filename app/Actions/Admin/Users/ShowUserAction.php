<?php

namespace App\Actions\Admin\Users;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ShowUserAction
{
    public function __invoke(User $user): JsonResponse
    {
        $user->load(['roles.permissions', 'permissions']);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }
}
