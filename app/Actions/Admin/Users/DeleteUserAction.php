<?php

namespace App\Actions\Admin\Users;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class DeleteUserAction
{
    public function __invoke(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
